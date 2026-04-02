<template>
  <div>
    <Tabs v-model="activeTab" :animated="false" @on-click="onClickTab">
        <TabPane v-for="(item, index) in tabList" :key="index" v-bind="item" />
      </Tabs>
  </div>
</template>
<script>
import { mapState } from "vuex";
export default {
  name: "",
  props:{
    dataType: {
      type: Number,
      default: 1,
    }
  },
  data() {
    return {
      tabList: [
        { label: "商品品牌", name: "/admin/product/product_brand" },
        { label: "商品参数", name: "/admin/product/specs" },
        { label: "商品单位", name: "/admin/product/unitList" },
        { label: "保障服务", name: "/admin/product/ensure" },
      ],
      activeTab: this.$route.path,
    };
  },
  methods: {
    onClickTab(name) {
      if(name == this.$route.path) return
      this.$router.push(name);
    },
  },
};
</script>
<style lang="less" scoped>
/deep/ .ivu-tabs-nav .ivu-tabs-tab {
  padding: 4px 16px 10px !important;
}
/deep/.ivu-tabs-bar{
  border: unset;
}
</style>