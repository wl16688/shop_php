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
    <template slot-scope="{ row }" slot="info">
      <div class="imgPic acea-row row-middle">
        <viewer>
          <div><img class="w-40 h-40 block" v-lazy="row.product_image" /></div>
        </viewer>
        <div class="info line2">{{ row.store_name }}</div>
      </div>
    </template>
    <template slot-scope="{ row }" slot="content">
      <div class="content_font">{{ row.comment }}</div>
      <viewer>
        <div class="flex mt5">
          <img
            class="w-40 h-40 block mr-10"
            v-for="(item, index) in row.pics || []"
            :key="index"
            v-lazy="item"
          />
        </div>
      </viewer>
    </template>
    <template slot-scope="{ row }" slot="reply_score">
      <span v-if="row.reply_score == 3">好评</span>
      <span v-else-if="row.reply_score == 2">中评</span>
      <span v-else>差评</span>
    </template>
    <template slot-scope="{ row }" slot="reply">
      <Tooltip max-width="200" placement="bottom">
        <span class="line2">{{
          row.replyComment ? row.replyComment.content : ""
        }}</span>
        <p slot="content">
          {{ row.replyComment ? row.replyComment.content : "" }}
        </p>
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
          title: "商品信息",
          slot: "info",
          minWidth: 150,
        },
        {
          title: "用户名称",
          key: "nickname",
          minWidth: 100,
        },
        {
          title: "商品评价",
          slot: "reply_score",
          minWidth: 90,
        },
        {
          title: "商品质量",
          key: "product_score",
          minWidth: 80,
        },
        {
          title: "服务态度",
          key: "service_score",
          minWidth: 80,
        },
        {
          title: "物流服务",
          key: "delivery_score",
          minWidth: 80,
        },
        {
          title: "评价内容",
          key: "comment",
          minWidth: 210,
        },
        {
          title: "评价时间",
          key: "add_time",
          sortable: true,
          minWidth: 150,
        },
        {
          title: "操作",
          slot: "action",
          fixed: "right",
          minWidth: 90,
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
      this.editIndex = index;
      this.$emit("editReply", row, index);
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
