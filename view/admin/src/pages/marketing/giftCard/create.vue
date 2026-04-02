<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/marketing/giftCard/index' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span
            v-text="$route.params.id ? '编辑礼品卡' : '添加礼品卡'"
            class="mr20 ml16"
          ></span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt mb79">
      <div class="new_tab">
        <Tabs v-model="currentTab">
          <TabPane
            v-for="(item, index) in headTab"
            :key="index"
            :label="item.name"
            :name="item.type"
          ></TabPane>
        </Tabs>
      </div>
      <Form
        class="formValidate"
        ref="formValidate"
        :rules="ruleValidate"
        :model="formValidate"
        :label-width="labelWidth"
        label-position="right"
        @submit.native.prevent
      >
        <div v-show="currentTab == 1">
          <FormItem label="礼品卡类型:" props="type">
            <div
              class="productType"
              :class="formValidate.type == item.id ? 'on' : ''"
              v-for="(item, index) in cardType"
              :key="index"
              @click="cardTypeTap(item)"
            >
              <div
                class="name"
                :class="formValidate.type == item.id ? 'on' : ''"
              >
                {{ item.name }}
              </div>
              <div class="title">({{ item.title }})</div>
              <div v-if="formValidate.type == item.id" class="jiao"></div>
              <div
                v-if="formValidate.type == item.id"
                class="iconfont iconduihao"
              ></div>
            </div>
          </FormItem>
          <FormItem label="礼品卡名称:" prop="name">
            <Input
              v-model="formValidate.name"
              class="w-460"
              placeholder="请输入礼品卡名称"
            ></Input>
          </FormItem>
          <!-- 关联卡号 -->
          <FormItem label="关联卡号:" prop="batch_id">
            <Select
              v-model="formValidate.batch_id"
              placeholder="请选择卡号批次"
              :disabled="isEdit"
              @on-change="cardCodeChange"
              class="w-460"
            >
              <Option
                v-for="(item, index) in batchOptions"
                :key="index"
                :value="item.id"
                >{{ item.name }}(剩余{{ item.remain_num }}条)</Option
              >
            </Select>
          </FormItem>
          <!-- 总数量 -->
          <FormItem label="生成数量:" prop="total_num">
            <InputNumber
              :min="1"
              :max="maxCount"
              :step="1"
              controls-position="right"
              v-model="formValidate.total_num"
              :disabled="isEdit"
              class="w-460"
              placeholder="生成礼品卡数量"
            ></InputNumber>
          </FormItem>
          <!-- 使用须知 -->
          <FormItem label="使用须知:" prop="instructions">
            <Input
              v-model="formValidate.instructions"
              type="textarea"
              :autosize="{ minRows: 2, maxRows: 6 }"
              class="w-460"
              placeholder="请输入使用说明"
            ></Input>
          </FormItem>
          <!-- 排序 -->
          <FormItem label="排序:">
            <Input
              v-model="formValidate.sort"
              class="w-460"
              placeholder="请输入排序"
            ></Input>
          </FormItem>
          <FormItem label="封面图:" prop="cover_image">
            <div class="acea-row">
              <div v-if="formValidate.cover_image" class="pictrue">
                <img v-lazy="formValidate.cover_image" />
                <Button
                  shape="circle"
                  icon="md-close"
                  @click.native="formValidate.cover_image = ''"
                  class="btndel"
                ></Button>
              </div>
              <div
                v-else
                class="upLoad acea-row row-center-wrapper"
                @click="modalPicTap()"
              >
                <Icon type="ios-camera-outline" size="26" />
              </div>
            </div>
            <div class="text--w111-999">建议尺寸：710*400</div>
          </FormItem>
          <!-- 有效期 -->
          <FormItem label="有效期:" prop="valid_type">
            <RadioGroup
              v-model="formValidate.valid_type"
              @on-change="validTypeChange"
            >
              <Radio :label="1">永久有效</Radio>
              <Radio :label="2">固定时间</Radio>
            </RadioGroup>
          </FormItem>
          <div v-if="formValidate.valid_type == 2">
            <FormItem label="起止时间:">
              <DatePicker
                :editable="false"
                :clearable="true"
                @on-change="fixedChangeTime"
                :value="fixed_time"
                format="yyyy/MM/dd HH:mm:ss"
                type="datetimerange"
                placement="bottom-start"
                placeholder="自定义时间"
                class="w-460"
              ></DatePicker>
            </FormItem>
          </div>
          <!-- 礼品卡状态 -->
          <FormItem label="礼品卡状态:" prop="status">
            <i-switch
              v-model="formValidate.status"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
        </div>
        <div v-show="currentTab == 2">
          <wangeditor
            class="w-full"
            :content="description"
            @editorContent="getEditorContent"
          ></wangeditor>
        </div>
        <div v-show="currentTab == 3">
          <!-- 储值金额 -->
          <FormItem
            label="储值金额："
            prop="balance"
            v-if="formValidate.type == 1"
          >
            <InputNumber
              v-model="formValidate.balance"
              :min="0"
              :max="999999"
              :step="1"
              controls-position="right"
              :placeholder="'请输入储值金额'"
              :style="{ width: '460px' }"
            ></InputNumber>
          </FormItem>
          <div v-else>
            <!-- 兑换商品类型 -->
            <FormItem label="兑换商品类型:">
              <RadioGroup
                v-model="formValidate.exchange_type"
                @on-change="changeType"
              >
                <Radio :label="1">固定商品打包</Radio>
                <Radio :label="2">任选N件商品</Radio>
              </RadioGroup>
              <div class="text--w111-999">
                当商品修改后，已激活礼品卡的用户不受影响（以当时下单为准）
              </div>
            </FormItem>
            <FormItem
              label="兑换商品数量:"
              v-if="formValidate.exchange_type == 2 && tableData.length"
            >
              <InputNumber
                v-model="formValidate.gift_num"
                :min="1"
                :max="100"
                :step="1"
                controls-position="right"
                :placeholder="'请输入兑换商品数量'"
                class="w-460"
              ></InputNumber>
            </FormItem>
            <FormItem label="选择商品:">
              <div class="flex-y-center">
                <Button @click="checkProduct">选择商品</Button>
                <span class="pl-14">已选 {{ tableData.length || 0 }} 个</span>
              </div>
              <Table
                :columns="columns"
                :data="tableData"
                class="ivu-mt"
                v-show="tableData.length > 0"
              >
                <template slot-scope="{ row, index }" slot="goodInfo">
                  <div class="imgPic acea-row">
                    <viewer>
                      <div class="w-40 h-40">
                        <img class="w-full h-full block" v-lazy="row.image" />
                      </div>
                    </viewer>
                    <div class="flex-1">
                      <Tooltip max-width="200" placement="bottom" transfer>
                        <div class="w-full line2 lh-20px pl-14">
                          {{ row.store_name.store_name || row.suk }}
                        </div>
                        <p slot="content">
                          {{ row.store_name.store_name || row.suk }}
                        </p>
                      </Tooltip>
                    </div>
                  </div>
                </template>
                <template slot-scope="{ row, index }" slot="limit_num">
                  <InputNumber
                    v-model="row.limit_num"
                    :min="1"
                    :max="999999"
                    :step="1"
                    controls-position="right"
                    :placeholder="'请输入数量'"
                    @on-change="limitChange($event, index)"
                    class="w-140"
                  ></InputNumber>
                </template>
                <template slot-scope="{ row, index }" slot="action">
                  <a @click="del(index)">删除</a>
                </template>
              </Table>
            </FormItem>
          </div>
        </div>
      </Form>
    </Card>
    <Card :bordered="false" dis-hover class="fixed-card">
      <div class="flex-center">
        <Button v-if="currentTab != 1" @click="upTab" style="margin-right: 10px"
          >上一步</Button
        >
        <Button
          type="primary"
          class="submission"
          v-if="currentTab != 3"
          @click="downTab('formValidate')"
          >下一步</Button
        >
        <Button
          v-else
          type="primary"
          class="submission"
          @click="handleSubmit('formValidate')"
          >保存并发布</Button
        >
      </div>
    </Card>
    <Modal
      v-model="modals"
      title="商品列表"
      footerHide
      class="paymentFooter"
      scrollable
      width="900"
      @on-cancel="cancel"
    >
      <goods-attr
        ref="goodSattr"
        product_type="0"
        :default-selected="defaultSelected"
        @getProductId="getProductId"
      ></goods-attr>
    </Modal>
  </div>
</template>
<script>
import { FormItem, InputNumber } from "view-design";
import { mapState, mapMutations } from "vuex";
import wangeditor from "@/components/wangEditor/index.vue";
import goodsAttr from "@/components/goodsAttr";
import {
  cardBatchOptionApiApi,
  cardGiftCreateApi,
  cardGiftInfoApi,
  cardGiftUpdateApi,
} from "@/api/marketing";
export default {
  name: "createGiftCard",
  data() {
    return {
      currentTab: "1",
      headTab: [
        { name: "基础设置", type: "1" },
        { name: "礼品卡详情", type: "2" },
        { name: "礼品卡权益", type: "3" },
      ],
      formValidate: {
        id: "", //自增ID
        name: "", //礼品卡名称
        type: 1, //礼品卡类型 1-储值卡 2-兑换卡
        batch_id: "", //关联卡密
        total_num: 1, //总数量
        instructions: "", //使用说明
        cover_image: "", //封面图
        valid_type: 1, //有效期类型 1-永久有效 2-固定时间
        status: 1, //礼品卡状态 0-禁用 1-启用
        balance: 0, //储值金额
        exchange_type: 1, //'兑换商品类型 1-固定商品打包 2-任选N件商品'
        gift_num: 1, //兑换商品数量
        description: "",
        // product: [], //兑换商品ID
      },
      description: "",
      defaultSelected: [],
      cardType: [
        { name: "储值卡", title: "充值余额", id: 1 },
        { name: "兑换卡", title: "兑换商品", id: 2 },
      ],
      ruleValidate: {
        name: [
          {
            required: true,
            message: "请输入礼品卡名称",
            trigger: "blur",
          },
        ],
        batch_id: [
          {
            required: true,
            message: "请输入关联卡密",
            trigger: "change",
            validator: (rule, value, callback) => {
              if (value === null || value === undefined || value === "") {
                callback(new Error("请输入关联卡密"));
              } else {
                callback();
              }
            },
          },
        ],
        total_num: [
          {
            required: true,
            message: "请输入礼品卡数量",
            trigger: "blur",
            validator: (rule, value, callback) => {
              if (value === null || value === undefined || value === "") {
                callback(new Error("请输入礼品卡数量"));
              } else {
                callback();
              }
            },
          },
        ],
        cover_image: [
          {
            required: true,
            message: "请上传封面图",
          },
        ],
        instructions: [
          {
            required: true,
            message: "请输入使用说明",
            trigger: "blur",
          },
        ],
      },
      tableData: [],
      columns: [
        { title: "商品信息", slot: "goodInfo", align: "left", minWidth: 300 },
        { title: "价格", key: "price", align: "left", minWidth: 80 },
        { title: "数量", slot: "limit_num", align: "left", minWidth: 80 },
        { title: "库存", key: "stock", align: "left", minWidth: 80 },
        { title: "操作", slot: "action", align: "center", minWidth: 50 },
      ],
      modals: false,
      indexGoods: 0,
      batchOptions: [],
      maxCount: 1,
      fixed_time: "",
      isEdit: false,
    };
  },
  components: {
    goodsAttr,
    wangeditor,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile", "menuCollapse"]),
    labelWidth() {
      return this.isMobile ? undefined : 96;
    },
  },
  created() {
    this.getBatchOptions();
    if (this.$route.params.id) {
      this.isEdit = true;
      this.getInfo();
    }
  },
  methods: {
    changeType(val) {
      if (val == 2) {
        this.columns.splice(2, 1);
      } else {
       this.columns.splice(2, 0, {
          title: "数量",
          slot: "limit_num",
          align: "left",
          minWidth: 80,
        });
      }
    },
    validTypeChange(val) {
      if (val == 2) {
        this.fixed_time = [];
      }
    },
    fixedChangeTime(val) {
      this.formValidate.fixed_time = val;
    },
    cardTypeTap(type) {
      if (this.isEdit) return this.$Message.error("编辑状态下不允许修改类型");
      this.formValidate.type = type.id;
    },
    modalPicTap() {
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.formValidate.cover_image = imgUrl;
        }
      });
    },
    checkProduct() {
      this.modals = true;
    },
    cancel() {
      this.modals = false;
    },
    getProductId(data) {
      try {
        data.map((item) => {
          this.$set(item, "limit_num", 1);
        });
        // 过滤已存在的商品,只添加未存在的新商品
        let newData = data.filter((newItem) => {
          return !this.tableData.some(
            (existingItem) => existingItem.unique === newItem.unique
          );
        });
        // 少了哪些商品，进行删除
        let delData = this.tableData.filter((existingItem) => {
          return !data.some(
            (newItem) => newItem.unique === existingItem.unique
          );
        });
        if (delData.length) {
          this.tableData = this.tableData.filter((existingItem) => {
            return !delData.some(
              (newItem) => newItem.unique === existingItem.unique
            );
          });
        }

        if (newData.length) {
          this.tableData = [...this.tableData, ...newData];
        }
        this.formValidate.gift_num = this.tableData.length;
        this.modals = false;
      } catch (error) {
        console.log(error);
      }

      // let list = this.tableData.concat(data);
      // let uni = this.unique(list);
      // this.tableData = uni;
    },
    //对象数组去重；
    unique(arr) {
      const res = new Map();
      return arr.filter((arr) => !res.has(arr.id) && res.set(arr.id, 1));
    },
    del(index) {
      this.tableData.splice(index, 1);
    },
    getBatchOptions() {
      cardBatchOptionApiApi().then((res) => {
        this.batchOptions = res.data;
      });
    },
    cardCodeChange(val) {
      let index = this.batchOptions.findIndex((item) => item.id == val);
      this.maxCount = this.batchOptions[index].remain_num;
    },
    upTab() {
      this.currentTab = (Number(this.currentTab) - 1).toString();
    },
    downTab() {
      this.currentTab = (Number(this.currentTab) + 1).toString();
    },
    handleSubmit(name) {
      this.$refs[name].validate((valid) => {
        if (valid) {
          if (this.tableData.length) {
            this.formValidate.product = this.tableData.map((item) => {
              return {
                product_id: item.product_id,
                limit_num: item.limit_num || "",
                unique: item.unique,
              };
            });
          }
          if (this.isEdit) {
            cardGiftUpdateApi(this.$route.params.id, this.formValidate).then(
              (res) => {
                this.$Message.success(res.msg);
                this.$router.push({ path: "/admin/marketing/giftCard/index" });
              }
            );
          } else {
            cardGiftCreateApi(this.formValidate)
              .then((res) => {
                this.$Message.success(res.msg);
                this.$router.push({ path: "/admin/marketing/giftCard/index" });
              })
              .catch((err) => {
                this.$Message.error(err.msg);
              });
          }
        }
      });
    },
    getInfo() {
      cardGiftInfoApi(this.$route.params.id)
        .then((res) => {
          this.formValidate = res.data;
          this.formValidate.balance = Number(res.data.balance) || 0;
          this.fixed_time = res.data.fixed_time;
          this.tableData = res.data.product || [];
          this.formValidate.description = res.data.description || "";
          this.description = res.data.description || "";
          if(this.formValidate.exchange_type == 2){
            this.columns.splice(2, 1);
          }
          this.defaultSelected = res.data.product.map((item) => {
            return item.unique;
          });
          // this.formValidate.product = [];
          // this.formValidate.product.forEach(item => {
          //   item.count = item.limit_num;
          // })
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getEditorContent(data) {
      this.formValidate.description = data;
    },
    limitChange(e, index) {
      this.tableData[index].limit_num = e;
    },
  },
};
</script>
<style lang="less" scoped>
.new_tab {
  /deep/ .ivu-tabs-nav .ivu-tabs-tab {
    padding: 4px 16px 20px !important;
    font-weight: 500;
  }
}
.productType {
  width: 150px;
  height: 60px;
  background: #ffffff;
  border-radius: 3px;
  border: 1px solid #e7e7e7;
  float: left;
  text-align: center;
  padding-top: 8px;
  position: relative;
  cursor: pointer;
  line-height: 23px;
  margin-right: 12px;

  &.on {
    border-color: #1890ff;
  }

  .name {
    font-size: 14px;
    font-weight: 600;
    color: rgba(0, 0, 0, 0.85);
    &.on {
      color: #1890ff;
    }
  }

  .title {
    font-size: 12px;
    font-weight: 400;
    color: #999999;
  }

  .jiao {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 0;
    height: 0;
    border-bottom: 26px solid #1890ff;
    border-left: 26px solid transparent;
  }

  .iconfont {
    position: absolute;
    bottom: -3px;
    right: 1px;
    color: #ffffff;
    font-size: 12px;
  }
}
.w-460 {
  width: 460px;
}
.lh-20px {
  line-height: 20px;
}
.fixed-card {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 250px;
  z-index: 99;
  box-shadow: 0 -1px 2px rgb(240, 240, 240);

  /deep/ .ivu-card-body {
    padding: 15px 16px 14px;
  }

  .ivu-form-item {
    margin-bottom: 12px !important;
  }

  /deep/ .ivu-form-item-content {
    margin-right: 124px;
    text-align: center;
  }
}
</style>
