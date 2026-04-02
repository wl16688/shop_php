<template>
	<view>
		<view class="w-full bg-top relative" :style="{backgroundImage:headerBg}">
			<NavBar titleText="个人中心"
				textSize="34rpx"
				:isScrolling="pageScrollStatus"
				:showBack="false"
				textColor="#333333"></NavBar>
			<view class="pt-40 pl-32 pr-36 flex-between-center">
				<view class="flex-y-center">
					<image class="w-112 h-112 rd-50-p111-" :src="userInfo.avatar"></image>
					<view class="flex-col pl-24">
						<text class='fs-32 fw-500 lh-44rpx'>{{userInfo.nickname}}</text>
						<view class="flex-y-center">
							<text class="fs-24 pr-14">ID:{{userInfo.uid}}</text>
							<view class="h-42 flex-center fs-22 pl-16 pr-14 rd-30rpx b-c" @tap="checkIdentity">普通用户
								<text class="iconfont icon-ic_rightarrow fs-22"></text>
							</view>
						</view>
					</view>
				</view>
				<view class="relative">
					<image class="w-40 h-40" src="../static/mer_set_icon.png" @tap="intoPage('/pages/users/message_center/index')"></image>
					<text v-if="userInfo.service_num" class="number-badge">{{ userInfo.service_num >= 100 ? '99+' : userInfo.service_num }}</text>
				</view>
			</view>
			<view class="flex-between-center relative z-10 money-box">
				<view class="flex-y-center">
					<text class="fs-26 text--w111-999">余额</text>
					<text class="fs-28 text--w111-333 fw-600 Regular pl-8">{{ userInfo.now_money }}</text>
				</view>
				<view class="flex-y-center">
					<text class="fs-26 text--w111-999">消费</text>
					<text class="fs-28 text--w111-333 fw-600 Regular pl-8">{{ userInfo.channel_pay_price }}</text>
				</view>
				<view class="flex-y-center">
					<text class="fs-26 text--w111-999">采购节省</text>
					<text class="fs-28 text--w111-333 fw-600 Regular pl-8">{{ userInfo.channel_discount_price }}</text>
				</view>
			</view>
		</view>
		
		<view class="check-card flex-between-center px-28" :style="{backgroundImage:merCard}">
			<view class='flex-y-center'>
				<image src="../static/channel_icon.png" class="w-40 h-40"></image>
				<text class="fs-32 lh-44rpx fw-500 text--w111-F0E0C4 pl-12">{{ userInfo.channel_iden_name || '采购商身份' }}</text>
				<view class="mer-tag fs-18 flex-center">{{userInfo.channel_discount}}折</view>
			</view>
			<!-- <view class="w-140 h-50 check-btn rd-30rpx flex-center fs-20" @tap="checkIdentity">普通用户
			<text class="iconfont icon-ic_rightarrow fs-18"></text> </view> -->
		</view>
		<view class="px-20">
			<view class="w-full bg--w111-fff rd-20rpx mt-20 pt-32">
				<view class="flex-between-center px-24">
					<text class="fs-30 fw-500 lh-42rpx">订单中心</text>
					<view class="flex-y-center" @tap="intoPage('/pages/goods/order_list/index')">
						<text class="fs-26 text--w111-999">查看全部</text>
						<text class="iconfont icon-ic_rightarrow fs-24"></text>
					</view>
				</view>
				<view class="acea-row section-content">
					<view v-for="(item,index) in orderMenu" :key="index" class="item"
						@click="intoPage(item.url)">
						<view class="icon"><text :class="item.icon" class="iconfont"></text></view>
						<view class="">{{ item.title }}</view>
						<uni-badge class="uni-badge-left-margin" v-if="item.num > 0" :text="item.num"></uni-badge>
					</view>
					<view class="w-full h-120 rd-16rpx bg--w111-f5f5f5 mt-32 p-10 flex-between-center" v-if="notPayOrder"
						@click="intoPage('/pages/goods/order_list/index?status=0')">
						<view class="flex-y-center">
							<image :src="notPayOrder.img" class="w-100 h-100 rd-12rpx"></image>
							<view class="ml-16">
								<view class="fs-24 lh-34rpx text--w111-333 fw-500">等待付款</view>
								<view class="fs-22 lh-30rpx text--w111-333 mt-12 flex-y-center">
									还剩
									<countDown
										:is-day="false"
										tip-text=" "
										day-text=" "
										hour-text=":"
										minute-text=":"
										second-text=" "
										:datatime="notPayOrder.stop_time"
										bgColor="#F5F5F5"
										colors="var(--view-theme)"
										dotColor="var(--view-theme)"
										@endTime="getMenuData"
									></countDown>
									订单自动关闭
								</view>
							</view>
						</view>
						<view class="w-136 h-56 rd-30rpx flex-center fs-24 fw-500 text-primary-con con_border mr-14">去支付</view>
					</view>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-20rpx mt-20 py-32">
				<view class="fs-30 fw-500 lh-42rpx pl-24">我的服务</view>
				<view class="mt-40 px-24">
					<view class="flex-between-center mer-cell" @tap="intoPage('/pages/users/user_invoice_list/index')">
						<view class="flex-y-center">
							<image class="w-40 h-40" src="../static/fapiao_icon.png"></image>
							<text class="fs-28 lh-40rpx pl-24">发票管理</text>
						</view>
						<text class="iconfont icon-ic_rightarrow fs-28"></text>
					</view>
					<view class="flex-between-center mer-cell" @tap="intoPage('/pages/users/user_address_list/index')">
						<view class="flex-y-center">
							<image class="w-40 h-40" src="../static/address_icon.png"></image>
							<text class="fs-28 lh-40rpx pl-24">地址管理</text>
						</view>
						<text class="iconfont icon-ic_rightarrow fs-28"></text>
					</view>
					 <!-- #ifdef H5 || APP-PLUS -->
					 <view class="flex-between-center mer-cell" @tap="intoPage('/pages/extension/customer_list/chat')">
					 	<view class="flex-y-center">
					 		<image class="w-40 h-40" src="../static/kefu_icon.png"></image>
					 		<text class="fs-28 lh-40rpx pl-24">联系客服</text>
					 	</view>
					 	<text class="iconfont icon-ic_rightarrow fs-28"></text>
					 </view>
					 <!-- #endif -->
					 <!-- #ifdef MP-WEIXIN -->
					 <button open-type='contact' class="flex-between-center mer-cell" v-if="routineContact">
					 	<view class="flex-y-center">
					 		<image class="w-40 h-40" src="../static/kefu_icon.png"></image>
					 		<text class="fs-28 lh-40rpx pl-24">联系客服</text>
					 	</view>
					 	<text class="iconfont icon-ic_rightarrow fs-28"></text>
					 </button>
					 <view class="flex-between-center mer-cell" @tap="intoPage('/pages/extension/customer_list/chat')" v-else>
					 	<view class="flex-y-center">
					 		<image class="w-40 h-40" src="../static/kefu_icon.png"></image>
					 		<text class="fs-28 lh-40rpx pl-24">联系客服</text>
					 	</view>
					 	<text class="iconfont icon-ic_rightarrow fs-28"></text>
					 </view>
					 <!-- #endif -->

				</view>
			</view>
		</view>
		<view class='h-200'></view>
		<tab-bar></tab-bar>
	</view>
</template>

<script>
	import NavBar from "@/components/NavBar.vue";
	import countDown from '@/components/countDown';
	import tabBar from "../components/tabBar/index.vue";
	import { checkIdentityApi, getUserInfo } from "@/api/user.js";
	import { toLogin } from '@/libs/login.js';
	import { mapState, mapGetters } from 'vuex';
	import { HTTP_REQUEST_URL } from "@/config/app.js"
	export default {
		data() {
			return {
				pageScrollStatus: false,
				orderMenu:[
					{
						icon: 'icon-ic_daifukuan12',
						title: '待付款',
						url: '/pages/goods/order_list/index?status=0'
					},
					{
						icon: 'icon-ic_daifahuo11',
						title: '待发货',
						url: '/pages/goods/order_list/index?status=1'
					},
					{
						icon: 'icon-ic_daishouhuo1',
						title: '待收货',
						url: '/pages/goods/order_list/index?status=2'
					},
					{
						icon: 'icon-ic_daipingjia1',
						title: '待评价',
						url: '/pages/goods/order_list/index?status=3'
					},
					{
						icon: 'icon-ic_daituikuan1',
						title: '售后/退款',
						url: '/pages/users/user_return_list/index'
					}
				],
				notPayOrder: false,
				userInfo:{}
			}
		},
		components:{ NavBar, countDown, tabBar },
		computed:{
			...mapGetters({isLogin: 'isLogin'}),
			headerBg(){
				return 'url('+ HTTP_REQUEST_URL +'/statics/images/activity/mer_user_bg.png'+')'
			},
			merCard(){
				return 'url('+ HTTP_REQUEST_URL +'/statics/images/activity/merchant_user_card.png'+')'
			},
			routineContact(){
				return false
			}
		},
		onPageScroll(object) {
			if (object.scrollTop > 40) {
				this.pageScrollStatus = true;
			} else if (object.scrollTop < 40) {
				this.pageScrollStatus = false;
			}
			uni.$emit('scroll');
		},
		onLoad() {
			this.getInfo();
		},
		methods: {
			getInfo(){
				getUserInfo().then((res) => {
					this.userInfo = res.data;
					this.orderMenu.forEach((item, index) => {
						switch (item.title) {
							case '待付款':
								this.$set(item, 'num', res.data.orderStatusNum.unpaid_count);
								break;
							case '待发货':
								this.$set(item, 'num', res.data.orderStatusNum.unshipped_count);
								break;
							case '待收货':
								this.$set(item, 'num', res.data.orderStatusNum.received_count);
								break;
							case '待评价':
								this.$set(item, 'num', res.data.orderStatusNum.evaluated_count);
								break;
							case '售后/退款':
								this.$set(item, 'num', res.data.orderStatusNum.refunding_count);
								break;
						}
					});
					this.$store.commit('UPDATE_USERINFO', res.data);
					let identity = res.data.identity;
					if(identity != 1 && this.isLogin){
						// uni.switchTab({
						// 	url: "/pages/index/index"
						// })
					}
				});
			},
			checkIdentity(){
				checkIdentityApi({identity: 0}).then(res=>{
					getUserInfo().then((res) => {
						this.$store.commit('UPDATE_USERINFO', res.data);
						uni.switchTab({
							url: '/pages/user/index'
						})
					});
				}).catch(err=>{
					return this.$util.Tips({
						title: err
					});
				})
			},
			// 查看订单
			intoPage(url) {
				if (this.isLogin) {
					this.$util.JumpPath(url);
				} else {
					toLogin();
				}
			},
		}
	}
</script>

<style scoped lang="scss">
.bg-top{
	/* #ifdef H5 */
	background-size: 100% 386rpx;
	/* #endif */
	/* #ifdef MP-WEIXIN || APP-PLUS */
	background-size: 100% 466rpx;
	/* #endif */
	background-repeat: no-repeat;
}
.number-badge {
	position: absolute;
	top: -8rpx;
	right: 0;
	min-width: 10rpx;
	height: 24rpx;
	padding: 0 6rpx;
	border: 2rpx solid $primary-merchant;
	border-radius: 12rpx;
	background-color: #ffffff;
	transform: translateX(50%);
	font-weight: 500;
	font-size: 18rpx;
	line-height: 24rpx;
	color: $primary-merchant;
}
.check-card{
	position: relative;
	z-index: 10;
	width: 710rpx;
	height: 120rpx;
	background-size: cover;
	border-radius: 22rpx;
	margin: -48rpx 20rpx 0;
}
.text--w111-F0E0C4{
	color: #F0E0C4;
}
.money-box{
	padding: 0 78rpx 76rpx 64rpx;
	margin-top: 40rpx;
}
.mer-tag{
	width: 60rpx;
	height: 30rpx;
	background: #FEF0D9;
	border-radius: 20rpx;
	border: 1rpx solid #FACC7D;
	margin-left: 8rpx;
	color: #B68229;
}
.check-btn{
	// border: 1rpx solid #BFB7A8;
	color: #F7F0D4;
}
.section-content {
	padding: 48rpx 0 36rpx;

	.item {
		position: relative;
		flex: 1;
		text-align: center;
		font-size: 26rpx;
		line-height: 36rpx;
		color: #333333;
		.uni-badge-left-margin {
			position: absolute;
			top: -20rpx;
			right: 26rpx;
			/deep/ .uni-badge--error {
				background-color: $primary-merchant !important;
			}
			.uni-badge {
				color: $primary-merchant;
				border: 1px solid $primary-merchant;
				z-index: 29;
			}
		}
	}

	.icon {
		margin-bottom: 18rpx;
	}

	.iconfont {
		font-size: 48rpx;
	}
	.con_border {
		color: $primary-merchant;
		border: 1px solid $primary-merchant;
	}
}
.mer-cell ~ .mer-cell{
	margin-top: 56rpx;
}
.b-c{
	border: 1rpx solid #ccc;
}
</style>
