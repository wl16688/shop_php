<template>
  <Table
    ref="table"
    :columns="columns"
    :data="tableList"
    class="ivu-mt"
    no-data-text="暂无数据"
    no-filtered-data-text="暂无筛选结果"
    @on-selection-change="onSelectTab"
  >
    <template slot-scope="{ row }" slot="topicName">
      <Tooltip
        theme="dark"
        max-width="300"
        :content="row.topic_name"
        :delay="600"
        :transfer="true"
      >
        <div class="title line2">{{ row.topic_name }}</div>
      </Tooltip>
    </template>
    <template slot-scope="{ row, index }" slot="action">
      <a @click="edit(row, index)">编辑</a>
    </template>
  </Table>
</template>

<script>
export default {
  props: {
    tableList: {
      type: Array,
      default: () => [],
    },
  },
  data() {
    return {
      aiTableList: [],
      columns: [
        {
          type: "selection",
          width: 100,
          align: "center",
        },
        {
          title: "标题",
          key: "title",
          minWidth: 150,
        },
        {
          title: "内容",
          key: "content",
          minWidth: 150,
        },
        {
          title: "话题",
          slot: "topicName",
          minWidth: 150,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          width: 90,
        },
      ],
    };
  },
  methods: {
    // 全选
    onSelectTab(selection) {
      this.$emit("onSelectData", selection);
    },
    edit(row, index) {
      this.$emit("onEditAiData", row, index);
    },
  },
};
</script>
<style scoped lang="less">
.ivu-mt .imgPic .info {
  width: 60%;
  margin-left: 10px;
}

.ivu-mt .picList .pictrue {
  height: 36px;
  margin: 7px 3px 0 3px;
}

.ivu-mt .picList .pictrue img {
  height: 100%;
  display: block;
}

.product-data {
  display: flex;
  align-items: center;

  .image {
    width: 50px !important;
    height: 50px !important;
    margin-right: 10px;
  }
}
</style>
