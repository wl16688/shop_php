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
          <FormItem label="搜索：">
            <Input
              clearable
              placeholder="请输入身份名称"
              v-model="tableForm.keyword"
              class="input-add mr14"
            />
          </FormItem>
          <FormItem label="状态:">
            <Select
              v-model="tableForm.is_show"
              @on-change="userSearchs"
              clearable
              class="input-add"
            >
              <Option :value="0">隐藏</Option>
              <Option :value="1">显示</Option>
            </Select>
            <Button type="primary" class="ml14" @click="userSearchs">查询</Button>
          </FormItem>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" class="ivu-mt mt16">
      <div>
        <Button type="primary" @click="addItem()">添加身份</Button>
        <Table
          :data="dataList"
          :columns="columns"
          ref="table"
          class="mt-14"
          highlight-row
          no-data-text="暂无数据"
          no-filtered-data-text="暂无筛选结果"
        >
          <template slot-scope="{ row }" slot="is_show">
            <i-switch
              v-model="row.is_show"
              :value="row.is_show"
              :true-value="1"
              :false-value="0"
              @on-change="onchangeIsShow(row)"
              size="large"
            >
              <span slot="open">显示</span>
              <span slot="close">隐藏</span>
            </i-switch>
          </template>
          <template slot-scope="{ row, index }" slot="action">
            <a @click="editItem(row.id)">编辑</a>
            <Divider type="vertical"></Divider>
            <a @click="del(row, '删除采购商', index)">删除</a>
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
  channelIdentityListApi,
  channelIdentityAddApi,
  channelIdentityEditApi,
  channelIdentityUpdateApi,
} from "@/api/user";
export default {
  name: "standing",
  data() {
    return {
      total: 0,
      total2: 0,
      dataList: [],
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "身份名称", key: "name", minWidth: 120 },
        // { title: "等级", key: "level", minWidth: 120 },
        { title: "享受折扣", key: "discount", minWidth: 130 },
        { title: "状态", slot: "is_show", minWidth: 90 },
        { title: "操作", slot: "action", width: 120 },
      ],
      FromData: null,
      loading: false,
      current: 0,
      tableForm: {
        page: 1,
        limit: 15,
        keyword: "",
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
    // 搜索
    userSearchs() {
      this.tableForm.page = 1;
      this.getList();
    },
    // 列表
    getList() {
      this.loading = true;
      channelIdentityListApi(this.tableForm)
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
    pageChange(index) {
      this.tableForm.page = index;
      this.getList();
    },
    addItem() {
      this.$modalForm(channelIdentityAddApi())
        .then((res) => {
          this.getList();
        })
        .catch((err) => {});
    },
    editItem(id) {
      this.$modalForm(channelIdentityEditApi(id))
        .then((res) => {
          this.getList();
        })
        .catch((err) => {});
    },
    // 修改是否显示
    onchangeIsShow(row) {
      channelIdentityUpdateApi(row.id, row.is_show)
        .then(async (res) => {
          this.$Message.success(res.msg);
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        method: "DELETE",
        uid: row.uid,
        url: `channel/identity/${row.id}`,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>

<style scoped lang="less"></style>
