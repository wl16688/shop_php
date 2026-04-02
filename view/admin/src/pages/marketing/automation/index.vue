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
          <FormItem label="任务名称：">
            <Input
              placeholder="请输入任务名称"
              v-model="formValidate.name"
              class="w-250"
            />
          </FormItem>
          <FormItem label="任务状态：">
            <Select
              v-model="formValidate.status"
              clearable
              class="w-250"
              @on-change="searchList"
            >
              <Option value="1">开启</Option>
              <Option value="0">关闭</Option>
            </Select>
          </FormItem>
          <div style="display: inline-block">
            <FormItem label="任务时间：">
              <DatePicker
                :editable="false"
                @on-change="createTime"
                :value="timeVal"
                format="yyyy/MM/dd"
                type="datetimerange"
                placement="bottom-start"
                placeholder="创建时间"
                :options="options"
                class="w-250"
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
      <!-- 操作 -->
      <Button
        type="primary"
        @click="add"
        class="mr10"
        >创建{{ pageName }}</Button
      >
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
        <template slot-scope="{ row }" slot="task_type">
          <span>{{ row.task_type == 1 ? '用户生日' : '自定义节日' }}</span>
        </template>
        <template slot-scope="{ row }" slot="activity_date_type">
          <span v-if="row.task_type == 1">{{ row.birthday_type | birthdayTypeFilter }}</span>
          <span v-else>{{ row.activity_date_type | activityTypeFilter }}</span>
        </template>
         <template slot-scope="{ row }" slot="push_time_type">
          <span>{{ row.push_time_type == 1 ? '全时段' : '指定时段' }}</span>
         </template>
        <template slot-scope="{ row }" slot="push_user_type">
          <span>{{ row.push_user_type == 1 ? '全部人群' : '指定人群' }}</span>
        </template>
        <!-- gift_type_text -->
        <template slot-scope="{ row }" slot="gift_type_text">
          <span v-if="row.gift_type_text.length > 0">{{ row.gift_type_text.join(',')  }}</span>
          <span v-else>--</span>
        </template>
        <template slot-scope="{ row }" slot="push_channel_text">
          <span>{{ row.push_channel_text.join(',') }}</span>
        </template>
        <template slot-scope="{ row }" slot="start_time">
          <div v-if="row.is_permanent">永久有效</div>
          <div v-else>
            <div>开始：{{ row.start_time }}</div>
            <div>结束：{{ row.end_time }}</div>
          </div>
        </template>
        <template slot-scope="{ row }" slot="status">
          <i-switch
            v-model="row.status"
            :true-value="1"
            :false-value="0"
            @on-change="cardStatusChange(row)"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </i-switch>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row.id)">编辑</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除任务', index)">删除</a>
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
  holidayListApi,
  holidayStatusApi,
} from "@/api/marketing";
import timeOptions from "@/utils/timeOptions";
export default {
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      dataTimeVal: [],
      formValidate: {
        name: "",
        status: "",
        time: "",
        page: 1,
        limit: 15,
        task_type:2,
      },
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "任务名称", key: "name", minWidth: 120 },
        { title: "任务类型", slot: "task_type", minWidth: 120 },
        { title: "活动日期", slot: "activity_date_type", minWidth: 100 },
        { title: "推送时段", slot: "push_time_type", width: 130 },
        { title: "推送人群", slot: "push_user_type", minWidth: 100 },
        { title: "赠送内容", slot: "gift_type_text", minWidth: 100 },
        { title: "推送渠道", slot: "push_channel_text", minWidth: 100 },
        { title: "有效期", slot: "start_time", width: 180 },
        { title: "活动状态", slot: "status", minWidth: 100 },
        { title: "操作", slot: "action", minWidth: 120, fixed: "right" },
      ],
      dataList: [],
      loading: false,
      total: 0,
      recordNumberList: [],
      recordNumber: 0,
      showRecord: false,
      pageName: "智能推送",
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
  filters: {
    birthdayTypeFilter(value) {
      const types = {
        1: "生日当天",
        2: "生日当周",
        3: "生日当月",
      };
      return types[value] || "未知类型";
    },
    activityTypeFilter(value){
      const types = {
        1: "自定义",
        2: "每月",
        3: "每周"
      };
      return types[value] || "未知类型";
    }
  },
   watch: {
    "$route.path": {
      handler(newPath) {
        if (newPath == "/admin/marketing/automation") {
          this.formValidate.task_type = 2; 
          this.pageName = "智能推送";
        } else if (newPath == "/admin/marketing/birthday") {
          this.formValidate.task_type = 1; 
          this.pageName = "生日有礼";
        }
        this.getList();
      },
      deep: true,
      immediate: true, // 可选：是否在组件创建时立即执行一次
    },
  },
  // created() {
  //   this.getList();
  // },
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.formValidate.time = this.timeVal.join("-");
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `marketing/holiday/del/${row.id}`,
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
    //修改
    edit(id) {
      this.$router.push({
        path: "/admin/marketing/automation/create/" + id
      });
    },
    //添加
    add() {
      this.$router.push({
        path: "/admin/marketing/automation/create?task_type=" + this.formValidate.task_type
      });
    },
    getList() {
      holidayListApi(this.formValidate).then((res) => {
        this.dataList = res.data.list;
        this.total = res.data.count;
      }).catch((err) => {
        this.$Message.error(err.msg);
      });
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
      this.formValidate.name = "";
      this.formValidate.status = "";
      this.formValidate.time = "";
      this.formValidate.page = 1;
      this.timeVal = [];
      this.dataTimeVal = [];
      this.getList();
    },
    cardStatusChange(row) {
      holidayStatusApi(row.id, row.status)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
  },
};
</script>

<style lang="less" scoped>
.cover-image {
  width: 77px;
  height: 44px;
  border-radius: 4px;
  img {
    width: 100%;
    height: 100%;
    border-radius: 4px;
    display: block;
    object-fit: cover;
  }
}
</style>
