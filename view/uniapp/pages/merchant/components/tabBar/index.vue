<template>
	<!-- 底部导航 -->
	<keep-alive>
		<view class="page-footer">
			<view class="foot-item" :class="item.pagePath == activeRouter?'active':''"
				v-for="(item,index) in footerList" :key="index" @click="goRouter(item)">
				<block v-if="item.pagePath == activeRouter">
					<image :src="item.selectedIconPath"></image>
					<view class="txt">{{item.text}}</view>
				</block>
				<block v-else>
					<image :src="item.iconPath"></image>
					<view class="txt">{{item.text}}</view>
				</block>
				<uni-badge v-if="index == 1 && cartNum > 0" class="badge-style" :text="cartNum" absolute="rightTop"></uni-badge>
			</view>
		</view>
	</keep-alive>
</template>

<script>
	import { mapGetters } from 'vuex';
	import {getCartCounts} from '@/api/order.js';
	export default {
		name: 'footer',
		props: {},
		created() {
			let routes = getCurrentPages(); //获取当前打开过的页面路由数组
			let curRoute = routes[routes.length - 1].route //获取当前页面路由
			this.activeRouter = '/' + curRoute
		},
		computed: {
			...mapGetters(['isLogin', 'cartNum']),
			styleType(){
				return this.$store.state.app.system_channel_style;
			}
		},
		data() {
			return {
				activeRouter:'',
				footerList:[
					{
						pagePath: "/pages/merchant/index/goods",
						iconPath: require("../../static/1-0.png"),
						selectedIconPath: require("../../static/1-1.png"),
						text: "首页"
					},
					{
						pagePath: "/pages/merchant/cart/index",
						iconPath: require("../../static/2-0.png"),
						selectedIconPath: require("../../static/2-1.png"),
						text: "购物车"
					},
					{
						pagePath: "/pages/merchant/user/index",
						iconPath: require("../../static/3-0.png"),
						selectedIconPath: require("../../static/3-1.png"),
						text: "我的"
					}
				],
				// cartNum:0
			}
		},
		mounted() {
			this.getCartNum();
		},
		methods: {
			goRouter(item) {
				var pages = getCurrentPages();
				var page = (pages[pages.length - 1]).$page.fullPath;
				if (item.pagePath == page) return

				uni.redirectTo({
				  url: item.pagePath,
				  animationType: 'none' // 关闭默认的滑动效果
				});
			},
			getCartNum() {
				this.$store.dispatch('indexData/getCartNum')
			},
		}
	}
</script>

<style scoped lang="scss">
	.page-footer {
		position: fixed;
		bottom: 0;
		left:0;
		z-index: 1000;
		display: flex;
		align-items: center;
		justify-content: space-around;
		width: 100%;
		height: calc(100rpx + constant(safe-area-inset-bottom)); ///兼容 IOS<11.2/
		height: calc(100rpx + env(safe-area-inset-bottom)); ///兼容 IOS>11.2/
		box-sizing: border-box;
		border-top: solid 1rpx #F3F3F3;
		background-color: #fff;
		padding-bottom: constant(safe-area-inset-bottom); ///兼容 IOS<11.2/
		padding-bottom: env(safe-area-inset-bottom); ///兼容 IOS>11.2/

		.foot-item {
			display: flex;
			width: max-content;
			align-items: center;
			justify-content: center;
			flex-direction: column;
			position: relative;
			padding: 0 20rpx;
			&.active {
				color: $primary-merchant;
			}
		}

		.foot-item image {
			height: 40rpx;
			width: 40rpx;
			text-align: center;
			margin: 0 auto;
		}

		.foot-item .txt {
			font-size: 20rpx;
			margin-top: 6rpx;
		}
	}
</style>
