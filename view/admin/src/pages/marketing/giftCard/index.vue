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
          <FormItem label="礼品卡搜索：">
            <Input
              placeholder="请输入礼品卡名称"
              v-model="formValidate.name"
              class="w-250"
            />
          </FormItem>
          <FormItem label="礼品卡状态：">
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
            <FormItem label="创建时间：">
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
        v-auth="['admin-marketing-ghiftCard-create']"
        type="primary"
        @click="add"
        class="mr10"
        >添加礼品卡</Button
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
        <template slot-scope="{ row }" slot="name">
          <div class="flex">
            <div class="cover-image">
              <img :src="row.cover_image" />
            </div>
            <div class="flex-1 pl-8">
              <div class="w-full line1">{{ row.name }}</div>
              <div class="text-blue pt-4">
                {{ row.type == 1 ? "储值卡" : "兑换卡" }}
              </div>
            </div>
          </div>
        </template>
        <template slot-scope="{ row }" slot="total_num">
          <div>{{ row.used_num }}/{{ row.total_num }}</div>
        </template>
        <template slot-scope="{ row }" slot="valid_type">
          <div v-if="row.valid_type == 1">永久有效</div>
          <div v-else-if="row.valid_type == 2">
            {{ row.fixed_time.join("-") }}
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
          <a @click="getRecord(row.id)">卡密管理</a>
          <Divider type="vertical" />
          <a @click="edit(row.id)">编辑</a>
          <Divider type="vertical" />
          <template>
            <Dropdown
              @on-click="changeMenu(row, $event, index)"
              :transfer="true"
            >
              <a href="javascript:void(0)" class="acea-row row-middle">
                <span>更多</span>
                <Icon type="ios-arrow-down"></Icon>
              </a>
              <DropdownMenu slot="list">
                <DropdownItem name="3">导出</DropdownItem>
                <DropdownItem name="2">数量</DropdownItem>
                <DropdownItem name="4">记录</DropdownItem>
                <DropdownItem name="1">删除</DropdownItem>
              </DropdownMenu>
            </Dropdown>
          </template>
          <!-- <a @click="del(row, '删除活动边框', index)">删除</a> -->
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
    <Modal
      v-model="showRecord"
      title="卡密记录"
      width="800"
      :footer-hide="true"
    > 
    <div>
      <Table
        :columns="[
          { title: 'ID', key: 'id', width: 80 },
          { title: '管理员', key: 'admin_name', minWidth: 100 },
          { title: '操作时间', key: 'add_time', minWidth: 120 },
          { title: '操作数量', key: 'num', minWidth: 100 },
          { title: '操作类型', slot: 'pm', minWidth: 100 },
          { title: '详情', key: 'record', minWidth: 100 },
        ]"
        :data="recordNumberList"
        class="ivu-mt"
        no-data-text="暂无数据"
      >
        <template slot-scope="{ row }" slot="pm">
          <Tag
            v-if="row.pm == 1"
            color="blue"
            size="medium"
            >增加</Tag
          >
          <Tag
            v-else-if="row.pm == 2"
            color="red"
            size="medium"
            >减少</Tag
          >
          <Tag v-else size="medium">未知</Tag>
        </template>
      </Table>
    </div>
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  cardGiftListApi,
  cardGiftOtherFormApi,
  cardGiftStatusUpdateApi,
  exportGiftCardApi,
  giftCardRecordNumberApi,
} from "@/api/marketing";
import timeOptions from "@/utils/timeOptions";
import exportExcel from "@/utils/newToExcel.js";
import { Modal } from "view-design";
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
      },
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "礼品卡名称", slot: "name", minWidth: 170 },
        { title: "批次名称", key: "batch_name", minWidth: 120 },
        { title: "数量(已使用/总数)", slot: "total_num", minWidth: 100 },
        { title: "有效期", slot: "valid_type", width: 130 },
        { title: "排序", key: "sort", minWidth: 100 },
        { title: "礼品卡状态", slot: "status", minWidth: 100 },
        { title: "创建时间", key: "add_time", minWidth: 140 },
        { title: "操作", slot: "action", minWidth: 120, fixed: "right" },
      ],
      dataList: [],
      loading: false,
      total: 0,
      recordNumberList: [],
      recordNumber: 0,
      showRecord: false
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
    this.getList();
  },
  methods: {
    createTime(e) {
      this.timeVal = e;
      this.formValidate.time = this.timeVal.join("-");
    },
    dataTime(e) {
      this.dataTimeVal = e;
      this.formValidate.time = this.dataTimeVal.join("-");
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `marketing/card/gift/${row.id}`,
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
    getRecord(card_id) {
      this.$router.push({
        path: "/admin/marketing/giftCard/record",
        query: {
          card_id,
        },
      });
    },
    //修改
    edit(id) {
      this.$router.push({
        path: "/admin/marketing/giftCard/create/" + id,
      });
    },
    //添加
    add() {
      this.$router.push({
        path: "/admin/marketing/giftCard/create",
      });
    },
    // 礼品卡列表
    getList() {
      cardGiftListApi(this.formValidate).then((res) => {
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
      this.formValidate = {
        name: "",
        status: "",
        time: "",
        page: 1,
      };
      this.timeVal = [];
      this.dataTimeVal = [];
      this.getList();
    },
    cardStatusChange(row) {
      cardGiftStatusUpdateApi(row.id, row.status)
        .then((res) => {
          this.$Message.success(res.msg);
          this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    changeMenu(row, name, index) {
      switch (name) {
        case "1":
          this.del(row, "删除礼品卡", index);
          break;
        case "2":
          this.editBatch(row.id);
          break;
        case "3":
          this.exports(row.id);
          break;
        case "4":
          this.getRecordNumber(row.id);
          break;
      }
    },
    editBatch(id) {
      this.$modalForm(cardGiftOtherFormApi(id))
        .then((res) => {
          this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
    getRecordNumber(id){
      giftCardRecordNumberApi(id).then((res) => {
        this.recordNumberList = res.data.list;
        this.recordNumber = res.data.count;
        this.showRecord = true;
      }).catch((err) => {
        this.$Message.error(err.msg);
      });
    },
    //导出
    async exports(id) {
      let [th, filekey, data, fileName] = [[], [], [], ""];
      let lebData = await this.getExcelData({ card_id: id });
      if (!fileName) fileName = lebData.filename;
      filekey = lebData.filekey;
      if (!th.length) th = lebData.header; //表头
      data = data.concat(lebData.export);
      exportExcel(th, filekey, fileName, data);
    },
    getExcelData(data) {
      return new Promise((resolve) => {
        exportGiftCardApi(data).then((res) => resolve(res.data));
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
