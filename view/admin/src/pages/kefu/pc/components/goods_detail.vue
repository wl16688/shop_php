<template>
  <div class="goods_detail">
    <div class="goods_detail_wrapper" :class="product?'on':''">
      <iframe
        :src="`${url}pages/goods_details/index?id=${goodsId}`"
        style="width: 100%; height: 100%; border: none;"
      ></iframe>
      <!-- 添加透明遮罩层，确保覆盖在iframe之上，但允许滚动 -->
      <div class="overlay-mask"  @click.prevent></div>
    </div>
  </div>
</template>

<script>
import { HappyScroll } from "vue-happy-scroll";
import { productInfo } from "@/api/kefu";
import { productInfoApi } from "@/api/product";
import Setting from '@/setting';
export default {
  name: "goods_detail",
  props: {
    goodsId: {
      type: String | Number,
      default: "",
    },
    product: {
      type: String | Number,
      default: "",
    }
  },
  components: {
    HappyScroll,
  },
  data() {
    return {
      url: Setting.apiBaseURL.replace(/adminapi/, ''),
      value2: 0,
      goodsInfo: {},
    };
  },
  mounted() {
    // if(this.product){
    //   this.getInfoApi();
    // }else {
    //   this.getInfo();
    // }
  },
  methods: {
    getInfo() {
      productInfo(this.goodsId).then((res) => {
        this.goodsInfo = res.data;
      });
    },
    getInfoApi() {
      productInfoApi(this.goodsId)
         .then(async (res) => {
           this.goodsInfo = res.data.productInfo;
         })
         .catch((res) => {
           this.$Message.error(res.msg);
         });
    },
  },
};
</script>

<style lang="less" scoped>
.goods_detail {
  .goods_detail_wrapper {
    z-index: 200;
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 375px;
    height: 640px;
    background: #F0F2F5;
    &.on{
      position: fixed;
    }
    
    // 添加遮罩层样式
    .overlay-mask {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: transparent;
      z-index: 999;
      // 使用pointer-events: none让滚动事件穿透到iframe
      pointer-events: none;
    }
  }
}
</style>