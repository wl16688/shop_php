<template>
	<view class="container">
		<view class="bg-card" :style="{backgroundImage:headerBg}"></view>
		<view class="bg--w111-fff rd-24rpx form-card">
			<!-- <view class="fs-30 fw-500 lh-42rpx text-center">卡券兑换</view> -->
			<view class="w-full bg--w111-f9f9f9 rd-12rpx pl-24 mt-38">
				<input type="text" v-model="cardForm.card_number" placeholder="请输入卡号"
					placeholder-class="fs-30 text--w111-b3b3b3" class="w-full h-88 fs-30 lh-42rpx" />
			</view>
			<view class="w-full bg--w111-f9f9f9 rd-12rpx pl-24 mt-32">
				<input type="text" v-model="cardForm.card_pwd" placeholder="请输入密码"
					placeholder-class="fs-30 text--w111-b3b3b3" class="w-full h-88 fs-30 lh-42rpx" />
			</view>
			<button class="w-full h-88 rd-44rpx flex-center text--w111-fff bg-btn fs-28 mt-54" :disabled="disabled" @tap="exchangeConfirm">立即验证</button>
		</view>
	</view>
</template>
<script>
import { giftCardGetInfoApi } from "@/api/activity.js";
import { HTTP_REQUEST_URL } from '@/config/app';
import { mapState, mapGetters } from 'vuex';
import { toLogin } from '@/libs/login.js';
export default {
	name: "",
	data(){
		return {
			cardForm:{
				card_number:"",
				card_pwd: ""
			},
			disabled: false
		}
	},
	computed:{
		 ...mapGetters(['isLogin']),
		headerBg(){
			return 'url('+ HTTP_REQUEST_URL +'/statics/images/activity/gift_bg.png'+')'
		}
	},
	onLoad() {

	},
	methods:{
		exchangeConfirm(){
			if(this.isLogin){
				giftCardGetInfoApi(this.cardForm).then(res=>{
					this.disabled = false;
					uni.navigateTo({
						url: "/pages/activity/giftCard/info?card_number=" + this.cardForm.card_number + '&card_pwd=' + this.cardForm.card_pwd
					})
				}).catch(err=>{
					this.disabled = false;
					return this.$util.Tips({
						title: err
					})
				})
			}else{
				toLogin();
			}
			
		}
	}
}
</script>
<style>
.form-card uni-button[disabled] {
  background: #cccccc;
  color: #fff;
}
.container{
	height: 100vh;
	background: #FFEED8;
}
.bg-card{
	height: 648rpx;
	background-size: 100% 648rpx;
	background-repeat: no-repeat;
}
.form-card{
	width: 670rpx;
	height: 532rpx;
	position: fixed;
	top: 392rpx;
	left: 42rpx;
	z-index: 10;
	padding: 40rpx 40rpx 60rpx;
}
.bg-btn{
	background: linear-gradient( 90deg, #FF7931 0%, #E93323 100%);
}
</style>
