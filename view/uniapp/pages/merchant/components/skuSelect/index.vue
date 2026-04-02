<template>
	<base-drawer mode="bottom" :visible="attr.cartAttr" background-color="transparent" zIndex="3000" mask maskClosable
		@close="closeDrawer">
		<view>
			<view class="bg--w111-fff rd-t-40rpx">
			<view class="w-full pt-32">
				<view class="px-32 flex">
					<image class="w-180 h-180 rd-16rpx" :src="attr.productSelect.image" @tap="showImg"></image>
					<view class="pl-24">
						<baseMoney :money="attr.productSelect.channel_price" symbolSize="32" integerSize="48"
							decimalSize="32" color="#FF7E00" weight></baseMoney>
						<view class="mt-20 fs-24 text--w111-999">库存:{{ attr.productSelect.stock }}</view>
					</view>
				</view>
			</view>
			<scroll-view class="px-32" scroll-y="true" style="max-height: 400rpx;">
				<view class="item mt-32" v-for="(item, indexw) in attr.productAttr" :key="indexw">
					<view class="fs-28">{{ item.attr_name }}</view>
					<view class="flex-y-center flex-wrap">
						<view class="sku-item" :class="item.index === itemn.attr ? 'active' : ''"
							v-for="(itemn, indexn) in item.attr_value" @click="tapAttr(indexw, indexn)"
							:key="indexn">
							{{ itemn.attr }}
						</view>
					</view>
				</view>
			</scroll-view>
			<view class="flex-between-center px-32 mt-24">
				<text class="fs-28">数量</text>
				<view class="flex-y-center">
					<view class="jia-btn w-84 h-48 flex-center" @click="CartNumAdd(false)">
						<text class="iconfont icon-ic_Reduce fs-24"></text>
					</view>
					<view class='w-84 h-48 text-center lh-48rpx bg--w111-f5f5f5'>{{ attr.productSelect.cart_num }}</view>
					<view class="jia-btn w-84 h-48 flex-center" @click="CartNumAdd(true)">
						<text class="iconfont icon-ic_increase fs-24"></text>
					</view>
				</view>
			</view>
			<view class="mx-20 pb-box">
				<view class="mt-52 h-72 flex-center rd-36px mer-bg fs-26 text--w111-fff" @click="confirmCartAdd" 
					v-if="attr.productSelect.stock">
					确定</view>
				<view class="mt-52 h-72 flex-center rd-36px bg-gray fs-26 text--w111-fff" v-else>已售罄</view>
			</view>
			</view>
		</view>
	</base-drawer>
</template>
<script>
export default {
	props:{
		attr: {
			type: Object,
			default: () => {}
		},
		storeInfo: {
			type: Object,
			default: () => {}
		},
	},
	data(){
		return {

		}
	},
	methods:{
		CartNumAdd(type){
			this.$emit('ChangeCartNum', type);
		},
		closeDrawer(){
			this.$emit('myevent');
		},
		confirmCartAdd(){
			this.$emit('onConfirm');
		},
		tapAttr: function(indexw, indexn) {
			let that = this;
			that.$emit("attrVal", {
				indexw: indexw,
				indexn: indexn
			});
			this.$set(this.attr.productAttr[indexw], 'index', this.attr.productAttr[indexw].attr_values[indexn]);
			let value = that
				.getCheckedValue()
				.join(",");
			that.$emit("ChangeAttr", value);

		},
		//获取被选中属性；
		getCheckedValue: function() {
			let productAttr = this.attr.productAttr;
			let value = [];
			for (let i = 0; i < productAttr.length; i++) {
				for (let j = 0; j < productAttr[i].attr_values.length; j++) {
					if (productAttr[i].index === productAttr[i].attr_values[j]) {
						value.push(productAttr[i].attr_values[j]);
					}
				}
			}
			return value;
		},
		showImg(){
			this.$emit('getImg');
		}
	}
}
</script>
<style lang="scss">
.sku-item {
	height: 56rpx;
	line-height: 56rpx;
	border: 1px solid #F2F2F2;
	font-size: 24rpx;
	color: #333;
	padding: 0 44rpx;
	border-radius: 28rpx;
	margin: 24rpx 0 0 16rpx;
	background-color: #F2F2F2;
	word-break: break-all;
}

.active {
	color: $primary-merchant;
	background: $light-primary-merchant;
	border-color: $primary-merchant;
}
.pb-box{
	padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
}
.bg-gray{
	background-color: #ccc;
}
</style>
