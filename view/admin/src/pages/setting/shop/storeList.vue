<template>
  <div>
    <!-- <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <div class="new_card_pd"></div>
    </Card> -->
    <Card :bordered="false" dis-hover class="ivu-mt tablebox" :padding="16">
      <div class="new_tab">
        <div class="table">
          <Button type="primary" @click="addStore">添加自提点</Button>
          <Table
            :columns="columns"
            :data="dataList"
            ref="table"
            class="ivu-mt"
            :loading="loading"
            highlight-row
            no-userFrom-text="暂无数据"
            no-filtered-userFrom-text="暂无筛选结果"
          >
            <template slot-scope="{ row }" slot="image">
              <img :src="row.image" class="w-40" />
            </template>
            <template slot-scope="{ row }" slot="is_show">
              <i-switch v-model="row.is_show" :true-value="1" :false-value="0"
                @on-change="onchangeIsShow(row)" size="large">
                <span slot="open">开启</span>
                <span slot="close">关闭</span>
              </i-switch>
            </template>
            <template slot-scope="{ row, index }" slot="action">
              <a @click="getEdit(row)">编辑</a>
              <Divider type="vertical" />
              <a @click="deleteStore(row,'删除自提点', index)">删除</a>
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
        </div>
      </div>
    </Card>
    <system-store ref="storeModal"></system-store>
  </div>
</template>
<script>
import { mapState } from "vuex";
import systemStore from '@/components/systemStore/index';
import { storeListApi, storeChangeStatusApi } from "@/api/store";
export default {
  name: "storeList",
  components: { systemStore },
  data() {
    return {
      loading: false,
      columns: [
        { title: "ID", key: "id", width: 80 },
        { title: "自提点图片", slot: "image", minWidth: 80 },
        { title: "自提点名称", key: "name", minWidth: 150 },
        { title: "联系电话", key: "phone", minWidth: 90 },
        { title: "自提点地址", key: "address", ellipsis: true, minWidth: 150 },
        { title: "营业时间", key: "day_time", minWidth: 120 },
        { title: "状态", slot: "is_show", minWidth: 80 },
        { title: "操作", slot: "action", minWidth: 120 },
      ],
      dataList: [],
      total: 0,
      formValidate: {
        page: 1,
        limit: 15,
      },
      modals: true,
      addForm: {
        name: "",
        phone: "",
        status: 1,
        image: "",
        is_show: 1,
        city: 0,
        day_time: [],
        detailed_address: "",
        latitude: "",
        longitude: "",
      },
      addressData: [],
      ruleValidate: {},
      storeAddress: "",
      addresData: [],
      isEdit: false,
      mapKey: "",
    };
  },
  mounted() {
    this.getList();
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
  methods: {
    getList() {
      this.loading = true;
      storeListApi(this.formValidate)
        .then((response) => {
          this.dataList = response.data.list;
          this.total = response.data.count;
          this.loading = false;
        })
        .catch((error) => {
          console.error(error);
          this.loading = false;
        });
    },
    getEdit(row) {
      this.$refs.storeModal.isTemplate = true;
      this.$refs.storeModal.getInfo(row.id);
    },
    addStore() {
      this.$refs.storeModal.isTemplate = true;
    },
    pageChange(val) {
      this.formValidate.page = val;
      this.getList();
    },
    onchangeIsShow(row){
      storeChangeStatusApi(row.id, row.is_show).then(res=>{
        this.$Message.success(res.msg);
        this.formValidate.page = 1;
        this.getList();
      }).catch(err=>{
        this.$Message.error(err.msg)
      })
    },
    deleteStore(row, tit, num) {
      let delfromData = {
        title: tit,
        method: "DELETE",
        uid: row.uid,
        url: `store/store/del/${row.id}`,
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.formValidate.page = 1;
          this.getList();
        })
        .catch((res) => {
          this.$Message.error(res.msg);
        });
    },
  },
};
</script>
<style>
/* .w-full {
  width: 460px;
} */
.map-sty {
  width: 90%;
  text-align: right;
  margin: 0 0 0 10%;
}
</style>
