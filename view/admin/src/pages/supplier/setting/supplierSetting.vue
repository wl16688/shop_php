<template>
  <!-- 供应商设置 -->
  <from-submit
    :validate="ruleValidate"
    :url="url"
    :title="title"
    :rules="rules"
  />
</template>

<script>
import fromSubmit from "@/components/fromSubmit/fromSubmit.vue";
import buildData from "@/pages/setting/shop/buildData";

export default {
  name: "supplierSetting",
  components: { fromSubmit },
  mixins: [buildData],
  data() {
    return {
      ruleValidate: {},
      rules: [],
      url: "",
      title: "页面设置",
      type: "supplier",
    };
  },
};
</script>

<style scoped></style>
