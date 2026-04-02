<template>
  <div>
    <!-- 商品信息-->
    <FormItem label="商品名称：" required :rules="ruleValidate.store_name">
      <div class="flex-y-center">
        <Input
          v-model="formValidate.store_name"
          placeholder="请输入商品名称"
          v-width="'50%'"
          :maxlength="$formConfig.fieldLength.name"
          show-word-limit
          @on-change="handleInput"
        />
        <AiModule
          class="ml-10"
          mode="drawer"
          trigger-text="AI润色"
          :default-content="formValidate.store_name"
          :api-params="{ type: 'product_name' }"
          @generated="handleGenerated"
          @fill="handleFill"
        />
      </div>
    </FormItem>
    <FormItem label="商品分类：" required :rules="ruleValidate.cate_id">
      <el-cascader
        placeholder="请选择商品分类"
        v-width="'50%'"
        size="mini"
        v-model="formValidate.cate_id"
        :options="treeSelect"
        :props="props"
        filterable
        clearable
      >
      </el-cascader>
      <span class="addClass" @click="addClass" v-show="!isSupplier"
        >新增分类</span
      >
    </FormItem>
    <FormItem label="商品品牌：" prop="">
      <div class="flex">
        <Cascader
          :data="brandData"
          placeholder="请选择商品品牌"
          change-on-select
          v-model="formValidate.brand_id"
          filterable
          v-width="'50%'"
        ></Cascader>
        <span class="addClass" @click="addBrand" v-show="!isSupplier"
          >新增品牌</span
        >
      </div>
    </FormItem>
    <FormItem label="单位：" required :rules="ruleValidate.unit_name">
      <Select
        v-model="formValidate.unit_name"
        clearable
        filterable
        v-width="'50%'"
        placeholder="请输入单位"
        @on-change="unitChange"
      >
        <Option
          v-for="(item, index) in unitNameList"
          :value="item.name"
          :key="index"
          >{{ item.name }}</Option
        >
      </Select>
      <span class="addClass" @click="addUnit" v-show="!isSupplier"
        >新增单位</span
      >
    </FormItem>
    <FormItem label="商品编码：" prop="">
      <Input
        v-model="formValidate.code"
        placeholder="请输入商品编码"
        v-width="'50%'"
      />
    </FormItem>
    <FormItem label="商品轮播图：" required :rules="ruleValidate.slider_image">
      <div class="acea-row">
        <div
          class="pictrue"
          v-for="(item, index) in formValidate.slider_image"
          :key="index"
          draggable="true"
          @dragstart="handleDragStart($event, item)"
          @dragover.prevent="handleDragOver($event, item)"
          @dragenter="handleDragEnter($event, item)"
          @dragend="handleDragEnd($event, item)"
        >
          <img :src="item" v-viewer />
          <Button
            shape="circle"
            icon="md-close"
            @click.native="handleRemove(index)"
            class="btndel"
          ></Button>
        </div>
        <div
          v-if="formValidate.slider_image.length < 9"
          class="upLoad acea-row row-center-wrapper"
          @click="modalPicTap('duo')"
        >
          <Icon type="ios-camera-outline" size="26" />
        </div>
        <Input
          v-model="formValidate.slider_image[0]"
          class="input-display"
        ></Input>
      </div>
      <div class="tips">
        建议尺寸：800 *
        800px，可拖拽改变图片顺序，默认首张图为主图，最多上传10张
      </div>
    </FormItem>
    <FormItem label="商品标签：" class="labelClass">
      <div class="acea-row row-middle">
        <div
          class="labelInput acea-row row-between-wrapper"
          @click="openStoreLabel"
        >
          <div style="width: 90%">
            <div v-if="formValidate.store_label_id.length">
              <Tag
                v-for="(item, index) in formValidate.store_label_id"
                :key="index"
                @on-close="closeStoreLabel(item)"
                >{{ item.label_name }}</Tag
              >
            </div>
            <span class="span" v-else>选择商品标签</span>
          </div>
          <div class="iconfont iconxiayi"></div>
        </div>
        <span class="addClass" @click="addStoreLabel" v-show="!isSupplier"
          >新增标签</span
        >
      </div>
    </FormItem>
    <FormItem label="添加视频：">
      <i-switch v-model="formValidate.video_open" size="large">
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </i-switch>
    </FormItem>
    <FormItem
      label="上传视频："
      prop="video_link"
      v-if="formValidate.video_open"
    >
      <div class="flex">
        <Input v-model="formValidate.video_link" v-width="'50%'" />
        <span class="addClass" @click="addVideo">选择视频</span>
      </div>
      <div class="iview-video-style" v-if="formValidate.video_link">
        <video
          class="video-style"
          :src="formValidate.video_link"
          controls="controls"
        ></video>
        <div class="mark"></div>
        <Icon type="ios-trash-outline" class="iconv" @click="delVideo" />
      </div>
    </FormItem>
    <FormItem label="适用群体：" v-show="!isSupplier">
      <CheckboxGroup v-model="formValidate.product_clear" class="checkAlls">
        <Checkbox label="is_general_product">零售用户</Checkbox>
        <Checkbox label="is_channel_product">采购商</Checkbox>
      </CheckboxGroup>
    </FormItem>
    <FormItem
      label="仅会员可见："
      v-if="formValidate.product_clear.includes('is_general_product')"
    >
      <i-switch
        v-model="formValidate.is_vip_product"
        :true-value="1"
        :false-value="0"
        size="large"
      >
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </i-switch>
      <div class="fs-12 text--w111-999">
        开启后仅付费会员可以看见并购买此商品
      </div>
    </FormItem>
    <FormItem label="上架时间：" v-show="!isSupplier">
      <RadioGroup v-model="formValidate.is_show" @on-change="goodsOn">
        <Radio :label="1">
          <Icon type="social-apple"></Icon>
          <span>立即上架</span>
        </Radio>
        <Radio :label="2">
          <Icon type="social-android"></Icon>
          <span>定时上架</span>
        </Radio>
        <Radio :label="0">
          <Icon type="social-windows"></Icon>
          <span>放入仓库</span>
        </Radio>
      </RadioGroup>
    </FormItem>
    <FormItem label="" v-if="formValidate.is_show == 2 && !isSupplier">
      <DatePicker
        type="datetime"
        @on-change="onchangeShow"
        :options="startPickOptions"
        :value="formValidate.auto_on_time"
        v-model="formValidate.auto_on_time"
        placeholder="请选择上架时间"
        format="yyyy-MM-dd HH:mm"
        style="width: 250px"
      ></DatePicker>
    </FormItem>
    <FormItem label="定时下架：" v-show="!isSupplier">
      <Switch
        v-model="formValidate.off_show"
        :true-value="1"
        :false-value="0"
        size="large"
        @on-change="goodsOff"
      >
        <span slot="open">开启</span>
        <span slot="close">关闭</span>
      </Switch>
    </FormItem>
    <FormItem label="" v-if="formValidate.off_show == 1 && !isSupplier">
      <DatePicker
        type="datetime"
        @on-change="onchangeOff"
        :options="endPickOptions"
        :value="formValidate.auto_off_time"
        v-model="formValidate.auto_off_time"
        placeholder="请选择下架时间"
        format="yyyy-MM-dd HH:mm"
        style="width: 260px"
      ></DatePicker>
      <div class="tips">
        开启定时下架后，系统会在设置时间下架该商品。下架时间需晚于开售时间，商品才能定时开售。
      </div>
    </FormItem>
    <FormItem
      label="商品来源："
      v-if="formValidate.product_type == 0 && !isSupplier"
    >
      <RadioGroup v-model="formValidate.type" @on-change="sourceChange">
        <Radio :label="0">
          <span>平台自采</span>
        </Radio>
        <Radio :label="2">
          <span>供应商</span>
        </Radio>
      </RadioGroup>
    </FormItem>
    <FormItem
      label="供应商："
      v-if="
        formValidate.type == 2 && formValidate.product_type == 0 && !isSupplier
      "
    >
      <Select
        v-model="formValidate.supplier_id"
        clearable
        @on-change="selectSupplier"
        v-width="'50%'"
      >
        <Option v-for="item in supplierList" :value="item.id" :key="item.id"
          >{{ item.supplier_name }}
        </Option>
      </Select>
    </FormItem>
    <!-- 商品标签 -->
    <Modal
      v-model="storeLabelShow"
      scrollable
      title="选择商品标签"
      :closable="true"
      width="540"
      :footer-hide="true"
      :mask-closable="false"
    >
      <storeLabelList
        ref="storeLabel"
        @activeData="activeStoreData"
        @close="storeLabelClose"
      ></storeLabelList>
    </Modal>
    <menus-from
      :formValidate="formBrand"
      :fromName="1"
      ref="menusFrom"
    ></menus-from>
  </div>
</template>
<script>
import {
  cascaderListApi,
  productCreateApi,
  brandList,
  productAllUnit,
  productUnitCreate,
  productLabelAdd,
} from "@/api/product";
import { getSupplierList } from "@/api/supplier";
import storeLabelList from "@/components/storeLabelList";
import menusFrom from "../../productBrand/components/menusFrom.vue";
import vuedraggable from "vuedraggable";
import EventBus from "@/utils/bus";
import AiModule from "@/components/aiModule";

export default {
  name: "productBaseSet",
  props: {
    baseInfo: {
      type: Object,
      default: () => {},
    },
    successData: {
      type: Boolean,
      default: false,
    },
    productType: {
      type: Number,
      default: 0,
    },
  },
  data() {
    return {
      formValidate: {
        id: 0,
        brand_id: [],
        code: "",
        slider_image: [],
        store_name: "",
        cate_id: [],
        store_label_id: [],
        unit_name: "",
        video_link: "",
        video_open: false,
        is_show: 0,
        auto_on_time: "",
        auto_off_time: "",
        off_show: 0,
        product_type: 0,
        product_clear: ["is_general_product"],
        is_vip_product: 0,
        supplier_id: "",
        type: 0,
      },
      props: { emitPath: false, multiple: true, checkStrictly: true },
      //商品分类树形数据
      treeSelect: [],
      // 品牌数据
      brandData: [],
      // 单位数据
      unitNameList: [],
      storeLabelShow: false,
      formBrand: {},
      ruleValidate: {
        store_name: [
          { required: true, message: "请输入商品名称", trigger: "blur" },
        ],
        cate_id: [
          {
            required: true,
            message: "请选择商品分类",
            trigger: "change",
            type: "array",
          },
        ],
        unit_name: [
          {
            required: true,
            message: "请输入单位",
            trigger: "change",
          },
        ],
        slider_image: [
          {
            required: true,
            message: "请上传商品轮播图",
            type: "array",
          },
        ],
      },
      isSupplier: Number(localStorage.getItem("isSupplier")) || false,
      supplierList: [],
    };
  },
  components: {
    storeLabelList,
    menusFrom,
    draggable: vuedraggable,
    AiModule,
  },
  computed: {
    startPickOptions() {
      const that = this;
      return {
        disabledDate(time) {
          if (that.formValidate.auto_off_time) {
            return (
              time.getTime() >
              new Date(that.formValidate.auto_off_time).getTime()
            );
          }
          return "";
        },
      };
    },
    endPickOptions() {
      const that = this;
      return {
        disabledDate(time) {
          if (that.formValidate.is_show == "1") {
            return time.getTime() < Date.now();
          }
          if (that.formValidate.auto_on_time) {
            return (
              time.getTime() <
              new Date(that.formValidate.auto_on_time).getTime()
            );
          }
          return "";
        },
      };
    },
  },
  watch: {
    successData: {
      handler(val) {
        if (val) {
          let keys = Object.keys(this.formValidate);
          keys.map((i) => {
            this.formValidate[i] = this.baseInfo[i];
          });
        }
      },
      immediate: true,
      deep: true,
    },
    productType: {
      handler(val) {
        this.formValidate.product_type = val;
      },
      immediate: true,
      deep: true,
    },
  },
  created() {
    this.goodsCategory();
    this.getBrandList();
    this.getAllUnit();
    this.getSupplier();
  },
  methods: {
    handleGenerated(val) {
      this.formValidate.store_name = val;
    },
    handleFill(val) {
      this.formValidate.store_name = val;
      this.$emit("onChanegStoreName", val);
    },
    // 品牌列表
    getBrandList() {
      brandList()
        .then((res) => {
          //initBran()函数作用iview中规定value必须是字符串，后台返回成了数字，用于处理这个，给了个递归；
          this.initBran(res.data);
          this.brandData = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    //获取供应商列表；
    getSupplier() {
      getSupplierList()
        .then(async (res) => {
          this.supplierList = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    initBran(data) {
      data.map((item) => {
        item.value = item.value.toString();
        if (item.children && item.children.length) {
          this.initBran(item.children);
        }
      });
    },
    addBrand() {
      this.$refs.menusFrom.modals = true;
      this.$refs.menusFrom.titleFrom = "添加品牌分类";
      this.formBrand = {
        sort: 0,
        is_show: 1,
      };
      this.formBrand.fid = [0];
      this.$refs.menusFrom.type = 1;
    },
    // 商品分类；
    goodsCategory() {
      cascaderListApi(1)
        .then((res) => {
          this.treeSelect = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    addClass() {
      this.$modalForm(productCreateApi()).then(() => this.goodsCategory());
    },
    getAllUnit() {
      productAllUnit()
        .then((res) => {
          this.unitNameList = res.data;
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    addUnit() {
      this.$modalForm(productUnitCreate()).then(() => this.getAllUnit());
    },
    addStoreLabel() {
      this.$modalForm(productLabelAdd()).then(() => {});
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      this.formValidate.store_label_id = storeDataLabel;
    },
    openStoreLabel(row) {
      this.storeLabelShow = true;
      this.$refs.storeLabel.storeLabel(
        JSON.parse(JSON.stringify(this.formValidate.store_label_id))
      );
    },
    // 标签弹窗关闭
    storeLabelClose() {
      this.storeLabelShow = false;
    },
    goodsOn(e) {
      if (e == 0 || e == 1) {
        this.formValidate.auto_on_time = "";
      }
    },
    goodsOff(e) {
      if (!e) {
        this.formValidate.auto_off_time = "";
      }
    },
    //定时上架
    onchangeShow(e) {
      this.formValidate.auto_on_time = e;
    },
    //定时下架
    onchangeOff(e) {
      this.formValidate.auto_off_time = e;
    },
    // 删除视频；
    delVideo() {
      this.$set(this.formValidate, "video_link", "");
    },
    // 移动
    handleDragStart(e, item) {
      this.dragging = item;
    },
    handleDragEnd(e, item) {
      this.dragging = null;
    },
    handleDragOver(e) {
      e.dataTransfer.dropEffect = "move";
    },
    handleDragEnter(e, item) {
      e.dataTransfer.effectAllowed = "move";
      if (item === this.dragging) {
        return;
      }
      const newItems = [...this.formValidate.slider_image];
      const src = newItems.indexOf(this.dragging);
      const dst = newItems.indexOf(item);
      newItems.splice(dst, 0, ...newItems.splice(src, 1));
      this.formValidate.slider_image = newItems;
    },
    handleRemove(i) {
      this.formValidate.slider_image.splice(i, 1);
    },
    modalPicTap() {
      this.$imgModal((e) => {
        e.forEach((item) => {
          this.formValidate.slider_image.push(item.att_dir);
          this.formValidate.slider_image =
            this.formValidate.slider_image.splice(0, 9);
          this.$emit("swiperImageBus", this.formValidate.slider_image);
        });
      }, true);
    },
    addVideo() {
      this.$imgModal((e) => {
        let videoUrl = e[0].att_dir;
        if (videoUrl.includes("mp4")) {
          this.formValidate.video_link = videoUrl;
        } else {
          this.$Message.error("请选择正确的视频文件");
        }
      });
    },
    unitChange(val) {
      EventBus.$emit("unitChanged", val);
    },
    handleInput() {
      this.$emit("onChanegStoreName", this.formValidate.store_name);
    },
    sourceChange(val) {
      this.formValidate.supplier_id = "";
      this.$emit("supplierChanged", val);
    },
    selectSupplier(val) {
      this.formValidate.supplier_id = val || "";
    },
  },
};
</script>
<style scoped lang="less">
.addClass {
  color: #1890ff;
  margin-left: 14px;
  cursor: pointer;
}
.input-display {
  display: none;
}
.labelInput {
  border: 1px solid #dcdee2;
  width: 50%;
  padding: 0 5px;
  border-radius: 5px;
  min-height: 30px;
  cursor: pointer;
  .span {
    color: #c5c8ce;
  }
  .iconxiayi {
    font-size: 12px;
  }
}
.labelClass {
  /deep/.ivu-form-item-content {
    line-height: unset;
  }
}
.iview-video-style {
  width: 40%;
  height: 180px;
  border-radius: 10px;
  background-color: #707070;
  margin-top: 10px;
  position: relative;
  overflow: hidden;
}

.iview-video-style .iconv {
  color: #fff;
  line-height: 180px;
  width: 50px;
  height: 50px;
  display: inherit;
  font-size: 26px;
  position: absolute;
  top: -74px;
  left: 50%;
  margin-left: -25px;
  cursor: pointer;
}

.iview-video-style .mark {
  position: absolute;
  width: 100%;
  height: 30px;
  top: 0;
  background-color: rgba(0, 0, 0, 0.5);
  text-align: center;
}
.video-style {
  width: 100%;
  height: 100% !important;
  border-radius: 10px;
}
.tips {
  display: inline-bolck;
  font-size: 12px;
  font-weight: 400;
  color: #999999;
  margin-top: 6px;
}
</style>
