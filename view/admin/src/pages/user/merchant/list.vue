<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="pt-20 pl-20 pr-20">
        <Form ref="tableForm" :model="tableForm" :label-width="labelWidth" :label-position="labelPosition"
          @submit.native.prevent inline>
          <FormItem label="采购用户：">
            <Input v-model="tableForm.keyword" placeholder="请输入" element-id="name" clearable class="input-add mr-14"
              maxlength="20">
            <Select v-model="tableForm.field_key" slot="prepend" style="width: 80px" default-label="全部">
              <Option value="channel_name">采购商名称</Option>
              <Option value="real_name">联系人</Option>
              <Option value="phone">联系电话</Option>
            </Select>
            </Input>
          </FormItem>
          <FormItem label="采购商身份:">
            <Select v-model="tableForm.channel_type" @on-change="userSearchs" clearable class="input-add">
              <Option v-for="(item, index) in identityOptions" :key="index" :value="item.id">{{ item.name }}</Option>
            </Select>
          </FormItem>
          <FormItem label="消费次数：">
            <div class="input-add flex-between-center">
              <Input type="number" class="w-100px" v-model="count[0]" />
              <span>~</span>
              <Input type="number" class="w-100px" v-model="count[1]" />
            </div>
          </FormItem>
          <FormItem label="消费金额：">
            <div class="input-add flex-between-center">
              <Input type="number" class="w-100px" v-model="money[0]" />
              <span>~</span>
              <Input type="number" class="w-100px" v-model="money[1]" />
            </div>
          </FormItem>
          <FormItem :label-width="0">
            <Button type="primary" @click="userSearchs" class="mr14">查询</Button>
            <Button @click="reset">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt mt16">
      <div>
        <Button type="primary" class="mb-14" @click="addItem">添加采购商</Button>
        <Table :data="dataList" :columns="columns" ref="table" highlight-row no-data-text="暂无数据"
          no-filtered-data-text="暂无筛选结果">
          <template slot-scope="{ row, index }" slot="user_nickname">
            <span v-if="row.user_nickname">{{ row.user_nickname }}</span>
            <span class="pl-8">(uid:{{row.uid}})</span>
          </template>
          <template slot-scope="{ row, index }" slot="action">
            <a @click="getInfo(row.id)">详情</a>
            <Divider type="vertical"></Divider>
            <a @click="getEdit(row.id)">编辑</a>
            <Divider type="vertical"></Divider>
            <a @click="del(row, '删除采购商', index)">删除</a>
          </template>
        </Table>
        <div class="acea-row row-right page">
          <Page :total="total" show-elevator show-total :page.sync="tableForm.page" @on-change="pageChange"
            :page-size="tableForm.limit" />
        </div>
      </div>
    </Card>
    <Modal v-model="visible" scrollable :title="isEdit ? '编辑采购商' : '添加采购商'" :closable="true" :z-index="9" footer-hide
      width="650">
      <Form ref="merchantForm" :model="merchantForm" :rules="merchantRules" :label-width="85">
        <FormItem label="选择用户：" prop="uid" v-if="!isEdit">
          <div class="acea-row">
            <div class="pictrue" v-if="selectUser.uid" @click="userTap">
              <img v-lazy="selectUser.image" />
            </div>
            <div class="upLoad acea-row row-center-wrapper" v-else @click="userTap">
              <Icon type="ios-camera-outline" size="26" />
            </div>
          </div>
        </FormItem>
        <FormItem label="采购名称：" prop="channel_name">
          <Input v-model="merchantForm.channel_name" placeholder="请输入采购名称" maxlength="20" />
        </FormItem>
        <!-- 联系人 -->
        <FormItem label="联系人：" prop="real_name">
          <Input v-model="merchantForm.real_name" placeholder="请输入联系人姓名" maxlength="20" />
        </FormItem>
        <!-- 联系电话 -->
        <FormItem label="联系电话：" prop="phone">
          <Input v-model="merchantForm.phone" placeholder="请输入联系电话" maxlength="20" />
        </FormItem>
        <!-- 省市区 -->
        <FormItem label="省市区：" prop="province_ids">
          <Cascader :data="addressData" :load-data="loadData" v-model="merchantForm.province_ids" @on-change="addchack"
            class="inputW"></Cascader>
        </FormItem>
        <FormItem label="详细地址：" prop="address">
          <Input v-model="merchantForm.address" placeholder="请输入详细地址" maxlength="20" />
        </FormItem>
        <!-- 采购商身份 -->
        <FormItem label="采购身份：" prop="channel_type">
          <Select v-model="merchantForm.channel_type" clearable>
            <Option v-for="(item, index) in identityOptions" :key="index" :value="item.id">{{ item.name }}</Option>
          </Select>
        </FormItem>
        <!-- 备注 -->
        <FormItem label="备注：">
          <Input type="textarea" :autosize="{ minRows: 2, maxRows: 4 }" v-model="merchantForm.admin_remark"
            placeholder="请输入备注" maxlength="100" />
        </FormItem>
        <FormItem label="资质图片：" prop="certificate">
          <div class="acea-row">
            <div class="pictrue" v-for="(item, index) in merchantForm.certificate" :key="index">
              <img :src="item" v-viewer />
              <Button shape="circle" icon="md-close" @click.native="handleRemove(index)" class="btndel"></Button>
            </div>
            <div v-if="merchantForm.certificate.length < 8" class="upLoad acea-row row-center-wrapper"
              @click="modalPicTap('duo')">
              <Icon type="ios-camera-outline" size="26" />
            </div>
          </div>
          <div class="text--w111-999">(建议上传5M以内的图片）</div>
        </FormItem>
      </Form>
      <div class="acea-row row-right">
        <Button @click="cancel">取消</Button>
        <Button type="primary" class="ml-14" @click="addMerchantConfirm">保存</Button>
      </div>
    </Modal>
    <Modal v-model="customerShow" scrollable title="请选择商城用户" :closable="false" width="900">
      <customerInfo v-if="customerShow" :is_channel="0" @imageObject="imageObject"></customerInfo>
    </Modal>
    <Drawer :closable="false" width="700" class-name="order_box" v-model="showDrawer" :styles="{ padding: 0 }"
      @on-visible-change="drawerChange">
      <div>
        <div class="pt-30 pl-24 pb-20">
          <div class="flex-y-center">
            <img class="info-avatar" :src="merInfo.avatar" />
            <span class="fs-16 fw-500 pl-12">{{ merInfo.channel_name }}</span>
          </div>
          <div class="flex pt-24">
            <div class="flex-col w-64">
              <span class="fs-12 text--w111-606266">总计订单</span>
              <span class="fs-14 text--w111-000 pt-8">{{ merInfo.order_count }}笔</span>
            </div>
            <div class="flex-col w-96 ml-100">
              <span class="fs-12 text--w111-606266">总消费金额</span>
              <span class="fs-14 text--w111-000 pt-8">{{ merInfo.order_price }}元</span>
            </div>
            <div class="flex-col w-64 ml-100">
              <span class="fs-12 text--w111-606266">消费次数</span>
              <span class="fs-14 text--w111-000 pt-8">{{ merInfo.order_count }}次</span>
            </div>
          </div>
        </div>
        <Tabs v-model="activeName" @on-click="onClickTab">
          <TabPane label="基础信息" name="info">
            <Form :model="merInfo" :label-width="90">
              <FormItem label="采购商名称:">
                <div>{{ merInfo.channel_name }}</div>
              </FormItem>
              <!-- 联系人 -->
              <FormItem label="联系人:">
                <div>{{ merInfo.real_name }}</div>
              </FormItem>
              <!-- 联系电话 -->
              <FormItem label="联系电话:">
                <div>{{ merInfo.phone }}</div>
              </FormItem>
              <!-- 省市区 -->
              <FormItem label="省市区:">
                <div>{{ merInfo.province }}</div>
              </FormItem>
              <FormItem label="详细地址:">
                <div>{{ merInfo.address }}</div>
              </FormItem>
              <!-- 采购商身份 -->
              <FormItem label="采购商身份:">
                <div>{{ shenfen }}</div>
                <!-- <Select disabled v-model="merInfo.channel_type" clearable>
                  <Option v-for="(item, index) in identityOptions" :key="index" :value="item.id">{{ item.name }}
                  </Option>
                </Select> -->
              </FormItem>
              <!-- 备注 -->
              <FormItem label="备注:">
                <div>{{ merInfo.admin_remark }}</div>
              </FormItem>
              <FormItem label="资质图片">
                <div class="acea-row">
                  <div class="zizhi mr-12" v-for="(item, index) in merInfo.certificate" :key="index">
                    <img class="w-full block rd-4px pointer" :src="item" v-viewer />
                  </div>
                </div>
              </FormItem>
            </Form>
          </TabPane>
          <TabPane label="消费记录" name="record">
            <Table :columns="recordColumn" :data="recordData" ref="table" :loading="loading" no-userFrom-text="暂无数据"
              no-filtered-userFrom-text="暂无筛选结果"></Table>
            <div class="acea-row row-right page">
              <Page :total="total2" :current.sync="recordForm.page" show-elevator show-total
                @on-change="recordPageChange" :page-size="recordForm.limit" />
            </div>
          </TabPane>
        </Tabs>
      </div>
    </Drawer>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  channelMerchantListApi,
  channelMerchantVerifyApi,
  channelMerchantCreateApi,
  channelIdentityOptionsApi,
  channelMerchantInfoApi,
  channelUserRecordApi,
} from "@/api/user";
import { cityApi } from "@/api/store";
import timeOptions from "@/utils/timeOptions";
import customerInfo from "@/components/customerInfo";
export default {
  name: "merchantList",
  components: {
    customerInfo,
  },
  data() {
    return {
      options: timeOptions,
      total: 0,
      total2: 0,
      dataList: [],
      timeVal: [],
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "采购商名称", key: "channel_name", minWidth: 120 },
        { title: "采购商身份", key: "identity_name", minWidth: 120 },
        { title: "享受折扣(%)", key: "identity_discount", minWidth: 80 },
        { title: "联系人", key: "real_name", minWidth: 80 },
        { title: "联系电话", key: "phone", minWidth: 100 },
        { title: "用户昵称", slot: "user_nickname", minWidth: 140 },
        { title: "创建时间", key: "add_time", minWidth: 120 },
        { title: "备注", key: "admin_remark", tooltip: true, minWidth: 100 },
        { title: "操作", slot: "action", width: 120 },
      ],
      identityOptions: [],
      FromData: null,
      loading: false,
      current: 0,
      tableForm: {
        page: 1,
        limit: 15,
        channel_type: "",
        keyword: "",
        count: '',
        money: '',
        field_key: "",
        status: "",
        date: "",
      },
      visible: false,
      merchantForm: {
        uid: "", //用户ID
        channel_name: "", //采购商名称
        channel_type: "", //采购商身份
        real_name: "", //联系人
        phone: "", //联系电话
        province: "", //省市区
        address: "", //详细地址
        admin_remark: "", //备注
        code: "", //验证码
        certificate: [], //资质图片
        province_ids: [], //省市区ID
      },
      merchantRules: {
        uid: [
          { required: true, message: "请选择用户" },
        ],
        channel_name: [
          { required: true, message: "请输入采购商名称", trigger: "blur" },
        ],
        real_name: [
          { required: true, message: "请输入联系人姓名", trigger: "blur" },
        ],
        phone: [
          { required: true, message: "请输入联系电话", trigger: "blur" },
          {
            pattern: /^1[3-9]\d{9}$/,
            message: "请输入正确的手机号",
            trigger: "blur",
          },
        ],
        province_ids: [
          { required: true, message: "请选择省市区", type: "array", trigger: "change" },
        ],
        address:[
          { required: true, message: "请输入详细地址", trigger: "blur" },
        ],
        certificate: [
          { required: true, message: "请上传资质图片" },
        ],
        channel_type: [{ required: true, message: "请选择采购商身份" }],
      },
      isEdit: false,
      selectUser: {
        uid: "",
        image: "",
      },
      customerShow: false,
      lazyProps: {
        lazy: true,
        lazyLoad: this.provinceFn,
      },
      showDrawer: false,
      activeName: "info",
      merInfo: {
        id: "",
        channel_name: "",
        channel_type: "",
        real_name: "",
        phone: "",
        province: "",
        address: "",
        remark: "",
        code: "",
        certificate: [],
      },
      addressData: [],
      count: [],
      money: [],
      recordColumn: [
        { title: "订单ID", key: "order_id", minWidth: 160 },
        { title: "收货人", key: "real_name", minWidth: 100 },
        { title: "商品数量", key: "total_num", minWidth: 90 },
        { title: "商品总价", key: "total_price", minWidth: 110 },
        { title: "实付金额", key: "pay_price", minWidth: 120 },
        { title: "交易完成时间", key: "pay_time", minWidth: 120 },
      ],
      recordData: [],
      recordForm: {
        page: 1,
        limit: 15,
        channel: 1,
      },
      shenfen: ""
    };
  },
  computed: {
    ...mapState("media", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 80;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  created() {
    this.getOptions();
    this.cityInfo({ pid: 0 });
    this.getList();
  },
  methods: {
    // 具体日期
    onchangeTime(e) {
      this.timeVal = e;
      this.tableForm.date = this.timeVal.join("-");
    },
    getOptions() {
      channelIdentityOptionsApi()
        .then((res) => {
          this.identityOptions = res.data;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 搜索
    userSearchs() {
      this.tableForm.page = 1;
      this.tableForm.count = this.count.join("-");
      this.tableForm.money = this.money.join("-");
      this.getList();
    },
    // 列表
    getList() {
      this.loading = true;
      channelMerchantListApi(this.tableForm)
        .then(async (res) => {
          let data = res.data;
          this.dataList = data.list;
          this.total = data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    addItem() {
      this.isEdit = false;
      this.merchantForm = {
        uid: "", //用户ID
        channel_name: "", //采购商名称
        channel_type: "", //采购商身份
        real_name: "", //联系人
        phone: "", //联系电话
        province: "", //省市区
        address: "", //详细地址
        admin_remark: "", //备注
        code: "", //验证码
        certificate: [], //资质图片
        province_ids: [], //省市区ID
      };
      this.selectUser = {
        uid: "",
        image: "",
      };
      this.visible = true;
    },
    reset() {
      this.tableForm.keyword = "";
      this.tableForm.field_key = "";
      this.tableForm.channel_type = "";
      this.tableForm.count = "";
      this.count = [];
      this.money = [];
      this.timeVal = [];
      this.tableForm.date = "";
      this.getList();
    },
    pageChange(index) {
      this.tableForm.page = index;
      this.getList();
    },
    recordPageChange(index) { },
    verify(id) {
      this.$modalForm(channelMerchantVerifyApi(id))
        .then((res) => {
          this.getList();
        })
        .catch((err) => { });
    },
    userTap() {
      this.customerShow = true;
    },
    imageObject(e) {
      this.customerShow = false;
      this.selectUser = e;
      this.merchantForm.uid = e.uid;
    },
    modalPicTap() {
      this.$imgModal((e) => {
        e.forEach((item) => {
          this.merchantForm.certificate.push(item.att_dir);
          this.merchantForm.certificate = this.merchantForm.certificate.splice(
            0,
            8
          );
        });
      });
    },
    getInfo(id) {
      channelMerchantInfoApi(id)
        .then((res) => {
          this.merInfo = res.data;
          this.merInfo.province = res.data.province.toString();
          let i = this.identityOptions.findIndex(
            (item) => item.id == res.data.channel_type
          );
          this.shenfen = this.identityOptions[i].name;
          this.showDrawer = true;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    onClickTab(e) {
      console.log(e);
      this.activeName = e;
      if (e == "record") {
        this.getRecord();
      }
    },
    getRecord() {
      this.loading = true;
      channelUserRecordApi(this.merInfo.id, this.recordForm)
        .then((res) => {
          let data = res.data;
          this.recordData = data.list;
          this.total2 = data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    getEdit(id) {
      channelMerchantInfoApi(id)
        .then((res) => {
          this.merchantForm = res.data;
          this.merchantForm.province = res.data.province.toString();
          this.merchantForm.province_ids = res.data.province_ids.map((item) =>
            Number(item)
          );
          this.isEdit = true;
          this.visible = true;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    addMerchantConfirm() {
      this.$refs.merchantForm.validate((valid) => {
        if (valid) {
          channelMerchantCreateApi(this.merchantForm)
            .then((res) => {
              this.$Message.success(res.msg);
              this.cancel();
              this.tableForm.page = 1;
              this.getList();
            })
            .catch((res) => {
              this.$Message.error(res.msg);
            });
        } else {
          return false;
        }
      });
    },
    cancel() {
      this.merchantForm = {
        uid: "", //用户ID
        channel_name: "", //渠道商名称
        channel_type: "", //渠道商身份
        real_name: "", //联系人
        phone: "", //联系电话
        province: "", //省市区
        address: "", //详细地址
        remark: "", //备注
        code: "", //验证码
        certificate: [], //资质图片
      };
      this.visible = false;
    },
    cityInfo(data) {
      cityApi(data).then((res) => {
        this.addressData = res.data;
      });
    },
    loadData(item, callback) {
      item.loading = true;
      cityApi({ pid: item.value }).then((res) => {
        item.children = res.data;
        item.loading = false;
        callback();
      });
    },
    addchack(e, selectedData) {
      this.merchantForm.province = selectedData.map((o) => o.label).join(",");
    },
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        method: "DELETE",
        uid: row.uid,
        url: `channel/merchant/${row.id}`,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.dataList.splice(num, 1);
          this.total--;
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    drawerChange(val) {
      if (!val) {
        this.activeName = 'info';
      }
    }
  },
};
</script>

<style scoped lang="less">
.info-avatar {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  object-fit: cover;
}

.ml-100 {
  margin-left: 100px;
}

.zizhi {
  width: 72px;
  height: 72px;
  border-radius: 8px;
  margin-bottom: 8px;

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.ivu-tabs {
  color: rgba(0, 0, 0, 0.85);

  /deep/ .ivu-tabs-bar {
    border-bottom: 0;
    margin-bottom: 0;
    background-color: #f5f7fa;

    .ivu-tabs-nav-container {
      font-size: 13px;
    }

    .ivu-tabs-ink-bar {
      display: none;
    }

    .ivu-tabs-tab {
      padding: 7px 19px !important;
      margin-right: 0;
      line-height: 26px;
    }

    .ivu-tabs-tab-active {
      background-color: #ffffff;
      color: rgba(0, 0, 0, 0.85);

      &:before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background-color: #1890ff;
      }
    }
  }

  /deep/ .ivu-tabs-content {
    .ivu-tabs-tabpane {
      padding: 15px 15px !important;

      &:first-child {
        padding: 20px 20px 0 0 !important;
      }
    }
  }
}
</style>
