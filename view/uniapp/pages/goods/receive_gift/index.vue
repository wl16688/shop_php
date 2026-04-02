<template>
	<view class="warpper">
		<image class="top-bg" src="../static/receive-gift-bag.png" mode=""></image>
		<image class="top-bow" src="../static/bow.png" mode=""></image>
		<view class="aleart">
			<view class="from">{{ nickname }} 送给您的一份礼物</view>
			<view class="message">{{ gift_mark }}</view>
			<view class="aleart-body">
				<view class="goods-img">
					<image class="img" :src="image" mode=""></image>
					<view class="expire" v-if="refund_status != 0 || giftStatus == 4">
						<image src="../static/expire.png" mode=""></image>
					</view>
				</view>
			</view>
			<view class="title line1">
				{{ store_name }}
			</view>
			<view v-if="cartInfo.length > 1" class="more text--w111-999" @click="showDrawer = true">
				全部{{ cartInfo.length }}件
				<text class="iconfont icon-ic_downarrow"></text>
			</view>
		</view>
		<view v-if="(giftStatus == 1 || giftStatus == 2) && refund_status == 0" class="btn" @click="goGetGift()">
			{{ giftStatusText }}
		</view>
		<view v-else-if="giftStatus == 3 && refund_status == 0" class="btn-n">礼物已被领取</view>
		<!-- <view v-else-if="refund_status != 0 || giftStatus == 4" class="btn-n">礼物已失效</view> -->
		<text v-if="(giftStatus == 1 || giftStatus == 2) && refund_status == 0" class="text--w111-999 mt-32 fs-28">礼物超过24小时未领取将失效</text>
		<text v-if="refund_status != 0 || giftStatus == 4" class="text--w111-999 mt-32 fs-28">礼物超过24小时未领取已失效</text>
		<base-drawer
			mode="bottom"
			:visible="showDrawer"
			background-color="transparent"
			mask
			maskClosable
			@close="
				() => {
					showDrawer = false;
				}
			"
		>
			<view class="w-full bg--w111-fff rd-t-40rpx py-32">
				<view class="text-center fs-32 text--w111-333 fw-500">礼品清单</view>
				<view class="mt-64 px-32">
					<view class="mb-38" v-for="(item, index) in cartInfo" :key="index">
						<view class="flex-y-center">
							<image class="w-160 h-160 mr-30 rd-16rpx" :src="item.productInfo.image"></image>
							<view class="flex-1 flex-col justify-between h-160">
								<text class="text--w111-333 fs-28 fw-400 line2">{{ item.productInfo.store_name }}</text>
								<view class="flex flex-between-center">
									<view class="mt-6 fs-22 text--w111-999">
										{{ item.productInfo.attrInfo.suk }}
									</view>
									<view class="mt-6 pl-40 fs-22 text--w111-999">
										x
										<text class="fs-26">{{ item.cart_num }}</text>
									</view>
								</view>
							</view>
						</view>
					</view>
				</view>
				<view class="mx-20 pb-safe">
					<view class="mt-52 h-72 flex-center rd-36px bg-red fs-26 text--w111-fff" @tap="showDrawer = false">确定</view>
				</view>
			</view>
		</base-drawer>
	</view>
</template>

<script>
import { getSendGiftOrderDetail } from '@/api/order.js';
import { toLogin } from '@/libs/login.js';
import { mapGetters } from 'vuex';
let app = getApp();
export default {
	computed: mapGetters(['isLogin']),
	data() {
		return {
			order_id: '',
			giftStatus: 0,
			refund_status: 0,
			gift_mark: '',
			image: '',
			store_name: '',
			nickname: '',
			giftStatusText: '',
			o_id: 0,
			showDrawer: false,
			cartInfo: []
		};
	},
	props: {},
	watch: {
		isLogin: {
			handler: function (newV, oldV) {
				if (newV) {
					this.getOrderInfo();
				}
			},
			deep: true
		}
	},
	onLoad(options) {
		if (this.isLogin) {
			this.order_id = options.id;
			// #ifdef MP
			if (options.scene) {
				let value = this.$util.getUrlParams(decodeURIComponent(options.scene));
				if (value.order_id) options.id = value.id;
				//记录推广人uid
				if (value.pid) app.globalData.spid = value.pid;
			}
			// #endif
			this.getOrderInfo();
		} else {
			toLogin();
		}
	},
	methods: {
		goGetGift() {
			if (this.giftStatus == 2) {
				uni.reLaunch({
					url: `/pages/goods/order_details/index?order_id=${this.o_id}`
				});
			} else {
				uni.reLaunch({
					url: `/pages/goods/order_confirm/index?order_id=${this.order_id}&is_send_gift=2`
				});
			}
		},
		getOrderInfo() {
			getSendGiftOrderDetail(this.order_id).then((res) => {
				this.cartInfo = res.data.cartInfo;
				this.gift_uid = res.data.receive_gift_uid;
				this.gift_mark = res.data.send_gift_mark;
				this.nickname = res.data.nickname;
				this.refund_status = res.data.refund_status;
				this.image = res.data.cartInfo[0].productInfo.image;
				this.store_name = res.data.cartInfo[0].productInfo.store_name;
				this.o_id = res.data.order_id;
				if (!res.data.receive_gift_time_valid) {
					this.giftStatus = 4;
					this.giftStatusText = '礼物已失效';
					return;
				}
				if (this.gift_uid === 0) {
					this.giftStatus = 1;
					this.giftStatusText = '收下礼物';
				} else if (this.gift_uid === this.$store.state.app.uid) {
					this.giftStatus = 2;
					this.giftStatusText = '查看礼物';
				} else if (this.gift_uid !== this.uid) {
					this.giftStatus = 3;
				}
			});
		}
	}
};
</script>

<style lang="scss" scoped>
.warpper {
	display: flex;
	flex-direction: column;
	align-items: center;
	.top-bg {
		width: 100%;
		height: 206rpx;
	}
	.top-bow {
		width: 342rpx;
		height: 78rpx;
		margin-top: -120rpx;
		z-index: 9;
	}
}
.more {
	background-color: #f5f5f5;
	border-radius: 8rpx;
	font-size: 26rpx;
	color: #999999;
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 8rpx 24rpx;
	margin-bottom: 44rpx;
	.iconfont {
		font-size: 26rpx;
		margin-left: 4rpx;
	}
}
.aleart {
	width: 670rpx;
	z-index: 7;
	margin-top: -20rpx;
	background-color: #fff;
	padding-top: 100rpx;
	border-radius: 24rpx;
	background-size: 100% 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;

	.from {
		font-size: 28rpx;
		font-weight: 500;
		color: #333333;
		margin-bottom: 16rpx;
	}
	.message {
		font-weight: 400;
		font-size: 26rpx;
		color: #999999;
		margin-bottom: 42rpx;
	}
	.aleart-body {
		display: flex;
		align-items: center;
		justify-content: center;
		width: 396rpx;
		height: 419rpx;
		background-image: url('/pages/goods/static/gift-border.png');
		background-size: 100% 100%;
		margin-bottom: 40rpx;

		.goods-img {
			width: 360rpx;
			height: 360rpx;
			margin-top: 24rpx;
			position: relative;
			.img {
				width: 100%;
				height: 100%;
			}
			.expire {
				position: absolute;
				left: 0;
				top: 0;
				width: 100%;
				height: 100%;
				background-color: rgba(255, 255, 255, 0.6);
				display: flex;
				align-items: center;
				justify-content: center;
				image {
					width: 236rpx;
					height: 236rpx;
					margin: auto;
				}
			}
		}
	}
	.title {
		width: 396rpx;
		font-weight: 400;
		font-size: 28rpx;
		color: #333333;
		margin-bottom: 34rpx;
		text-align: center;
	}
}
.btn,
.btn-clear,
.btn-n {
	width: 670rpx;
	height: 84rpx;
	line-height: 84rpx;
	border-radius: 20px;
	text-align: center;
	margin-top: 56rpx;
}
.btn {
	color: #fff;
	background: linear-gradient(90deg, #ff7931 0%, #e93323 100%);
}
.btn-clear {
	color: #e93323;
	border: 1px solid #e93323;
}
.btn-n {
	color: #fff;
	background: #cccccc;
}
</style>
