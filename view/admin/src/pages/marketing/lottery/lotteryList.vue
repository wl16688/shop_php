<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <Form
          ref="tableFrom"
          :model="tableFrom"
          :label-width="labelWidth"
          :label-position="labelPosition"
          @submit.native.prevent
          inline
        >
          <FormItem label="活动时间：">
            <DatePicker
              v-model="timeVal"
              type="daterange"
              :editable="false"
              @on-change="onchangeTime"
              format="yyyy/MM/dd"
              placement="bottom-start"
              placeholder="选择时间"
              class="input-add"
              :options="pickerOptions"
            ></DatePicker>
          </FormItem>
          <FormItem label="活动状态：">
            <Select
              class="input-add"
              v-model="tableFrom.start"
              clearable
              @on-change="userSearchs"
              placeholder="全部"
            >
              <Option value="" label="全部" />
              <Option :value="0" label="未开始" />
              <Option :value="1" label="进行中" />
              <Option :value="2" label="已结束" />
            </Select>
          </FormItem>
          <FormItem label="活动类型：">
            <Select
              class="input-add"
              v-model="tableFrom.factor"
              clearable
              @on-change="userSearchs"
              placeholder="全部"
            >
              <Option value="" label="全部" />
              <Option :value="1" label="积分抽取" />
              <Option :value="3" label="订单支付" />
              <Option :value="4" label="订单评价" />
              <Option :value="5" label="关注公众号" />
            </Select>
          </FormItem>
          <FormItem label="开启状态：">
            <Select
              class="input-add"
              v-model="tableFrom.status"
              clearable
              @on-change="userSearchs"
              placeholder="全部"
            >
              <Option value="" label="全部" />
              <Option :value="1" label="开启" />
              <Option :value="0" label="关闭" />
            </Select>
          </FormItem>

          <FormItem label="搜索抽奖：">
            <Input
              class="input-add mr14"
              placeholder="请输入活动名称"
              v-model="tableFrom.keyword"
              @on-change="userSearchs"
            />
          </FormItem>
          <Button type="primary" @click="userSearchs()">搜索</Button>
        </Form>
      </div>
    </Card>
    <Card class="mt20" :bordered="false" dis-hover>
      <Button class="mb15" type="primary" @click="openPage(0)"
        >创建抽奖活动</Button
      >
      <Table
        :columns="columns"
        :data="tableList"
        :loading="loading"
        highlight-row
        no-data-text="暂无数据"
      >
        <template slot-scope="{ row }" slot="status">
          <i-switch
            class="defineSwitch"
            :true-value="1"
            :false-value="0"
            v-model="row.status"
            :value="row.status"
            @on-change="(e) => onchangeIsShow(row)"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </i-switch>
        </template>
        <template slot-scope="{ row }" slot="start_time">
          <p>开始：{{ row.start_time }}</p>
          <p>结束：{{ row.end_time }}</p>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row)">编辑</a>
          <Divider type="vertical" />
          <a @click="openPage(1, row)">抽奖记录</a>
          <Divider type="vertical" />
          <Dropdown transfer @on-click="(name) => changeMenu(row, name, index)">
            <a href="javascript:void(0)">
              更多
              <Icon type="ios-arrow-down"></Icon>
            </a>
            <DropdownMenu slot="list">
              <DropdownItem name="3">
                <span
                  class="copy copy-data"
                  :data-clipboard-text="copyLink(row)"
                  >复制链接</span
                >
              </DropdownItem>
              <DropdownItem name="4">删除抽奖</DropdownItem>
            </DropdownMenu>
          </Dropdown>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          v-if="total"
          :total="total"
          :current.sync="tableFrom.page"
          :page-size.sync="tableFrom.limit"
          @on-change="getList"
          show-elevator
        />
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import { lotteryList, lotteryStatus } from "@/api/lottery";
import { formatDate } from "@/utils/validate";
import { ruleShip, ruleMark } from "./formRule/ruleShip";
import customerInfo from "@/components/customerInfo";
import ClipboardJS from "clipboard";
export default {
  name: "lotteryList",
  filters: {
    formatDate(time) {
      if (time !== 0) {
        let time = new time(time * 1000);
        return formatDate(time, "yyyy-MM-dd hh:mm");
      }
    },
  },
  components: {
    customerInfo,
  },
  data() {
    return {
      shipModel: false,
      loading: false,
      blackModel: false,
      pickerOptions: this.$timeOptions,
      locationList: [],
      timeVal: [],
      shipForm: {
        id: "",
        deliver_name: "",
        deliver_number: null,
      },
      markForm: {
        id: "",
        mark: "",
      },
      ruleShip: ruleShip,
      ruleMark: ruleMark,
      fromList: {
        title: "选择时间",
        fromTxt: [
          { text: "全部", val: "" },
          { text: "今天", val: "today" },
          { text: "昨天", val: "yesterday" },
          { text: "最近7天", val: "lately7" },
          { text: "最近30天", val: "lately30" },
          { text: "本月", val: "month" },
          { text: "本年", val: "year" },
        ],
      },
      typeList: [
        { text: "全部", val: "" },
        { text: "未中奖", val: "1" },
        { text: "积分", val: "2" },
        { text: "余额", val: "3" },
        { text: "红包", val: "4" },
        { text: "优惠券", val: "5" },
        { text: "商品", val: "6" },
      ],
      blackList: [],
      loading2: false,
      promoterShow: false,
      tableList: [],
      columns: [
        {
          title: "ID",
          key: "id",
          minWidth: 80,
        },
        {
          title: "活动名称",
          key: "name",
          minWidth: 120,
        },
        {
          title: "活动类型",
          key: "lottery_type",
          minWidth: 100,
        },
        {
          title: "抽奖人数",
          key: "records_total_user",
          minWidth: 100,
        },
        {
          title: "中奖人数",
          key: "records_wins_user",
          minWidth: 100,
        },
        {
          title: "抽奖次数",
          key: "records_total_num",
          minWidth: 100,
        },
        {
          title: "中奖次数",
          key: "records_wins_num",
          minWidth: 100,
        },
        {
          title: "活动状态",
          key: "lottery_status",
          minWidth: 100,
        },
        {
          title: "开启状态",
          slot: "status",
          minWidth: 100,
        },
        {
          title: "活动时间",
          slot: "start_time",
          minWidth: 180,
        },
        {
          title: "操作",
          slot: "action",
          minWidth: 180,
          fixed: "right",
        },
      ],
      grid: {
        xl: 7,
        lg: 10,
        md: 12,
        sm: 24,
        xs: 24,
      },
      tableFrom: {
        keyword: "",
        time: [],
        page: 1,
        limit: 15,
        factor: "",
        status: "",
        start: "",
      },
      total: 0,
      time: [],
      modelType: 1,
      lottery_id: "",
      modelTitle: "",
      orderData: {
        id: 0,
        mark: "",
        pc_template_name: "",
        pc_template_sku: "",
      },
      orderModel: false,
      customerShow: false,
      formInline: {
        uid: 0,
        image: "",
      },
      total2: 0,
      receiveFrom: {
        lottery_id: 0,
        keyword: "",
        page: 1,
        limit: 15,
      },
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 96;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  created() {
    this.$nextTick(function () {
      const clipboard = new ClipboardJS(".copy-data");
      clipboard.on("success", () => {
        this.$message.success("复制成功");
      });
    });
    this.getList();
  },
  methods: {
    // 操作
    changeMenu(row, name, index) {
      switch (name) {
        case "1":
          this.customer(row);
          break;
        case "3":
          this.onCopy(row);
          break;
        case "4":
          this.del(row, "删除活动", index);
          break;
      }
    },
    onCopy(row) {
      this.$copyText(this.copyLink(row))
        .then(() => {
          this.$Message.success("复制成功");
        })
        .catch(() => {
          this.$Message.error("复制失败");
        });
    },
    copyLink(row) {
      return `${window.location.origin}/pages/goods/lottery/grids/index?type=${row.factor}&lottery_id=${row.id}`;
    },
    customer(row) {
      this.promoterShow = true;
      this.formInline.lottery_id = row.id;
    },
    customerPle() {
      this.customerShow = true;
    },
    // 选择人员
    selectCustomer(e) {
      this.customerShow = false;
      this.formInline.uid = e.uid;
      this.formInline.image = e.image;
      this.formInline.nickname = e.nickname;
    },
    edit(row) {
      this.$router.push({
        path: "/admin/marketing/lottery/create",
        query: { lottery_id: row.id, type: row.type },
      });
    },
    openPage(type, row) {
      let url;
      if (type === 1) {
        url = `/admin/marketing/lottery/recording_list?id=${row.id}`;
      } else {
        url = `/admin/marketing/lottery/create`;
      }
      this.$router.push({
        path: url,
      });
    },
    // 具体日期
    onchangeTime(e) {
      this.time = e;
      if (!e || !e[0]) {
        this.tableFrom.time = [];
      } else {
        this.tableFrom.time = this.time[0] ? this.time.join("-") : "";
      }
      this.tableFrom.page = 1;
      this.getList();
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `lottery/del/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.tableList.splice(num, 1);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    // 列表
    getList() {
      this.loading = true;
      lotteryList(this.tableFrom)
        .then(async (res) => {
          let data = res.data;
          this.tableList = data.list;
          this.total = data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    // 表格搜索
    userSearchs() {
      this.tableFrom.page = 1;
      this.getList();
    },
    // 修改是否显示
    onchangeIsShow(row) {
      lotteryStatus(row)
        .then(async (res) => {
          this.$Message.success(res.msg);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="less">
.tabBox_img {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  cursor: pointer;

  img {
    width: 100%;
    height: 100%;
  }
}

.prize {
  display: flex;
  align-items: center;
}

body .el-table ::-webkit-scrollbar {
  // z-index: 11111;
  // width: 6px;
  // height: 6px;
  // background-color: #F5F5F5;
}

.prize img {
  width: 36px;
  height: 36px;
  border-radius: 4px;
  cursor: pointer;
  margin-right: 5px;
}

.trips {
  color: #ccc;
}
.picBox {
  display: inline-block;
  cursor: pointer;

  .upLoad {
    width: 58px;
    height: 58px;
    line-height: 58px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    background: rgba(0, 0, 0, 0.02);
  }

  .pictrue {
    width: 60px;
    height: 60px;
    border: 1px dotted rgba(0, 0, 0, 0.1);
    margin-right: 10px;

    img {
      width: 100%;
      height: 100%;
    }
  }

  .iconfont {
    color: #898989;
  }
}
</style>
