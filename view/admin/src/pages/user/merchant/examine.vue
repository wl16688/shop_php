<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="pt-20 pl-20 pr-20">
        <Form
          ref="tableForm"
          :model="tableForm"
          :label-width="labelWidth"
          :label-position="labelPosition"
          @submit.native.prevent
          inline
        >
          <FormItem label="时间选择：">
            <DatePicker
              :editable="false"
              @on-change="onchangeTime"
              :value="timeVal"
              format="yyyy/MM/dd"
              type="daterange"
              placement="bottom-start"
              placeholder="自定义时间"
              :options="options"
              class="input-add"
            ></DatePicker>
          </FormItem>
          <FormItem label="审核状态:">
            <Select
              v-model="tableForm.verify_status"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="0">审核中</Option>
              <Option :value="1">已通过</Option>
              <Option :value="2">已拒绝</Option>
            </Select>
          </FormItem>
          <FormItem label="采购用户：">
            <Input
              v-model="tableForm.keyword"
              placeholder="请输入"
              element-id="name"
              clearable
              class="input-add mr-14"
              maxlength="20"
            >
              <Select
                v-model="tableForm.field_key"
                slot="prepend"
                style="width: 80px"
                default-label="全部"
              >
                <Option value="channel_name">采购商名称</Option>
                <Option value="real_name">联系人</Option>
                <Option value="phone">联系电话</Option>
              </Select>
            </Input>
          </FormItem>
          <FormItem :label-width="0">
            <Button type="primary" @click="userSearchs" class="mr14" >查询</Button>
            <Button @click="reset">重置</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" class="ivu-mt mt16">
      <div>
        <Table
          :data="dataList"
          :columns="columns"
          ref="table"
          highlight-row
          no-data-text="暂无数据"
          no-filtered-data-text="暂无筛选结果"
        >
        <template slot-scope="{ row }" slot="address">
          <div>
             {{row.province.join(',')}} {{ row.address }}
          </div>
        </template>
        <template slot-scope="{ row }" slot="certificate">
          <div class="flex scroll-view" v-viewer>
            <img class="h-40 rd-4px block mr-8" v-for="(pic, i) in row.certificate" :key="i" :src="pic" />
          </div>
        </template>
          <template slot-scope="{ row }" slot="verify_status">
            <Tag color="blue" size="medium" v-if="row.verify_status == 0">审核中</Tag>
            <Tag color="green"  size="medium" v-if="row.verify_status == 1">已通过</Tag>
            <Tag color="red"  size="medium" v-if="row.verify_status == 2">已拒绝</Tag>
          </template>
          <template slot-scope="{ row, index }" slot="action">
            <a @click="verify(row.id)" v-if="row.verify_status == 0">审核</a>
            <Divider type="vertical" v-if="row.verify_status != 1"></Divider>
            <a @click="del(row, '删除采购商', index)" v-if="row.verify_status != 1">删除</a>
          </template>
        </Table>
        <div class="acea-row row-right page">
          <Page
            :total="total"
            show-elevator
            show-total
            :page.sync="tableForm.page"
            @on-change="pageChange"
            :page-size="tableForm.limit"
          />
        </div>
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  channelMerchantListApi,
  channelMerchantVerifyApi,
  channelIdentityUpdateApi,
} from "@/api/user";
import timeOptions from "@/utils/timeOptions";
export default {
  name: "standing",
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
        { title: "联系人", key: "real_name", width: 80 },
        { title: "联系电话", key: "phone", width: 100 },
        { title: "详细地址", slot: "address", tooltip: true, minWidth: 150 },
        { title: "资质图片", slot: "certificate", minWidth: 150 },
        { title: "申请时间", key: "add_time", minWidth: 120 },
        { title: "审核状态", slot: "verify_status", width: 80 },
        { title: "申请备注", key: "remark", tooltip: true, minWidth: 100 },
        { title: "操作", slot: "action", width: 120 },
      ],
      FromData: null,
      loading: false,
      current: 0,
      tableForm: {
        page: 1,
        limit: 15,
        keyword: "",
        field_key: "",
        verify_status: "",
        date: "",
        is_admin: 0,
      },
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
    this.getList();
  },
  methods: {
    // 具体日期
    onchangeTime(e) {
      this.timeVal = e;
      this.tableForm.date = this.timeVal.join("-");
    },
    // 搜索
    userSearchs() {
      this.tableForm.page = 1;
      this.getList();
    },
    // 列表
    getList() {
      this.loading = true;
      channelMerchantListApi(this.tableForm).then(async (res) => {
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
    reset(){
      this.tableForm.keyword = "";
      this.tableForm.field_key = "";
      this.tableForm.status = "";
      this.tableForm.verify_status = "";
      this.timeVal = [];
      this.tableForm.date = "";
      this.getList();
    },
    pageChange(index) {
      this.tableForm.page = index;
      this.getList();
    },
    verify(id){
      this.$modalForm(channelMerchantVerifyApi(id)).then((res) => {
        this.getList();
      })
      .catch((err) => {});
    },
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        method: "DELETE",
        uid: row.uid,
        url: `channel/merchant/${row.id}`,
      };
      this.$modalSure(delfromData).then((res) => {
          this.$Message.success(res.msg);
          this.dataList.splice(num, 1);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="less">
.scroll-view{
  overflow-x: scroll;
  cursor: pointer;
}
.scroll-view::-webkit-scrollbar {
  display: none; /* 隐藏滚动条 */
}
</style>
