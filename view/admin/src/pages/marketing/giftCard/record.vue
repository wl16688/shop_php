<template>
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd">
        <!-- 查询条件 -->
        <Form
          ref="formValidate"
          inline
          :model="formValidate"
          :label-width="labelWidth"
          :label-position="labelPosition"
          @submit.native.prevent
        >
        <FormItem label="卡号搜索：">
            <Input
              v-model="formValidate.keyword"
              placeholder="请输入"
              element-id="name"
              clearable
              class="w-250"
              maxlength="20"
            >
              <Select
                v-model="formValidate.field_key"
                slot="prepend"
                style="width: 80px"
                default-label="全部"
              >
                <Option value="card_number">卡号</Option>
                <Option value="nickname">用户名</Option>
                <Option value="uid">用户ID</Option>
              </Select>
            </Input>
          </FormItem>
           <FormItem label="礼品卡：" v-if="showCard">
            <Select
              v-model="formValidate.card_id"
              class="w-250"
              clearable
              @on-change="searchList"
            >
              <Option
                v-for="(item, index) in cardOptions"
                :key="index"
                :value="item.id"
                >{{ item.name }}</Option
              >
            </Select>
           </FormItem>
          <!-- 选择卡密批次 -->
          <FormItem label="卡号批次：" v-if="showBatch">
            <Select
              v-model="formValidate.batch_id"
              class="w-250"
              clearable
              @on-change="searchList"
            >
              <Option
                v-for="(item, index) in batchOptions"
                :key="index"
                :value="item.id"
                >{{ item.name }}</Option
              >
            </Select>
          </FormItem>
          <!-- 卡密状态 -->
           <FormItem label="卡密状态：">
            <Select
              v-model="formValidate.status"
              class="w-250"
              clearable
              @on-change="searchList"
            >
              <Option value="0">未分配</Option>
              <Option value="1">已使用</Option>
              <Option value="2">已分配</Option>
              <Option value="3">已过期</Option>
            </Select>
           </FormItem>
          <div style="display: inline-block">
            <FormItem label="激活时间：">
              <DatePicker
                :editable="false"
                @on-change="createTime"
                :value="timeVal"
                format="yyyy/MM/dd"
                type="datetimerange"
                placement="bottom-start"
                placeholder="激活时间"
                :options="options"
                class="input-add"
              ></DatePicker>
            </FormItem>
            <Button type="primary" @click="searchList" class="mr14 mt1"
              >查询</Button
            >
            <Button class="mt1" @click="reset()">重置</Button>
          </div>
        </Form>
      </div>
    </Card>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 表格 -->
      <Table
        ref="table"
        :columns="columns"
        :data="dataList"
        class="ivu-mt"
        :loading="loading"
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="nickname">
          <div>{{ row.nickname || '--' }} <span v-show="row.uid">/({{ row.uid }})</span></div>
        </template>
        <template slot-scope="{ row }" slot="type">
          <Tag color="green" size="medium" v-if="row.type == 1">储值卡</Tag>
          <Tag color="red" size="medium" v-else-if="row.type == 2">兑换卡</Tag>
          <span v-else>未关联</span>
        </template>
        <template slot-scope="{ row }" slot="card_name">
          <div>{{ row.card_name || '未关联' }}</div>
        </template>
        <template slot-scope="{ row }" slot="active_time">
          <div>{{ row.active_time || '未激活' }}</div>
        </template>
        <template slot-scope="{ row }" slot="status">
          <!-- 卡密状态 0-未使用 1-已使用 2-已分配,3 已过期 -->
          <Tag color="blue" size="medium" v-if="row.status == 0">未分配</Tag>
          <Tag color="red" size="medium" v-else-if="row.status == 1">已使用</Tag>
          <Tag color="orange" size="medium" v-else-if="row.status == 2">已分配</Tag>
          <Tag size="medium" v-else>已过期</Tag>
        </template>
        <template slot-scope="{ row }" slot="remark">
          <div>{{ row.remark || '--' }}</div>
        </template>
        <template slot-scope="{ row }" slot="action">
          <a @click="getRemark(row.id)">备注</a>
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
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  cardBatchOptionApiApi,
  cardCodeListApi,
  cardGiftCodeMarkApi,
  cardGiftOptionApiApi
} from "@/api/marketing";
import timeOptions from "@/utils/timeOptions";
export default {
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      formValidate: {
        page: 1,
        limit: 15,
        batch_id: "",
        card_id: "",
        status: "",
        active_time: "",
        field_key: "",
        keyword: "",
      },
      columns: [
        { title: "卡号", key: "card_number", minWidth: 150 },
        { title: "密码", key: "card_pwd", minWidth: 120 },
        { title: "使用用户", slot: "nickname", minWidth: 100 },
        { title: "卡券类型", slot: "type", minWidth: 100 },
        { title: "关联礼品卡", slot: "card_name", minWidth: 100 },
        { title: "关联卡密批次", key: "batch_name", minWidth: 100 },
        { title: "激活时间", slot: "active_time", minWidth: 100 }, //卡密状态
        { title: "卡密状态", slot: "status", minWidth: 100 }, //
        { title: "备注", slot: "remark", minWidth: 100 }, //卡密状态 卡密状态 0-未使用 1-已使用 2-已分配,3 已过期
        { title: "创建时间", key: "add_time", minWidth: 140 },
        { title: "操作", slot: "action", minWidth: 120, fixed: "right" },
      ],
      dataList: [],
      loading: false,
      total: 0,
      batchOptions: [],
      cardOptions: [],
      showBatch: true,
      showCard: true,
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
  mounted() {
    if(this.$route.query.batch_id){
      this.formValidate.batch_id = this.$route.query.batch_id;
      this.showBatch = false;
    }
    if(this.$route.query.card_id){
      this.formValidate.card_id = this.$route.query.card_id;
      this.showCard = false;
    }
    this.getList();
    this.getBatchOptions();
  },
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.formValidate.active_time = this.timeVal.join("-");
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `marketing/card/batch/${row.id}`,
        method: "DELETE",
        ids: "",
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
    // 卡密列表
    getList() {
      cardCodeListApi(this.formValidate).then((res) => {
        this.dataList = res.data.list;
        this.total = res.data.count;
      });
    },
    getBatchOptions() {
      cardBatchOptionApiApi().then((res) => {
        this.batchOptions = res.data;
      });
      cardGiftOptionApiApi().then(res=>{
        this.cardOptions = res.data;
      })
    },
    pageChange(index) {
      this.formValidate.page = index;
      this.getList();
    },
    // 查询
    searchList() {
      this.formValidate.page = 1;
      this.getList();
    },
    reset() {
      this.formValidate = {
        page: 1,
        limit: 15,
        batch_id: "",
        card_id: "",
        status: "",
        active_time: "",
        field_key: "",
        keyword: "",
      };
      if(this.$route.query.batch_id){
        this.formValidate.batch_id = this.$route.query.batch_id;
        this.showBatch = false;
      }
      if(this.$route.query.card_id){
        this.formValidate.card_id = this.$route.query.card_id;
        this.showCard = false;
      }
      this.timeVal = [];
      this.getList();
    },
    getRemark(id){
      this.$modalForm(cardGiftCodeMarkApi(id)).then((res) => {
        this.getList();
      }).catch((err) => {});
    }
  },
};
</script>

<style lang="less" scoped>
.w-415 {
  width: 415px;
}
.ml-30 {
  margin-left: 30px;
}
.desc {
  color: #999;
  font-size: 12px;
  height: 12px;
  line-height: 12px;
  padding: 12px 0;
}
</style>
