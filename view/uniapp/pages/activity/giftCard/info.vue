<template>
	<view>
		<view class="p-20">
			<view class="relative">
				<image class="w-full h-400 rd-24rpx block" mode="aspectFill" :src="cardInfo.cover_image"></image>
				<view class="gift-badge px-14 rd-40rpx flex-center z-10">
					<text class="iconfont icon-ic_card3 fs-32 text-yellow" v-show="cardInfo.type == 1"></text>
					<text class="iconfont icon-ic_gift2 fs-32 text-red" v-show="cardInfo.type == 2"></text>
					<text class="fs-24 pl-8" v-show="cardInfo.type == 1">{{cardInfo.balance}}元礼品卡</text>
					<text class="fs-24 pl-8" v-show="cardInfo.type == 2">兑换卡</text>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-16rpx px-24 mt-20" v-if="cardInfo.type == 2">
				<view class="flex-between-center pt-32">
					<view class="fs-28">可兑换商品
						<text class="fs-24 text--w111-999 pl-12" v-if="cardInfo.exchange_type == 1">该兑换卡可兑换以下商品</text>
						<text class="fs-24 text--w111-999 pl-12" v-else>任选 <text class="Regular text-red">{{ cardInfo.gift_num }}</text> 件兑换</text>
					</view>
					<text class="fs-28" v-if="cardInfo.exchange_type == 2">
						<text class="text-red">{{ totalLimitNum }}</text> /{{cardInfo.gift_num}}
					</text>
				</view>
				<view class="pb-32 product-box">
					<view class="mt-24 flex" v-for="(item, index) in cardInfo.product">
						<image class="block w-200 h-200 rd-16rpx" :src="item.image"></image>
						<view class="flex-1 h-200 flex-col justify-between pl-20">
							<view>
								<view class="w-444 line2 fs-28 h-80 lh-40rpx">{{ item.store_name }}</view>
								<view class="inline-block max-w-444 h-38 lh-38rpx mt-8  bg--w111-f5f5f5  text--w111-999 rd-20rpx px-12 fs-22 line1">
									 {{ item.suk }}
								</view>
							</view>
							<view class="flex-between-center">
								<baseMoney :money="item.price"
								symbolSize="24"
								integerSize="36"
								decimalSize="24"
								color="#e93323"
								weight></baseMoney>
								<text class='Regular fs-28' v-if="cardInfo.exchange_type == 1">x{{ item.limit_num }}</text>
								<view class="flex-y-center" v-else>
									<button class="w-44 h-44 flex-center"
										:disabled="item.limit_num <= 0"
										@click="decreaseLimitNum(index)">
										<text class="iconfont icon-ic_Reduce fs-24"></text>
									</button>
									<view class='w-88 h-44 text-center lh-44rpx bg--w111-f5f5f5'>{{ item.limit_num }}</view>
									<button class="w-44 h-44 flex-center"
										:disabled="totalLimitNum >= cardInfo.gift_num"
										@click="increaseLimitNum(index)">
										<text class="iconfont icon-ic_increase fs-24"></text>
									</button>
								</view>
							</view>
						</view>
					</view>
				</view>
			</view>
			<view class="w-full bg--w111-fff rd-16rpx p-24 mt-20 fs-28 lh-42rpx">
				<view class="pb-20" v-if="cardInfo.type == 1">储值金额<text class="fs-26 text--w111-666 pl-16">¥{{cardInfo.balance}}</text></view>
				<view>有效期至：<text class="fs-26 text--w111-666 pl-16">{{ cardInfo.valid_type == 1 ? '永久有效' : cardInfo.fixed_time[1] }}</text></view>

			</view>
			<view class="w-full bg--w111-fff rd-16rpx px-24 mt-20">
				<view class="fs-28 pt-32">使用须知</view>
				<view class="pt-12 fs-26 text--w111-666 lh-36rpx space-line pb-32">{{ cardInfo.instructions }}</view>
			</view>
			<view class="w-full bg--w111-fff rd-16rpx px-24 mt-20">
				<view class="fs-28 pt-32">礼品卡详情</view>
				<view class="pt-12 fs-26 text--w111-666 lh-36rpx space-line pb-32" v-html="cardInfo.description"></view>
			</view>
			<view class="h-200"></view>
		</view>
		<view class="fixed-lb w-full pb-safe bg--w111-fff z-99">
			<view class="w-full h-108 flex-between-center px-20">
				<button class="btn-box w-full h-72 rd-40rpx flex-center fs-26 text--w111-fff"
				:disabled="disabled"
				@tap="exchangeGift">确认兑换</button>
			</view>
		</view>
		<uni-popup ref="popup">
		    <view class="">
		        <view class="gift_pop bg--w111-fff relative">
					<image class="header-pic" :src="imgHost + '/statics/images/activity/gift_open_pic.png'"></image>
					<view class="success-text">
						<view class="text-center text-red fs-34 fw-500 lh-48rpx">成功到账</view>
						<view class="text-center fs-26 lh-36rpx pt-24">{{cardInfo.balance}}元已充值到您的账户中</view>
						<view class="flex-x-center mt-54">
							<view class="btn-box w-280 h-80 rd-40rpx flex-center fs-28 text--w111-fff" @tap="toMoney">去看看</view>
						</view>
					</view>
				</view>
				<view class="flex-center mt-94">
					<text class="iconfont icon-ic_close1 text--w111-fff fs-50" @tap="closeGift"></text>
				</view>
		    </view>
		</uni-popup>
	</view>
</template>
<script>
import { giftCardGetInfoApi, giftCardReceiveApi } from "@/api/activity.js";
import { HTTP_REQUEST_URL } from '@/config/app';
export default{
	name:"",
	data(){
		return {
			cardForm:{
				card_number:"",
				card_pwd: ""
			},
			cardInfo: {
				id: "", //自增ID
				name: "", //礼品卡名称
				fixed_time: [],
				type: 1, //礼品卡类型 1-储值卡 2-兑换卡
				batch_id: "", //关联卡密
				total_num: 1, //总数量
				instructions: "", //使用说明
				cover_image: "", //封面图
				valid_type: 1, //有效期类型 1-永久有效 2-固定时间
				status: 1, //礼品卡状态 0-禁用 1-启用
				balance: 0, //储值金额
				exchange_type: 1, //'兑换商品类型 1-固定商品打包 2-任选N件商品'
				gift_num: 1, //兑换商品数量
				description: "",
				product: [], //兑换商品
			},
			disabled: false,
			imgHost: HTTP_REQUEST_URL,
		}
	},
	computed: {
	    totalLimitNum() {
			return this.cardInfo.product.reduce((sum, item) => sum + item.limit_num, 0);
	    }
	  },
	onLoad(options) {
		this.cardForm.card_number = options.card_number;
		this.cardForm.card_pwd = options.card_pwd;
		if(options.card_number && options.card_pwd){
			this.getInfo();
		}
	},
	methods:{
		getInfo(){
			giftCardGetInfoApi(this.cardForm).then(res=>{
				this.cardInfo = res.data;
				this.cardInfo.description = res.data.description.replace(/<img/gi, '<img style="max-width: 100%;display:block;"');
				uni.setNavigationBarTitle({
					title: options.type == 1 ? '兑换礼金' : '兑换礼品'
				})
			}).catch(err=>{
				return this.$util.Tips({
					title: err
				})
			})
		},
		exchangeGift(){
		   if(this.cardInfo.exchange_type == 2){
			   if(this.totalLimitNum != this.cardInfo.gift_num) {
				    return this.$util.Tips({
						title: "请检查兑换数量"
					})
			   }
			   let product = this.cardInfo.product.
				filter(item => item.limit_num !== 0).map(item=>{
				   return {
						product_id: item.product_id,
					    limit_num: item.limit_num || 0,
					    unique: item.unique,
				   }
			   });
			   this.$set(this.cardForm,'product',product);
		   }
		    this.disabled = true;
			giftCardReceiveApi(this.cardForm).then(res=>{
				this.disabled = false;
				if(this.cardInfo.type == 1){
					this.$refs.popup.open();
				}else{
					let cardId = res.data.map(item=> item.cartId).join(",");
					uni.navigateTo({
						url: "/pages/goods/order_confirm/index?cartId=" + cardId + "&new=1"
					})
				}
			}).catch(err=>{
				this.disabled = false;
				return this.$util.Tips({
					title: err
				})
			})

		},
		toMoney(){
			this.$refs.popup.close();
			uni.redirectTo({
				url: "/pages/users/user_bill/index?type=2"
			})
		},
		closeGift(){
			this.$refs.popup.close();
		},
		increaseLimitNum(index) {
		    if (this.totalLimitNum < this.cardInfo.gift_num) {
		        this.cardInfo.product[index].limit_num += 1;
		    }
		},
		decreaseLimitNum(index) {
		    if (this.cardInfo.product[index].limit_num > 0) {
		        this.cardInfo.product[index].limit_num -= 1;
		    }
		}
	}
}
</script>
<style lang="scss">
.product-box uni-button[disabled], .product-box button[disabled]{
  background-color: #fff;
}
.fixed-lb uni-button[disabled], .fixed-lb button[disabled] {
  background: #cccccc;
  color: #fff;
}
.gift-badge{
	position: absolute;
	top: 24rpx;
	left: 24rpx;
	height: 46rpx;
	background: rgba(255,255,255,0.8);
	backdrop-filter: blur(8rpx)
}
.b-d{
	border: 1rpx solid #DDDDDD;
}
.btn-box{
	background: linear-gradient( 90deg, #E93323 0%, #FF7931 100%);
}
.max-w-444{
	max-width: 444rpx;
}
.gift_pop{
	width: 480rpx;
	height: 500rpx;
	border-radius: 48rpx;
	.header-pic{
		width: 480rpx;
		height: 364rpx;
		position: absolute;
		top: -72rpx;
		left: 0;
	}
	.success-text{
		position: absolute;
		top: 180rpx;
		left: 0;
		width: 480rpx;
		.text-red{
			color: #E93323;
		}
	}
}
.mt-94{
	margin-top: 94rpx;
}
.fs-50{
	font-size: 50rpx;
}
.text-red{
	color: #e93323;
}
.text-yellow{
	color: #FF7700;
}
</style>
