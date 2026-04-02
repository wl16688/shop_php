<template>
<!-- 商品-商品单位 -->
  <div>
     <KeepAlive>
      <navTab></navTab>
    </KeepAlive>
    <Card :bordered="false" dis-hover :padding="0">
      <div class="new_card_pd">
        <!-- 筛选条件 -->
        <Form
          ref="unitFrom"
          inline
          :model="unitFrom"
          :label-width="75"
          label-position="right"
          @submit.native.prevent
        >
          <Row :gutter="24" type="flex" justify="end">
            <Col span="24">
              <FormItem label="单位搜索：">
                <Input
                  v-model="unitFrom.name"
                  placeholder="请输入单位名称"
                  class="input-add"
                ></Input>
                <Button @click="unitSearchs()" type="primary">查询</Button>
              </FormItem>
            </Col>
          </Row>
        </Form>
      </div>
    </Card>

    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 相关操作 -->
      <div>
        <Button type="primary" @click="add" >添加商品单位</Button>
      </div>
      <!-- 商品单位表格 -->
      <Table
        :columns="columns1"
        :data="list"
        ref="table"
        class="mt25"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row, index }" slot="icons">
          <viewer>
            <div class="tabBox_img">
              <img v-lazy="row.icon" />
            </div>
          </viewer>
        </template>
        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row.id)">编辑</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除单位', index)">删除</a>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="unitFrom.limit"
        />
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import { productUnit, productUnitCreate, productUnitEdit } from "@/api/product";
import navTab from "../components/navTab.vue"
export default {
  name: "user_group",
  components: { navTab },
  data() {
    return {
      loading: false,
      columns1: [
        {
          title: "ID",
          key: "id",
          width: 80,
        },
        {
          title: "单位名称",
          key: "name",
          minWidth: 100,
        },
        {
          title: "排序",
          key: "sort",
          minWidth: 100,
        },
        {
          title: "操作",
          slot: "action",
          // fixed: "right",
          minWidth: 120,
        },
      ],
      unitFrom: {
        page: 1,
        limit: 15,
        name: "",
      },
      list: [],
      total: 0,
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
    unitSearchs() {
      this.unitFrom.page = 1;
      this.list = [];
      this.getList();
    },
    // 添加
    add() {
      this.$modalForm(productUnitCreate(0)).then(() => this.getList());
    },
    // 单位列表
    getList() {
      this.loading = true;
      productUnit(this.unitFrom)
        .then((res) => {
          let data = res.data;
          this.list = data.list;
          this.total = data.count;
          this.loading = false;
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    pageChange(index) {
      this.unitFrom.page = index;
      this.getList();
    },
    //修改
    edit(id) {
      this.$modalForm(productUnitEdit(id)).then(() => this.getList());
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `product/unit/${row.id}`,
        method: "DELETE",
        ids: "",
      };
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg);
          this.list.splice(num, 1);
					if (!this.list.length) {
					  this.unitFrom.page =
					    this.unitFrom.page == 1 ? 1 : this.unitFrom.page - 1;
					}
					this.getList();
        })
        .catch((err) => {
          this.$Message.error(err.msg);
        });
    },
  },
};
</script>

<style scoped lang="stylus">
.input-add {
width: 250px;
margin-right:14px;
}
</style>