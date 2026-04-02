<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div slot="title">
          <router-link :to="{ path: '/admin/user/label' }">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </router-link>
          <span
            v-text="$route.params.id ? '编辑标签' : '添加标签'"
            class="mr20 ml16"
          ></span>
        </div>
      </PageHeader>
    </div>
    <Card :bordered="false" dis-hover class="ivu-mt mb79">
      <Form
        class="formValidate"
        ref="formValidate"
        :rules="ruleValidate"
        :model="formValidate"
        :label-width="95"
        label-position="right"
        @submit.native.prevent
      >
        <FormItem label="标签名称:" prop="label_name">
          <el-select
            v-model="formValidate.label_name"
            size="small"
            multiple
            filterable
            allow-create
            default-first-option
            placeholder="请输入标签名称并按回车确定"
            :popper-append-to-body="false"
            class="select-down-none"
            @focus="labelFocus = true"
            @change="labelFocus = false"
            v-if="!formValidate.id"
          >
          </el-select>
          <Input
            type="text"
            v-model="formValidate.label_name"
            v-else
            class="w-300"
          ></Input>
          <div class="w-300" v-show="labelFocus">
            <Alert class="mt-10" type="warning" closable>请输入标签名称并按回车确定</Alert>
          </div>
        </FormItem>
        <!-- 关联标签组 -->
        <FormItem label="关联标签组:">
          <Select
            v-model="formValidate.label_cate"
            placeholder="请选择标签组"
            clearable
            class="w-300"
          >
            <Option
              v-for="(item, index) in labelGroupOptions"
              :key="index"
              :value="item.id"
              >{{ item.name }}</Option
            >
          </Select>
        </FormItem>
        <!-- 打标方式 -->
        <FormItem label="打标方式:">
          <RadioGroup
            v-model="formValidate.label_type"
            @on-change="validTypeChange"
          >
            <Radio :label="1">手动打标</Radio>
            <Radio :label="2">自动打标</Radio>
          </RadioGroup>
        </FormItem>
        <div v-if="formValidate.label_type == 2">
          <FormItem label="商品条件:">
            <i-switch
              v-model="formValidate.is_product"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
          <div v-if="formValidate.is_product == 1">
            <FormItem label="购买商品:">
              <RadioGroup
                v-model="formValidate.product.specify_dimension"
                @on-change="productTypeChange"
              >
                <Radio :label="1">指定商品</Radio>
                <Radio :label="2">指定分类</Radio>
                <Radio :label="3">指定商品标签</Radio>
              </RadioGroup>
            </FormItem>
            <FormItem v-if="formValidate.product.specify_dimension == 1">
              <div class="acea-row">
                <div
                  class="pictrue"
                  v-for="(item, index) in selectProducts"
                  :key="index"
                >
                  <img v-lazy="item.image" />
                  <Button
                    shape="circle"
                    icon="md-close"
                    @click.native="bindDelete(index)"
                    class="btndel"
                  ></Button>
                </div>
                <div
                  class="upLoad acea-row row-center-wrapper"
                  @click="goodsTap"
                >
                  <Icon type="ios-camera-outline" size="26" />
                </div>
              </div>
            </FormItem>
            <FormItem
              label="商品分类:"
              v-if="formValidate.product.specify_dimension == 2"
            >
              <el-cascader
                placeholder="请选择商品分类"
                class="w-300"
                size="mini"
                v-model="formValidate.product.ids"
                :options="productCateData"
                :props="{
                  emitPath: false,
                  multiple: true,
                  checkStrictly: true,
                }"
                filterable
                clearable
              >
              </el-cascader>
            </FormItem>
            <FormItem
              label="商品标签:"
              class="labelClass"
              v-if="formValidate.product.specify_dimension == 3"
            >
              <div class="acea-row row-middle">
                <div
                  class="labelInput acea-row row-between-wrapper"
                  @click="openStoreLabel"
                >
                  <div style="width: 90%">
                    <div v-if="storeLabelActiveData.length">
                      <Tag
                        v-for="(item, index) in storeLabelActiveData"
                        :key="index"
                        @on-close="closeStoreLabel(item)"
                        >{{ item.label_name }}</Tag
                      >
                    </div>
                    <span class="span" v-else>选择商品标签</span>
                  </div>
                  <div class="iconfont iconxiayi"></div>
                </div>
              </div>
            </FormItem>
          </div>
          <FormItem label="资产条件:">
            <i-switch
              v-model="formValidate.is_property"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
          <div v-if="formValidate.is_property == 1">
            <div class="gray-box">
              <div
                class="flex-y-center rule-cell"
                v-for="(item, index) in formValidate.property.property_rule"
                :key="index"
              >
                <Select
                  v-model="item.sub_type"
                  placeholder="请选择"
                  @on-change="propertyTypeChange($event, index)"
                  class="w-120"
                >
                  <Option :value="2">余额</Option>
                  <Option :value="1">积分</Option>
                </Select>
                <Input value="累计" readonly class="w-120 ml-10" />
                <Select
                  v-model="item.balance_type"
                  placeholder="请选择余额维度"
                  class="w-120 ml-10"
                >
                  <Option :value="1" v-show="item.sub_type == 2">充值</Option>
                  <Option :value="2">消耗</Option>
                </Select>
                <div class="flex-y-center">
                  <Select
                    v-model="item.operation_type"
                    :placeholder="
                      item.balance_type == 1
                        ? '请选择充值类型'
                        : '请选择消耗类型'
                    "
                    class="w-120 ml-10"
                  >
                    <Option :value="1" v-show="item.sub_type == 2">次数</Option>
                    <Option :value="2">{{ item.sub_type == 1 ? "数量" : "金额"}}</Option>
                  </Select>
                  <div
                    class="flex-y-center"
                    v-if="item.operation_type == 1 && item.sub_type == 2"
                  >
                    <InputNumber
                      v-model="item.operation_times_min"
                      :min="0"
                      :max="999"
                      :step="1"
                      :precision="0"
                      class="w-120 ml-10"
                      placeholder="请填写/次"
                    />
                    <span class="px-6">-</span>
                    <InputNumber
                      v-model="item.operation_times_max"
                      :min="0"
                      :max="999"
                      :step="1"
                      :precision="0"
                      class="w-120"
                      placeholder="请填写/次"
                    />
                  </div>
                  <div class="flex-y-center" v-else>
                    <InputNumber
                      v-model="item.amount_value_min"
                      :min="0"
                      :max="9999999"
                      class="w-120 ml-10"
                      placeholder="请填写/元"
                    />
                    <span class="px-6">-</span>
                    <InputNumber
                      v-model="item.amount_value_max"
                      :min="0"
                      :max="9999999"
                      class="w-120"
                      placeholder="请填写/元"
                    />
                  </div>
                </div>
                <span
                  class="iconfont iconshanchu2 text--w111-999 pointer pl-10"
                  @click="delCell(index, 'property')"
                ></span>
              </div>
              <div
                class="flex-y-center add-btn pointer"
                @click="addPropertyRule"
              >
                <Icon type="ios-add-circle-outline" />
                <span class="pl-2">添加条件</span>
              </div>
            </div>
          </div>
          <FormItem label="交易条件:">
            <i-switch
              v-model="formValidate.is_trade"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
          <div class="gray-box" v-if="formValidate.is_trade">
            <div
              class="flex-y-center rule-cell"
              v-for="(item, index) in formValidate.trade.trade_rule"
              :key="index"
            >
              <Input value="累计" readonly class="w-120" />
              <div class="flex-y-center">
                <Select
                  v-model="item.operation_type"
                  placeholder="请选择消费类型"
                  class="w-120 ml-10"
                >
                  <Option :value="1">消费次数</Option>
                  <Option :value="2">消费金额</Option>
                </Select>
                <div class="flex-y-center" v-if="item.operation_type == 1">
                  <InputNumber
                    v-model="item.operation_times_min"
                    :min="0"
                    :max="999"
                    :step="1"
                    :precision="0"
                    class="w-120 ml-10"
                    placeholder="请填写/次"
                  />
                  <span class="px-6">-</span>
                  <InputNumber
                    v-model="item.operation_times_max"
                    :min="0"
                    :max="999"
                    :step="1"
                    :precision="0"
                    class="w-120"
                    placeholder="请填写/次"
                  />
                </div>
                <div class="flex-y-center" v-else>
                  <InputNumber
                    v-model="item.amount_value_min"
                    :min="0"
                    :max="9999999"
                    class="w-120 ml-10"
                    placeholder="请填写/元"
                  />
                  <span class="px-6">-</span>
                  <InputNumber
                    v-model="item.amount_value_max"
                    :min="0"
                    :max="9999999"
                    class="w-120"
                    placeholder="请填写/元"
                  />
                </div>
              </div>
              <span
                class="iconfont iconshanchu2 text--w111-999 pointer pl-10"
                @click="delCell(index, 'trade')"
              ></span>
            </div>
            <div class="flex-y-center add-btn pointer" @click="addTradeRule">
              <Icon type="ios-add-circle-outline" />
              <span class="pl-2">添加条件</span>
            </div>
          </div>
          <FormItem label="客户条件:">
            <i-switch
              v-model="formValidate.is_customer"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
          <div class="gray-box" v-if="formValidate.is_customer">
            <div
              class="flex-y-center rule-cell"
              v-for="(item, index) in formValidate.customer.customer_rule"
              :key="index"
            >
              <Select
                v-model="item.customer_identity"
                placeholder="请选择消费类型"
                class="w-120"
              >
                <Option :value="1">注册时间</Option>
                <Option :value="2">访问时间</Option>
                <Option :value="3">用户等级</Option>
                <Option :value="4">客户身份</Option>
              </Select>
              <DatePicker
                :editable="false"
                @on-change="createTime($event, index)"
                v-model="item.time_val"
                format="yyyy/MM/dd"
                type="datetimerange"
                placement="bottom-start"
                placeholder="时间选择"
                :options="options"
                class="w-200 ml-10"
                v-if="
                  item.customer_identity == 1 || item.customer_identity == 2
                "
              ></DatePicker>
              <Select
                v-model="item.customer_num"
                clearable
                class="w-200 ml-10"
                v-else-if="item.customer_identity == 3"
              >
                <Option
                  :value="item.id"
                  v-for="(item, index) in levelList"
                  :key="index"
                  >{{ item.name }}</Option
                >
              </Select>
              <Select
                v-model="item.customer_num"
                clearable
                class="w-200 ml-10"
                v-else-if="item.customer_identity == 4"
              >
                <Option :value="1">等级会员</Option>
                <Option :value="2">付费会员</Option>
                <Option :value="3">推广员</Option>
                <Option :value="4">采购商</Option>
              </Select>
              <span
                class="iconfont iconshanchu2 text--w111-999 pointer pl-10"
                @click="delCell(index, 'customer')"
              ></span>
            </div>
            <div class="flex-y-center add-btn pointer" @click="addCustomerRule">
              <Icon type="ios-add-circle-outline" />
              <span class="pl-2">添加条件</span>
            </div>
          </div>
          <!-- 标签生效： -->
          <FormItem label="标签生效:">
            <RadioGroup v-model="formValidate.is_condition">
              <Radio :label="1">条件满足任一</Radio>
              <Radio :label="2">全部条件满足</Radio>
            </RadioGroup>
            <div class="text--w111-999 lh-16px">
              条件满足任一：上面的各类条件，满足任意一个即给客户打此标签
              <br />条件全部满足：上面的各类条件，需要全部满足后才能给客户打此标签
            </div>
          </FormItem>
        </div>
        <!-- <FormItem label="状态:">
          <i-switch
            v-model="formValidate.status"
            :true-value="1"
            :false-value="0"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </i-switch>
        </FormItem> -->
      </Form>
    </Card>
    <Card :bordered="false" dis-hover class="fixed-card">
      <div class="flex-center">
        <Button @click="cancel()">取消</Button>
        <Button type="primary" class="ml-14" @click="handleSubmit()"
          >确定</Button
        >
      </div>
    </Card>
    <!-- 商品列表 -->
    <Modal
      v-model="goodsModals"
      title="商品列表"
      footerHide
      scrollable
      width="900"
      @on-cancel="goodCancel"
    >
      <goods-list
        v-if="goodsModals"
        ref="goodslist"
        @getProductId="getProductId"
        @closeModal="()=>{goodsModals = false}"
        :ischeckbox="true"
      ></goods-list>
    </Modal>
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
  </div>
</template>

<script>
import { mapState } from "vuex";
import { userLabelAll } from "@/api/user";
import { FormItem } from "view-design";
import goodsList from "@/components/goodsList/index";
import storeLabelList from "@/components/storeLabelList";
import { cascaderListApi } from "@/api/product";
import timeOptions from "@/utils/timeOptions";
import { userLabelAddApi, userLabelInfoApi, levelListApi } from "@/api/user";
export default {
  name: "user_label",
  data() {
    return {
      options: timeOptions,
      formValidate: {
        id: "", //自增 ID
        label_name: [], //标签名称
        label_cate: "", //标签分组
        label_type: 1, //打标方式,1:手动;2 自动
        is_product: 0, //商品条件,1 开启 0 关闭
        is_property: 0, //资产条件,1 开启 0 关闭
        is_trade: 0, //交易条件,1 开启0 关闭
        is_customer: 0, //客户条件,1 开启 0 关闭
        is_condition: 1, //条件类型,1 条件满足任一,2全部条件满足
        //商品条件
        product: {
          time_dimension: 3, //时间维度,1 历史;2 最近;3 累计
          time_value: 0, //时间值（天）
          specify_dimension: 1, //商品维度,1 商品;2 分类;3 标签
          ids: [], //商品ID
        },
        //资产条件
        property: {
          property_rule: [
            {
              sub_type: 2, //规则子类型:1=>积分,2=>余额(资产)
              balance_type: 1, //余额类型:1=>充值,2=>消耗(资产)
              time_dimension: 3, //累计
              operation_type: 1, //充值类型:1=>充值次数,2=>充值金额
              amount_value_min: 0, //金额/数值最小
              amount_value_max: 0, //金额/数值大
              operation_times_min: 0, //操作次数最小
              operation_times_max: 0, //操作次数最大
            },
          ],
        },
        //交易条件
        trade: {
          trade_rule: [
            {
              time_dimension: 3, //累计
              operation_type: 1, //充值类型:1=>充值次数,2=>充值金额
              amount_value_min: 0, //金额/数值最小
              amount_value_max: 0, //金额/数值大
              operation_times_min: 0, //操作次数最小
              operation_times_max: 0, //操作次数最大
            },
          ],
        },
        //客户条件
        customer: {
          customer_rule: [
            {
              customer_identity: 1, //客户身份:1=>注册时间,2=>访问时间,3=>用户等级, 4=>客户身份
              customer_num: "", //客户身份数据(注册时间,访问时间,用户等级,用户身份(1=>等级会员,2=>付费会员,3=>推广员,4=>采购商))
              time_val: [],
            },
          ],
        }, //客户条件
        status: 1, //标签状态,1 启用;0 禁用
      },
      labelGroupOptions: [], //标签分组选项
      ruleValidate: {
        label_name: [{ required: true, message: "请输入标签名称" }],
      },
      goodsModals: false, //商品列表弹窗
      selectProducts: [], //选择的商品
      productCateData: [], //商品分类数据
      storeLabelShow: false, //商品标签弹窗
      storeLabelActiveData: [], //商品标签选中数据
      timeVal: [], //时间选择
      levelList: [], //用户等级列表
      labelFocus: false,
    };
  },
  components: {
    goodsList,
    storeLabelList,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
  },
  created() {
    this.getUserLabelAll();
    this.goodsCategory();
    if (this.$route.params.id) {
      this.getInfo();
    }
  },
  methods: {
    getUserLabelAll() {
      userLabelAll().then((res) => {
        this.labelGroupOptions = res.data;
      });
    },
    validTypeChange(val) {},
    goodsTap() {
      this.goodsModals = true;
      this.$refs.goodslist.handleSelectAll();
    },
    goodCancel() {
      this.goodsModals = false;
    },
    getProductId(e) {
      this.goodsModals = false;
      let nArr = this.selectProducts
        .concat(e)
        .filter((element, index, self) => {
          return (
            self.findIndex((x) => x.product_id == element.product_id) == index
          );
        });
      this.selectProducts = nArr.slice(0, 12);
    },
    bindDelete(index) {
      this.selectProducts.splice(index, 1);
    },
    productTypeChange(val) {
      this.selectProducts = [];
      this.formValidate.product.ids = [];
      this.storeLabelActiveData = [];
    },
    // 商品分类；
    goodsCategory() {
      cascaderListApi(1)
        .then((res) => {
          this.productCateData = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
      levelListApi().then((res) => {
        this.levelList = res.data.list;
      });
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      this.storeLabelActiveData = storeDataLabel;
    },
    openStoreLabel(row) {
      this.storeLabelShow = true;
      this.$refs.storeLabel.storeLabel(
        JSON.parse(JSON.stringify(this.storeLabelActiveData))
      );
    },
    // 标签弹窗关闭
    storeLabelClose() {
      this.storeLabelShow = false;
    },
    addPropertyRule() {
      this.formValidate.property.property_rule.push({
        sub_type: 2, //规则子类型:1=>积分,2=>余额(资产)
        balance_type: 1, //余额类型:1=>充值,2=>消耗(资产)
        time_dimension: 3, //累计
        amount_value: 0, //金额/数值（金额/积分）
        operation_type: 1, //充值类型:1=>充值次数,2=>充值金额
        amount_value_min: 0, //金额/数值最小
        amount_value_max: 0, //金额/数值大
        operation_times_min: 0, //操作次数最小
        operation_times_max: 0, //操作次数最大
      });
    },
    addTradeRule() {
      this.formValidate.trade.trade_rule.push({
        time_dimension: 3, //累计
        operation_type: 1, //充值类型:1=>充值次数,2=>充值金额
        amount_value_min: 0, //金额/数值最小
        amount_value_max: 0, //金额/数值大
        operation_times_min: 0, //操作次数最小
        operation_times_max: 0, //操作次数最大
      });
    },
    addCustomerRule() {
      this.formValidate.customer.customer_rule.push({
        customer_identity: 1, //客户身份:1=>注册时间,2=>访问时间,3=>用户等级, 4=>客户身份
        customer_num: "", //客户身份数据(注册时间,访问时间,用户等级,用户身份(1=>等级会员,2=>付费会员,3=>推广员,4=>采购商))
        time_val: [],
      });
    },
    propertyTypeChange(val, index) {
      if (val == 1) {
        this.formValidate.property.property_rule[index].balance_type = 2; //当前操作这一行规则改为消耗
        this.formValidate.property.property_rule[index].operation_type = 2; //当前操作这一行规则改为消耗金额
      }
    },
    createTime(val, index) {
      this.formValidate.customer.customer_rule[index].time_val = val;
    },
    delCell(index, type) {
      this.formValidate[type][type + "_rule"].splice(index, 1);
    },
    cancel() {
      this.$router.push({ path: "/admin/user/label" });
    },
    handleSubmit() {
      this.$refs.formValidate.validate((valid) => {
        if (valid) {
          let form = this.formValidate;
          if(form.label_type == 2 && !form.is_product && !form.is_property && !form.is_trade && !form.is_customer) return this.$Message.error("请添加规则");
          if (this.selectProducts.length) {
            this.formValidate.product.ids = this.selectProducts.map(
              (item) => item.product_id
            );
          }
          if (this.storeLabelActiveData.length) {
            this.formValidate.product.ids = this.storeLabelActiveData.map(
              (item) => item.id
            );
          }
          userLabelAddApi(this.formValidate)
            .then((res) => {
              this.$Message.success(res.msg);
              this.$router.push({ path: "/admin/user/label" });
            })
            .catch((res) => {
              this.$Message.error(res.msg);
            });
        } else {
          this.$Message.error("请填写完整信息");
        }
      });
    },
    getInfo() {
      userLabelInfoApi(this.$route.params.id).then((res) => {
        if (
          res.data.label_type == 2 &&
          res.data.product &&
          res.data.product.specify_dimension == 1 &&
          res.data.specifyData.length
        ) {
          this.selectProducts = res.data.specifyData.map((item) => {
            return {
              product_id: item.id,
              image: item.image,
              store_name: item.store_name,
            };
          });
        }
        if (
          res.data.label_type == 2 &&
          res.data.product &&
          res.data.product.specify_dimension == 3 &&
          res.data.specifyData.length
        ) {
          this.storeLabelActiveData = res.data.specifyData;
        }
        Object.assign(this.formValidate, res.data);
      });
    },
  },
};
</script>

<style lang="less">
.select-down-none {
  width: 300px;
  .el-select-dropdown {
    display: none !important;
  }
  .el-input__suffix {
    display: none !important;
  }
}
.w-300 {
  width: 300px;
}
.labelInput {
  border: 1px solid #dcdee2;
  width: 300px;
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
.gray-box {
  width: 848px;
  background-color: #f9f9f9;
  padding: 20px;
  margin-left: 95px;
  margin-bottom: 12px;
}
.add-btn {
  width: 80px;
  font-size: 13px;
  color: #2d8cf0;
  padding-top: 15px;
  .ivu-icon {
    font-size: 16px;
    font-weight: bold;
  }
  .pl-2 {
    padding-left: 2px;
  }
}
.rule-cell ~ .rule-cell {
  margin-top: 15px;
}
.px-6 {
  padding: 0 6px 0;
}
.icon-ic_delete {
  color: #909399;
  font-size: 14px;
  display: block;
  margin-left: 14px;
  cursor: pointer;
}
.lh-16px {
  line-height: 16px;
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
