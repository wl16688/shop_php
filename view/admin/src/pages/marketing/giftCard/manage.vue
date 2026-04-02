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
          <FormItem label="卡密搜索：">
            <Input
              placeholder="请输入卡密名称"
              v-model="formValidate.name"
              class="w-250"
            />
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
      <!-- 操作 -->
      <Button type="primary" @click="add" class="mr10">创建卡号批次</Button>
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
        <template slot-scope="{ row }" slot="total_num">
          <div>{{ row.used_num }}/{{ row.total_num }}</div>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="getRecord(row.id)">卡密管理</a>
          <Divider type="vertical" />
          <a @click="edit(row)">编辑</a>
          <Divider type="vertical" />
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
              <DropdownItem name="1">删除</DropdownItem>
            </DropdownMenu>
          </Dropdown>
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
      v-model="modals"
      closable
      footer-hide
      :title="isEdit ? '编辑卡号批次' : '创建卡号批次'"
      :mask-closable="false"
      :z-index="10"
      @on-cancel="cancel"
      width="540"
    >
      <div>
        <Form
          size="small"
          ref="addForm"
          :rules="ruleValidate"
          :model="addForm"
          :label-width="75"
        >
          <!-- 卡次名称 -->
          <FormItem label="批次名称:" prop="name">
            <Input
              v-model="addForm.name"
              placeholder="请输入卡次名称"
              class="w-433"
            />
          </FormItem>
          <template v-if="!isEdit">
            <!-- 卡号前缀 -->
            <FormItem label="卡号前缀:" prop="card_prefix">
              <Input
                v-model="addForm.card_prefix"
                :maxlength="8"
                placeholder="请输入卡号前缀"
                class="w-433"
              />
            </FormItem>
            <!-- 卡号后缀 -->
            <FormItem label="卡号后缀:" prop="card_suffix">
              <Input
                v-model="addForm.card_suffix"
                :maxlength="8"
                placeholder="请输入卡号后缀"
                class="w-433"
              />
            </FormItem>
            <!-- 卡号位数 数字输入框 整数 -->
            <FormItem label="生成数量:" prop="total_num">
              <InputNumber
                v-model="addForm.total_num"
                :min="1"
                :max="100000"
                placeholder="前缀和后缀之间的数字位数"
                class="w-433"
                @on-change="calculateGeneratedCards"
              />
              <div class="desc" v-show="addForm.total_num">
                卡号示例（{{ addForm.card_prefix }}{{ generatedCards }}{{addForm.card_suffix}}）
              </div>
              
            </FormItem>
            <!-- 卡密内容 多选框 -->
            <FormItem label="卡密规则:" prop="pwd_type">
              <CheckboxGroup v-model="addForm.pwd_type">
                <Checkbox :label="1" name="pwd_type">0-9</Checkbox>
                <Checkbox :label="2" name="pwd_type" class="ml-30"
                  >a-z</Checkbox
                >
                <Checkbox :label="3" name="pwd_type" class="ml-30"
                  >A-Z</Checkbox
                >
              </CheckboxGroup>
            </FormItem>
            <!-- 卡密位数 -->
            <FormItem label="卡密位数:" prop="pwd_num">
              <Input type="number" v-model="addForm.pwd_num" placeholder="请输入卡密位数" class="w-433" />
              <div class="desc">生成的卡密密码总位数长度，6-20位</div>
              <!-- 卡密位数仅限制卡密内容长度 -->
            </FormItem>
          </template>
        </Form>
        <div class="acea-row row-right">
          <Button @click="cancel">取消</Button>
          <Button
            type="primary"
            class="ml-14"
            :loading="addLoading"
            @click="addConfirm"
            >确定</Button
          >
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import { mapState } from "vuex";
import {
  cardBatchCreateApi,
  cardBatchListApi,
  cardBatchUpdateApi,
  exportGiftCardApi
} from "@/api/marketing";
import timeOptions from "@/utils/timeOptions";
import exportExcel from '@/utils/newToExcel.js';
export default {
  data() {
    return {
      options: timeOptions,
      timeVal: [],
      dataTimeVal: [],
      formValidate: {
        name: "",
        add_time: "",
        page: 1,
        limit: 15,
      },
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "批次名称", key: "name", minWidth: 170 },
        { title: "卡号前缀", key: "card_prefix", minWidth: 100 },
        { title: "卡号后缀", key: "card_suffix", minWidth: 100 },
        { title: "数量(已分配/总数)", slot: "total_num", minWidth: 100 },
        { title: "创建时间", key: "add_time", minWidth: 140 },
        { title: "操作", slot: "action", width: 160, fixed: "right" },
      ],
      addForm: {
        name: "", //卡次名称
        total_num: null, //卡号位数
        card_prefix: "", //卡号前缀
        card_suffix: "", //卡号后缀
        pwd_type: [], //卡密内容 1: 0-9 2: a-z 3: A-Z
        pwd_num: null, //卡密位数
      },
      ruleValidate: {
        name: [
          {
            required: true,
            message: "请输入卡次名称",
            trigger: "blur",
          },
        ],
        total_num: [
          {
            required: true,
            message: "请输入卡号位数",
            trigger: "blur",
            validator: (rule, value, callback) => {
              if (value === null || value === undefined || value === "") {
                callback(new Error("请输入卡号位数"));
              } else {
                callback();
              }
            },
          },
        ],
        card_prefix: [
          {
            required: true,
            message: "请输入卡号前缀",
            trigger: "blur",
          },
          {
            validator: (rule, value, callback) => {
              if (value && /[\u4e00-\u9fa5]/.test(value)) {
                callback(new Error("卡号前缀不能包含汉字"));
              } else {
                callback();
              }
            },
            trigger: "blur",
          }
        ],
        card_suffix: [
          {
            required: true,
            message: "请输入卡号后缀",
            trigger: "blur"
          },
          {
            validator: (rule, value, callback) => {
              if (value && /[\u4e00-\u9fa5]/.test(value)) {
                callback(new Error("卡号前缀不能包含汉字"));
              } else {
                callback();
              }
            },
            trigger: "blur",
          }
        ],
        pwd_type: [{ required: true, message: "请选择卡密内容" }],
        pwd_num: [
          {
            required: true,
            message: "请输入正确的卡密位数",
            trigger: "blur",
            validator: (rule, value, callback) => {
              if (value === null || value === undefined || value === "") {
                callback(new Error("请输入卡号位数"));
              } else if(!value || value < 6 || value > 20){
                callback(new Error("请输入6-20的整数"));
              }else {
                callback();
              }
            },
          },
        ],
      },
      dataList: [],
      loading: false,
      total: 0,
      modals: false,
      isEdit: false,
      generatedCards: 0, // 生成的卡片数量
      rowId: "",
      addLoading: false,
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
      this.formValidate.create_time = this.timeVal.join("-");
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
    calculateGeneratedCards(value) {
      const length = value.toString().length;
      this.generatedCards = Array.from({ length }, () => 0).join('');
    },
    getRecord(id) {
      this.$router.push({
        path: "/admin/marketing/giftCard/record",
        query: {
          batch_id: id,
        },
      });
    },
    //修改
    edit(row) {
      this.addForm.name = row.name;
      this.isEdit = true;
      this.modals = true;
      this.rowId = row.id;
    },
    //添加
    add() {
      this.modals = true;
    },
    addConfirm() {
      this.$refs.addForm.validate((valid) => {
        if (valid) {
          this.addLoading = true;
          let api = this.isEdit
            ? cardBatchUpdateApi(this.rowId, { name: this.addForm.name })
            : cardBatchCreateApi(this.addForm);
          api
            .then((res) => {
              this.addLoading = false;
              this.$Message.success(res.msg);
              this.cancel();
            })
            .catch((err) => {
              this.addLoading = false;
              this.$Message.error(err.msg);
            });
        } else {
          this.$Message.error("请检查输入项");
        }
      });
    },
    cancel() {
      this.isEdit = false;
      this.addForm = {
        name: "", //卡次名称
        total_num: null, //卡号位数
        card_prefix: "", //卡号前缀
        card_suffix: "", //卡号后缀
        pwd_type: [], //卡密内容 1: 0-9 2: a-z 3: A-Z
        pwd_num: null, //卡密位数
      };
      this.modals = false;
      this.getList();
    },
    // 卡密列表
    getList() {
      cardBatchListApi(this.formValidate).then((res) => {
        this.dataList = res.data.list;
        this.total = res.data.count;
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
        create_time: "",
        page: 1,
      };
      this.timeVal = [];
      this.dataTimeVal = [];
      this.getList();
    },
    changeMenu(row, name, index){
      if(name == 1){
        this.del(row, '删除卡密', index)
      }else if(name == 3){
        this.exports(row.id);
      }
    },
    //导出
    async exports(id) {
      let [th, filekey, data, fileName] = [[], [], [], ''];
      let lebData = await this.getExcelData({ batch_id: id });
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
    pwdNumReplace(val){
      // 最小值 1，最大值 20，禁止输入负数和0
      // this.addForm.pwd_num = val.replace(/^0/, "1");
    }
  },
};
</script>

<style lang="less" scoped>
.w-433 {
  width: 433px;
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
