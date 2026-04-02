<template>
  <div style="width: 100%">
    <Modal
      v-model="modals"
      scrollable
      footer-hide
      closable
      title="账单详情"
      :mask-closable="false"
      @on-cancel="cancel"
      width="950"
    >
      <!-- <Divider dashed/> -->
      <Form
        ref="formValidate"
        inline
        :label-width="95"
        label-position="right"
        class="tabform"
        @submit.native.prevent
      >
        <FormItem label="选择供应商：" label-for="status1">
          <Select
            v-model="formValidate.supplier_id"
            placeholder="请选择"
            clearable
            @on-change="searchs"
            class="w-250"
          >
            <Option
              :value="item.id"
              v-for="item in supplierList"
              :key="item.id"
              >{{ item.supplier_name }}</Option
            >
          </Select>
        </FormItem>
        <FormItem label="订单搜索：" label-for="status1">
          <Input
            v-model="formValidate.keyword"
            placeholder="请输入交易单号/交易人"
            class="w-250"
          ></Input>
        </FormItem>
        <Button type="primary" @click="searchs">搜索</Button>
        <Button class="ml14" @click="reset">重置</Button>
      </Form>
      <!-- <Divider dashed/> -->
      <Table
        :columns="columns"
        :data="tabList"
        ref="table"
        :loading="loading"
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
        class="table"
      >
        <template slot-scope="{ row, index }" slot="number">
          <!-- <span class="color">{{row.number}}</span> -->
          <span v-if="row.pm == 0" class="colorgreen">- {{ row.number }}</span>
          <span v-if="row.pm == 1" class="colorred">+ {{ row.number }}</span>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          :current="formValidate.page"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="formValidate.limit"
        />
      </div>
    </Modal>
  </div>
</template>

<script>
import { supplierFfundRecordInfoApi, getSupplierList } from "@/api/supplier";
import { mapState } from "vuex";
export default {
  name: "commissionDetails",
  data() {
    return {
      grid: {
        xl: 7,
        lg: 7,
        md: 12,
        sm: 24,
        xs: 24,
      },
      modals: false,
      detailsData: {},
      Ids: 0,
      loading: false,
      supplierList: [],
      ids: "",
      formValidate: {
        ids: "",
        supplier_id: "",
        keyword: "",
        page: 1,
        limit: 10,
      },
      total: 0,
      columns: [
        {
          title: "交易单号",
          key: "order_id",
          minWidth: 80,
        },
        {
          title: "关联订单",
          key: "link_id",
          minWidth: 80,
        },
        {
          title: "交易时间",
          key: "trade_time",
          minWidth: 150,
        },
        {
          title: "交易金额",
          slot: "number",
          minWidth: 80,
        },
        {
          title: "交易人",
          key: "user_nickname",
          ellipsis: true,
          minWidth: 80,
        },
        {
          title: "交易类型",
          key: "type_name",
          minWidth: 80,
        },
        {
          title: "支付方式",
          key: "pay_type_name",
          minWidth: 80,
        },
        {
          title: "备注",
          key: "remark",
          minWidth: 120,
        },
      ],
      tabList: [],
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 90;
    },
    labelPosition() {
      return this.isMobile ? "top" : "left";
    },
  },
  mounted() {
    this.getSupplierList();
  },
  methods: {
    getSupplierList() {
      getSupplierList().then((res) => {
        this.supplierList = res.data;
      });
    },
    searchs() {
      this.formValidate.page = 1;
      this.getList(this.ids);
    },
    // 时间
    onchangeTime(e) {
      this.formValidate.start_time = e[0];
      this.formValidate.end_time = e[1];
    },
    // 列表
    getList(id) {
      this.ids = id;
      this.formValidate.ids = id;
      this.loading = true;
      supplierFfundRecordInfoApi(this.formValidate)
        .then(async (res) => {
          let data = res.data;
          this.tabList = data.list;
          this.total = data.count;
          this.loading = false;
        })
        .catch((res) => {
          this.loading = false;
          this.$Message.error(res.msg);
        });
    },
    pageChange(index) {
      this.formValidate.page = index;
      this.getList(this.ids);
    },
    reset() {
      this.formValidate = {
        ids: this.ids,
        supplier_id: "",
        keyword: "",
        data: "",
        page: 1,
        limit: 10,
      };
      this.getList(this.ids);
    },
    // 关闭按钮
    cancel() {
      this.formValidate = {
        ids: "",
        supplier_id: "",
        keyword: "",
        data: "",
        page: 1,
        limit: 10,
      };
    },
  },
};
</script>

<style lang="less" scoped>
.colorred {
  color: #ff5722;
}
.colorgreen {
  color: #009688;
}
.table {
  .ivu-table-default {
    overflow-y: auto;
    max-height: 350px;
  }
}
.dashboard-workplace {
  &-header {
    &-avatar {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      margin-right: 16px;
      font-weight: 600;
    }

    &-tip {
      width: 82%;
      display: inline-block;
      vertical-align: middle;

      &-title {
        font-size: 13px;
        color: #000000;
        margin-bottom: 12px;
      }

      &-desc {
        &-sp {
          width: 33.33%;
          color: #17233d;
          font-size: 12px;
          display: inline-block;
        }
      }
    }

    &-extra {
      .ivu-col {
        p {
          text-align: right;
        }

        p:first-child {
          span:first-child {
            margin-right: 4px;
          }

          span:last-child {
            color: #808695;
          }
        }

        p:last-child {
          font-size: 22px;
        }
      }
    }
  }
}
</style>
