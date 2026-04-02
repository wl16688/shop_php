<template>
  <!-- 评论设置 -->
      <from-submit :validate="ruleValidate" :url="url" :title="title" :rules="rules"/>
  </template>
  
  <script>
      import fromSubmit from '@/components/fromSubmit/fromSubmit.vue';
    import buildData from "@/pages/setting/shop/buildData";
  
      export default {
          name: "reply",
          components:{fromSubmit},
          mixins:[buildData],
          data() {
              return {
                  ruleValidate: {},
                  rules: [],
                  url: '',
                  title:'评论设置',
                  type: 'reply'
              };
          },
      }
  </script>
  
  <style scoped>
  
  </style>