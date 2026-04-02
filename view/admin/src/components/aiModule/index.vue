<template>
  <div class="ai-module">
    <!-- 触发按钮 -->
    <span
      v-if="mode === 'drawer'"
      class="ai-trigger"
      @click="handleTrigger"
      :class="{ 'popover-mode': mode === 'popover' }"
    >
      <img class="ai-icon mr-4" src="@/assets/images/ai-icon.png" alt="" />
      {{ triggerText }}
    </span>

    <!-- Popover 模式 -->
    <Poptip
      v-if="mode === 'popover'"
      v-model="popoverVisible"
      placement="bottom"
      width="300"
      trigger="click"
    >
      <span v-if="mode === 'popover'" class="ai-trigger">
        <img class="ai-icon mr-4" src="@/assets/images/ai-icon.png" alt="" />
        {{ triggerText }}
      </span>
      <div slot="content">
        <div class="popover-content">
          <div class="modal-title">{{ popoverTitle || "AI智能生成" }}</div>
          <Input
            v-model="popoverInput"
            type="textarea"
            :rows="5"
            placeholder="请输入要润色的内容"
          />
          <!-- <div class="popover-actions" @click="generateFromPopover">
            立即生成
          </div> -->
          <div class="actions">
            <Button
              type="primary"
              :loading="generating"
              icon="md-paper-plane"
              @click="generateFromPopover"
              >立即生成</Button
            >
          </div>
        </div>
      </div>
    </Poptip>

    <!-- 右侧弹窗模式 -->
    <div v-if="mode === 'drawer'" class="modal-wrapper">
      <transition name="modal-fade">
        <div v-show="drawerVisible" class="drawer-content">
          <div class="card"></div>
          <!-- 标题区域 -->
          <div class="modal-title">
            <img
              class="ai-icon mr-4"
              src="@/assets/images/ai-icon.png"
              alt=""
            />AI智能生成
          </div>
          <!-- 输入区域 -->
          <div class="modal-content">
            <div class="input-section">
              <Input
                v-model="inputContent"
                type="textarea"
                :rows="4"
                placeholder="请输入要润色的内容"
              />
              <div class="action-buttons">
                <!-- <div class="generate-btn" @click="generateContent">
                  <Icon class="mr-4" type="md-paper-plane" />
                  {{ this.requestIndex > 0 ? "重新生成" : "生成" }}
                </div> -->
                <Button
                  type="primary"
                  :loading="generating"
                  icon="md-paper-plane"
                  @click="generateContent"
                >
                  {{ this.requestIndex > 0 ? "重新生成" : "生成" }}
                </Button>
              </div>
            </div>

            <!-- 生成结果区域 -->
            <div class="result-section" v-if="currentResult.length > 0">
              <div
                class="result-content"
                v-for="(item, index) in currentResult"
                :key="index"
              >
                <div class="result-text">{{ item }}</div>
                <div class="result-actions flex">
                  <div class="btn" @click="copyResult(item)">复制</div>
                  <div>｜</div>
                  <div class="btn" @click="fillResult(item)">填入</div>
                </div>
              </div>
            </div>

            <!-- 历史记录 -->
            <div class="result-section" v-if="generatedHistory.length > 0">
              <div class="section-title">
                <div class="line"></div>
                <div class="title">历史记录</div>
                <div class="line"></div>
              </div>
              <div
                class="result-content"
                v-for="(item, index) in generatedHistory"
                :key="index"
              >
                <div class="result-text">{{ item }}</div>
                <div class="result-actions flex">
                  <div class="btn" @click="copyResult(item)">复制</div>
                  <div>｜</div>
                  <div class="btn" @click="fillResult(item)">填入</div>
                </div>
              </div>
            </div>
            <!-- 关闭按钮 -->
            <div class="close-button" @click="closeDrawer">
              <Icon type="ios-close" size="24" color="rgba(0,0,0,0.25)" />
            </div>
          </div>
        </div>
      </transition>
    </div>
    <div
      v-if="drawerVisible && closable"
      class="drawer-mask"
      @click="closeDrawer"
    ></div>
  </div>
</template>

<script>
import { chatAi } from "@/api/system";
export default {
  name: "AiModule",
  props: {
    // 显示模式：drawer(右侧弹窗) 或 popover
    mode: {
      type: String,
      default: "drawer",
      validator: (value) => ["drawer", "popover"].includes(value),
    },
    // 触发按钮文字
    triggerText: {
      type: String,
      default: "AI润色",
    },
    // popover 标题
    popoverTitle: {
      type: String,
      default: "",
    },
    // 默认输入内容
    defaultContent: {
      type: String,
      default: "",
    },
    // 额外的请求参数
    apiParams: {
      type: Object,
      default: () => ({}),
    },
    closable: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      drawerVisible: false,
      popoverVisible: false,
      inputContent: "",
      popoverInput: "",
      currentResult: [],
      generatedHistory: [],
      generating: false,
      requestIndex: 0,
    };
  },
  watch: {
    defaultContent: {
      immediate: true,
      handler(newVal) {
        this.inputContent = newVal;
        this.popoverInput = newVal;
      },
    },
  },
  methods: {
    // 处理触发事件
    handleTrigger() {
      if (this.mode === "drawer") {
        this.drawerVisible = true;
        this.inputContent = this.defaultContent;
      } else {
        this.popoverVisible = true;
        this.popoverInput = this.defaultContent;
      }
    },

    // 生成内容
    async generateContent() {
      if (!this.inputContent.trim()) {
        this.$Message.warning("请输入要润色的内容");
        return;
      }

      this.generating = true;
      try {
        const response = await chatAi({
          message: this.inputContent,
          ...this.apiParams,
        });
        if (response.data && response.status === 200) {
          const result = response.data;
          this.currentResult = result;
          // 添加到历史记录（避免重复）
          if (
            result.length &&
            !this.hasSameElement(result, this.generatedHistory) &&
            this.requestIndex
          ) {
            console.log(this.requestIndex);
            this.generatedHistory.unshift(...result);
            // 限制历史记录数量
            if (this.generatedHistory.length > 10) {
              this.generatedHistory = this.generatedHistory.slice(0, 10);
            }
          }
          this.requestIndex++;
        } else {
          this.$Message.error(response.data.msg || "生成失败");
        }
      } catch (error) {
        console.error("AI生成错误:", error);
        this.$Message.error("生成失败，请稍后重试");
      } finally {
        this.generating = false;
      }
    },
    // 检查两个数组是否有相同的元素
    hasSameElement(arr1, arr2) {
      return arr1.some((item) => arr2.includes(item));
    },
    // Popover模式生成
    async generateFromPopover() {
      if (!this.popoverInput.trim()) {
        this.$Message.warning("请输入要润色的内容");
        return;
      }

      this.generating = true;
      try {
        const response = await chatAi({
          message: this.popoverInput,
          ...this.apiParams,
        });

        if (response.data && response.status === 200) {
          const result = response.data || response.data;
          this.$emit("generated", result);
          this.closePopover();
        } else {
          this.$Message.error(response.data.msg || "生成失败");
        }
      } catch (error) {
        console.error("AI生成错误:", error);
        this.$Message.error("生成失败，请稍后重试");
      } finally {
        this.generating = false;
      }
    },

    // 复制结果
    copyResult(item) {
      this.copyText(item);
    },

    // 填入结果
    fillResult(item) {
      this.fillText(item);
    },

    // 复制文本
    copyText(text) {
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
          this.$Message.success("复制成功");
        });
      } else {
        // 兼容性处理
        const textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);
        this.$Message.success("复制成功");
      }
    },

    // 填入文本
    fillText(text) {
      console.log("fillText", text);
      this.$emit("fill", text);
      if (this.mode === "drawer") {
        // this.drawerVisible = false;
      }
    },

    // 选择历史记录
    selectHistory(item) {
      this.currentResult = item;
    },

    // 关闭弹窗
    closeDrawer() {
      this.drawerVisible = false;
      this.clearData();
    },

    // 关闭Popover
    closePopover() {
      this.popoverVisible = false;
      this.popoverInput = "";
    },

    // 清除数据
    clearData() {
      // this.currentResult = [];
      // this.generatedHistory = [];
      this.inputContent = "";
    },
  },
};
</script>

<style lang="less" scoped>
// 定义宽度变量
@modalWidth: 25vw;

.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
}

.modal-wrapper {
  position: fixed;
  top: 20px;
  right: 20px;
  width: @modalWidth;
  z-index: 1000;
}
.drawer-content {
  display: flex;
  flex-direction: column;
  top: 20px;
  right: 20px;
  width: @modalWidth;
  height: calc(~"100vh - 40px");
  padding-bottom: 14px;
  z-index: 1001;
  border-radius: 12px;
  padding: 0 16px;
  background: #f6f7fa;
  // overflow: hidden;
  /deep/ .ivu-input {
    border: none;
    color: #333333;
    font-weight: 400;
  }
  /deep/ textarea {
    resize: none !important;
  }
  /deep/ .ivu-input:focus {
    box-shadow: none;
  }
  .modal-title {
    display: flex;
    align-items: center;
    font-size: 12px;
    font-weight: bold;
    color: #333;
    margin: 10px 0;
    .ai-icon {
      width: 25px;
      height: 25px;
      margin-right: 6px;
    }
  }
}

.modal-content {
  overflow-y: auto;
  flex: 1;
}
// 滚动条隐藏
.modal-content::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.close-button {
  position: absolute;
  top: 10px;
  right: 10px;
  cursor: pointer;
}

/* Animation */
.modal-fade-enter-active {
  transition: all 0.3s ease;
}

.modal-fade-enter-from,
.modal-fade-leave-to {
  opacity: 0;
  transform: translateY(-20px);
}

.modal-backdrop {
  transition: opacity 0.3s ease;
}

.modal-fade-enter-from .modal-backdrop,
.modal-fade-leave-to .modal-backdrop {
  opacity: 0;
}
.ai-module {
  /deep/ .ivu-poptip-body {
    padding: 16px;
  }
  .ai-trigger {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 32px;
    padding: 0 6px;
    color: #2d8cf0;
    cursor: pointer;
    background-color: #f7f9fa;
    font-size: 12px;
    border-radius: 4px;
    .ai-icon {
      width: 16px;
      height: 16px;
    }
    &:hover {
      color: #57a3f3;
    }
  }
}

.popover-content {
  overflow: hidden;
  .actions {
    display: flex;
    justify-content: end;
    margin-top: 10px;
    /deep/ .ivu-btn-primary,
    /deep/ .action-buttons {
      border: none !important;
      background: linear-gradient(136deg, #fa73f7 1%, #4d50fd 100%);
      box-shadow: none !important;
    }
    /deep/ .ivu-btn:focus {
      box-shadow: none !important;
      border: none !important;
    }
  }
  .modal-title {
    display: flex;
    align-items: center;
    font-size: 12px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
    .ai-icon {
      width: 25px;
      height: 25px;
      margin-right: 6px;
    }
  }
  .popover-actions {
    width: 80px;
    height: 32px;
    background: linear-gradient(136deg, #fa73f7 1%, #4d50fd 100%);
    border-radius: 4px;
    color: #fff;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 12px;
    // 右对齐
    float: right;
    cursor: pointer;
  }
}

.drawer-content {
  .input-section {
    padding: 10px;
    background-color: #fff;
    margin-bottom: 16px;
    border-radius: 6px;
    .action-buttons {
      border-top: 1px solid #eaecf0;
      padding-top: 12px;
      display: flex;
      align-items: center;
      justify-content: end;
      /deep/ .ivu-btn > .ivu-icon + span {
        margin-left: 0;
      }
      /deep/ .ivu-btn-primary {
        background: linear-gradient(136deg, #fa73f7 1%, #4d50fd 100%);
      }
      /deep/ .ivu-btn:focus {
        box-shadow: none;
        border: none !important;
      }
    }
  }

  .result-section {
    margin-bottom: 24px;

    .result-content {
      border-radius: 4px;
      padding: 12px;
      background: #fff;
      margin-bottom: 16px;
      .result-text {
        line-height: 1.6;
        margin-bottom: 12px;
        white-space: pre-wrap;
        color: #333333;
        font-weight: 400;
      }

      .result-actions {
        padding-top: 8px;
        display: flex;
        align-items: center;
        justify-content: end;
        color: #999999;
        .btn {
          line-height: 12px;
          cursor: pointer;
        }
      }
    }
  }
  .section-title {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    margin-bottom: 12px;
    color: #999999;
    width: 80%;
    margin: 0 auto 16px;
    .title {
      margin: 0 10px;
    }
    .line {
      flex: 1;
      height: 1px;
      background-color: #d8d8d8;
    }
  }
}
.drawer-mask {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 999;
}

@property --direc {
  syntax: "<angle>";
  initial-value: 132deg;
  inherits: false;
}
.card {
  z-index: -1;
}
.card::after,
.card::before {
  content: "";
  position: absolute;
  top: -1px;
  left: -1px;
  width: calc(~"100% + 2px");
  height: calc(~"100vh - 38px");
  background-image: linear-gradient(
    var(--direc),
    #d4d4ff,
    #fa73f7 43%,
    #4d50fd
  );
  animation: rotate 10s linear infinite;
  border-radius: 12px;
}

.card::before {
  filter: blur(5px);
}
@keyframes rotate {
  0% {
    --direc: 0deg;
  }
  100% {
    --direc: 360deg;
  }
}
</style>
