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

      activeTab: this.$route.path,
    };
  },
  computed: {
    tabList(){
      if(this.dataType == 1){
        return [
          { label: "客户列表", name: "/admin/work/client/list" },
          { label: "企微渠道码", name: "/admin/work/channel_code" },
          { label: "欢迎语", name: "/admin/work/welcome" },
          { label: "客户群发", name: "/admin/work/client/group" },
          { label: "朋友圈列表", name: "/admin/work/client/moment" },
        ]
      }else{
        return [
          { label: "客户群列表", name: "/admin/work/client/group_chat" },
          { label: "自动拉群", name: "/admin/work/auth_group" },
          { label: "客户群群发", name: "/admin/work/group/template" },
        ]
      }
    }
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