<template>
  <!-- 版本管理 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt">
      <Row type="flex" class="mb20">
        <Col span="24">
          <Button type="primary" @click="add" class="mr10">发布版本</Button>
        </Col>
      </Row>
      <Table
        :columns="columns1"
        :data="data1"
        :loading="loading"
        highlight-row
        no-userFrom-text="暂无数据"
        no-filtered-userFrom-text="暂无筛选结果"
      >
        <template slot-scope="{ row }" slot="version">
          <span v-if="row.is_new">最新版本</span>
          <span>{{ row.version }}</span>
        </template>
        <template slot-scope="{ row }" slot="platform">
          <span>{{ row.platform === 1 ? "安卓" : "苹果" }}</span>
        </template>
        <template slot-scope="{ row }" slot="is_force">
          <span>{{ row.is_force === 1 ? "强制" : "非强制" }}</span>
        </template>
        <template slot-scope="{ row }" slot="add_time">
          <span> {{ row.add_time | formatDate }}</span>
        </template>

        <template slot-scope="{ row, index }" slot="action">
          <a @click="edit(row)">编辑</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除版本', index)">删除</a>
        </template>
      </Table>
      <div class="acea-row row-right page">
        <Page
          :total="total"
          show-elevator
          show-total
          @on-change="pageChange"
          :page-size="tableOptions.limit"
        />
      </div>
    </Card>
  </div>
</template>

<script>
import { mapState } from "vuex";
import { versionList, versionCrate } from "@/api/system";

export default {
  name: "versionList",
  data() {
    return {
      columns1: [
        {
          title: "版本号",
          slot: "version",
          width: 120,
        },
        {
          title: "平台类型",
          slot: "platform",
          minWidth: 120,
        },
        {
          title: "升级信息",
          key: "info",
          minWidth: 60,
        },
        {
          title: "是否强制",
          slot: "is_force",
          minWidth: 120,
        },
        {
          title: "发布日期",
          key: "add_time",
          minWidth: 120,
        },
        {
          title: "下载地址",
          key: "url",
          minWidth: 120,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          minWidth: 150,
        },
      ],
      data1: [],
      total: 0,
      tableOptions: {
        page: 1,
        limit: 15,
      },
      loading: false,
    };
  },
  created() {
    this.getList();
  },
  methods: {
    // 版本列表
    getList() {
      this.loading = true;
      versionList(this.tableOptions)
        .then((res) => {
          this.data1 = res.data.list;
          this.total = res.data.count;
          this.loading = false;
        })
        .catch((err) => {
          this.loading = false;
          this.$Message.error(err.msg);
        });
    },
    // 添加版本
    add() {
      this.$modalForm(versionCrate(0)).then(() => this.getList());
    },
    // 编辑
    edit(row) {
      this.$modalForm(versionCrate(row.id)).then(() => this.getList());
    },
    // 删除
    del(row, tit, num) {
      let delfromData = {
        title: tit,
        num: num,
        url: `/app_version/version_del/${row.id}`,
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
    pageChange(index) {
      this.tableOptions.page = index;
      this.getList();
    },
  },
};
</script>

<style lang="less" scoped>
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
</style>
