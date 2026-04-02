<template>
	<uni-popup :style="colorStyle" ref="popup" type="bottom" @change="popupChange">
		<view class="popup-content">
			<view class="header flex-center">
				<text class="title">已选礼物清单</text>
				<text class="close iconfont icon-ic_close1" @click="close"></text>
			</view>

			<scroll-view scroll-y class="gift-list">
				<checkbox-group @change="checkboxChange">
					<view v-for="(item, index) in giftList" :key="index">
						<view class="flex-between-center gift-list-item">
							<!-- #ifndef MP -->
							<checkbox
								:value="item.id.toString()"
								:checked="item.checked"
								color="#ffffff"
								backgroundColor="#ffffff"
								activeBackgroundColor="var(--view-theme)"
								activeBorderColor="var(--view-theme)"
								:disabled="(!item.attrStatus || item.is_gift ? true : false) && footerswitch"
							/>
							<!-- #endif -->
							<!-- #ifdef MP -->
							<checkbox :value="item.id" :checked="item.checked" :disabled="(!item.attrStatus || item.is_gift ? true : false) && footerswitch" />
							<!-- #endif -->
							<view class="flex-1 ml-22 flex">
								<view class="w-200 h-200 rd-16rpx">
									<image
										class="w-200 h-200 rd-16rpx"
										v-if="item.productInfo.attrInfo"
										:src="item.productInfo.attrInfo.image"
										@tap="goPage(1, '/pages/goods_details/index?id=' + item.productInfo.id)"
										mode="aspectFit"
									></image>
									<image
										class="w-200 h-200 rd-16rpx"
										v-else
										:src="item.productInfo.image"
										@tap="goPage(1, '/pages/goods_details/index?id=' + item.productInfo.id)"
										mode="aspectFit"
									></image>
								</view>
								<view class="ml-20 flex-1 flex-col justify-between">
									<view class="w-full">
										<view class="w-382 line1 fs-28 fw-500 text--w111-333 lh-40rpx">{{ item.productInfo.store_name }}</view>
										<view
											class="inline-block max-w-322 h-38 lh-38rpx mt-12 bg--w111-f5f5f5 text--w111-999 rd-20rpx px-12 text-center fs-22"
											v-if="item.productInfo.attrInfo && item.productInfo.spec_type && !item.is_gift && item.attrStatus"
											@click.stop="cartAttr(item)"
										>
											<view class="flex">
												<text class="line1 max-w-260">{{ item.productInfo.attrInfo.suk }}</text>
												<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
											</view>
										</view>
										<view class="inline-block max-w-322 h-38 lh-38rpx mt-12 bg--w111-f5f5f5 text--w111-999 rd-20rpx px-12 text-center fs-22" v-else>
											<view class="flex">
												<text class="line1">{{ item.productInfo.attrInfo.suk }}</text>
												<text class="iconfont icon-ic_downarrow fs-24 ml-12"></text>
											</view>
										</view>
										<view class="flex items-end flex-wrap mt-12 w-382">
											<BaseTag
												:text="label.label_name"
												:color="label.color"
												:background="label.bg_color"
												:borderColor="label.border_color"
												:circle="label.border_color ? true : false"
												:imgSrc="label.icon"
												v-for="(label, idx) in item.productInfo.store_label"
												:key="idx"
											></BaseTag>
										</view>
									</view>
									<view class="flex-between-center" :class="item.productInfo.store_label.length ? 'mt-12' : 'mt-50'" v-if="item.attrStatus && !item.is_gift">
										<view>
											<baseMoney :money="item.sum_price" symbolSize="24" integerSize="36" decimalSize="24" weight></baseMoney>
										</view>
										<view class="flex-y-center pr-24 text--w111-333">
											<view class="flex-center w-48 h-48" @click.stop="subCart(index)">
												<text
													class="iconfont icon-ic_Reduce fs-24"
													:class="{
														'disabled-btn': item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty
													}"
												></text>
											</view>
											<input
												type="number"
												maxlength="3"
												class="w-72 h-36 rd-4rpx bg--w111-f5f5f5 flex-center text-center fs-24 text--w111-333 mx-8"
												@input="setValue($event, item)"
												v-model="item.cart_num"
											/>
											<view class="flex-center w-48 h-48" @click.stop="addCart(index, item)">
												<text class="iconfont icon-ic_increase fs-24"></text>
											</view>
										</view>
									</view>
									<view class="flex-between-center pr-24" v-if="!item.attrStatus">
										<text class="fs-24 lh-34rpx">请重新选择商品规格</text>
										<view class="w-96 h-48 rd-24rpx flex-center bg--w111-fff fs-24 font-num con_border" @click.stop="reElection(item)">重选</view>
									</view>
								</view>
							</view>
						</view>
					</view>
				</checkbox-group>
			</scroll-view>
			<view class="all flex flex-between-center px-20 py-20">
				<view class="flex-y-center">
					<checkbox-group @change="checkboxAllChange">
						<checkbox
							value="all"
							:checked="!!isAllSelect"
							color="#ffffff"
							backgroundColor="#ffffff"
							activeBackgroundColor="var(--view-theme)"
							activeBorderColor="var(--view-theme)"
						/>
						<text class="fs-26 text--w111-333 lh-36rpx">全选</text>
					</checkbox-group>
					<text class="total-price">
						预估总价:
						<text class="price">￥{{ selectValue.length ? discountInfo.deduction.pay_price || 0 : 0 }}</text>
					</text>
				</view>
				<button class="del" @click="subDel">删除</button>
			</view>
			<view class="footer flex-between-center">
				<button class="submit-btn" @click="submit">立即送给TA</button>
			</view>
		</view>

		<!-- 规格选择弹窗 -->
		<productWindow
			:attr="attrs"
			:isShow="1"
			:iSplus="1"
			:iScart="1"
			:showFooter="true"
			:storeInfo="storeInfo"
			:is_vip="is_vip"
			:type="2"
			:fangda="false"
			@myevent="onMyEvent"
			@ChangeAttr="ChangeAttr"
			@ChangeCartNum="ChangeCartNum"
			@attrVal="attrVal"
			@iptCartNum="iptCartNum"
			@goCat="reGoCat"
			id="product-window"
		></productWindow>
	</uni-popup>
</template>

<script>
import { mapGetters } from 'vuex';
import productWindow from '@/components/productWindow/index.vue';
import { cartCompute, cartDel, changeCartNum, getResetCart } from '@/api/order.js';
import { getAttr } from '@/api/store.js';
import { Debounce } from '@/utils/validate.js';
import colors from '@/mixins/color';
export default {
	components: {
		productWindow
	},
	props: {
		giftList: {
			type: Array,
			default: () => []
		}
	},
	mixins: [colors],
	data() {
		return {
			showSpecPopup: false,
			currentProduct: null,
			currentIndex: -1,
			attrs: {
				cartAttr: false,
				productAttr: [],
				productSelect: {}
			},
			selectValue: [], //选中的数据
			discountInfo: {
				discount: false,
				deduction: {},
				coupon: {},
				svip_price: 0,
				svip_status: false
			},
			isAllSelect: true,
			productValue: [], //系统属性
			storeInfo: {},
			attrValue: '', //已选属性
			attrTxt: '请选择', //属性页面提示
			cartId: 0,
			product_id: 0,
			is_vip: 0, //是否是会员
			isFooter: false
		};
	},
	computed: {
		...mapGetters(['isLogin']),
		totalPrice() {
			return this.giftList
				.reduce((total, item) => {
					return item.checked ? total + item.price * item.num : total;
				}, 0)
				.toFixed(2);
		},
		selectedCount() {
			return this.giftList.filter((item) => item.checked).length;
		}
	},
	methods: {
		open() {
			this.$refs.popup.open();
			this.$nextTick((e) => {
				this.setAllSelectValue(1);
			});
		},
		close() {
			this.$refs.popup.close();
			this.setAllSelectValue(0);
		},
		inArray: function (search, array) {
			for (let i in array) {
				if (array[i] == search) {
					return true;
				}
			}
			return false;
		},
		// 点击切换属性
		cartAttr(item) {
			this.isCart = 1;
			this.getGoodsDetails(item);
		},
		// 修改购物车
		reGoCat: function () {
			let that = this,
				productSelect = that.productValue[this.attrValue];
			//如果有属性,没有选择,提示用户选择
			if (that.attrs.productAttr.length && productSelect === undefined) {
				return that.$util.Tips({
					title: '产品库存不足，请选择其它'
				});
			}
			let q = {
				id: that.cartId,
				product_id: that.product_id,
				num: that.attrs.productSelect.cart_num,
				unique: that.attrs.productSelect !== undefined ? that.attrs.productSelect.unique : '',
				is_send_gift: 1
			};
			getResetCart(q)
				.then(function (res) {
					that.attrs.cartAttr = false;
					that.$util.Tips({
						title: '添加清单成功',
						success: () => {
							that.loadend = false;
							that.page = 1;
							that.giftList = [];
							that.getCartList();
						}
					});
				})
				.catch((res) => {
					return that.$util.Tips({
						title: res.msg
					});
				});
		},
		getCartList() {
			this.$emit('getCartList');
		},
		// 重选
		reElection: function (item) {
			this.isCart = 0;
			this.getGoodsDetails(item);
		},
		/**
		 * 获取产品详情
		 *
		 */
		getGoodsDetails: function (item) {
			uni.showLoading({
				title: '加载中',
				mask: true
			});
			let that = this;
			that.cartId = item.id;
			that.product_id = item.product_id;
			getAttr(item.product_id, 0)
				.then((res) => {
					uni.hideLoading();
					that.attrs.cartAttr = true;
					let storeInfo = res.data.storeInfo;
					that.$set(that, 'storeInfo', storeInfo);
					that.$set(that, 'is_vip', res.data.storeInfo.is_vip);
					that.$set(that.attrs, 'productAttr', res.data.productAttr);
					that.$set(that, 'productValue', res.data.productValue);
					that.DefaultSelect();
				})
				.catch((err) => {
					uni.hideLoading();
				});
		},
		subCart: Debounce(function (index) {
			let that = this;
			let status = false;
			let item = that.giftList[index];
			// 开启起购的话做一下不让减少的限制
			if (item.productInfo.min_qty && item.cart_num == item.productInfo.min_qty) return;
			item.cart_num = Number(item.cart_num) - 1;
			if (item.cart_num < 1) status = true;
			if (item.cart_num <= 1) {
				item.cart_num = 1;
				item.numSub = true;
			} else {
				item.numSub = false;
				item.numAdd = false;
			}
			that.setCartNum(item.id, item.cart_num, (data) => {
				that.giftList[index] = item;
				that.loadend = false;
				this.switchSelect();
			});
		}),
		addCart: Debounce(function (index, obj) {
			let that = this;
			let item = that.giftList[index];
			if (obj.numAdd || (obj.productInfo.limit_num > 0 && obj.cart_num >= obj.productInfo.limit_num)) {
				item.cart_num = item.productInfo.limit_num;
				return this.$util.Tips({
					title: '购物车数量不能大于限购数量'
				});
			}

			item.cart_num = Number(item.cart_num) + 1;
			let productInfo = item.productInfo;
			if (productInfo.hasOwnProperty('attrInfo') && item.cart_num >= item.productInfo.attrInfo.stock) {
				item.cart_num = item.productInfo.attrInfo.stock;
				item.numAdd = true;
				item.numSub = false;
			} else {
				item.numAdd = false;
				item.numSub = false;
			}
			that.setCartNum(item.id, item.cart_num, (data) => {
				this.switchSelect();
				that.loadend = false;
			});
		}),
		popupChange(e) {
			if (!e.show) {
				this.giftList = [];
				// this.selectValue = [];
				this.isAllSelect = true;
			}
			this.$emit('change', e);
		},
		checkboxChange(event) {
			let that = this;
			let value = event.detail.value;
			let valid = that.giftList;
			let arr1 = [];
			let arr2 = [];
			let arr3 = [];
			let len = 0;
			valid.forEach((item) => {
				len = len + 1;
				if (that.inArray(item.id, value)) {
					if (that.footerswitch) {
						if (item.attrStatus && !item.is_gift) {
							item.checked = true;
							arr1.push(item);
						} else {
							item.checked = false;
						}
					} else {
						item.checked = true;
						arr1.push(item);
					}
				} else {
					item.checked = false;
					arr2.push(item);
				}
			});
			if (that.footerswitch) {
				arr3 = arr2.filter((item) => !item.attrStatus || item.is_gift);
			}
			that.$set(that.giftList, 'valid', valid);
			that.isAllSelect = len === arr1.length + arr3.length;
			that.selectValue = value;
			that.switchSelect();
		},
		setCartNum(cartId, cartNum, successCallback) {
			let that = this;
			changeCartNum(cartId, cartNum).then((res) => {
				successCallback && successCallback(res.data);
			});
		},
		checkboxAllChange(event) {
			let value = event.detail.value;
			if (value.length > 0) {
				this.setAllSelectValue(1);
			} else {
				this.discountInfo.deduction.pay_price = 0
				this.setAllSelectValue(0);
			}
		},
		setAllSelectValue: function (status) {
			let that = this;
			let selectValue = [];
			let valid = that.giftList;
			if (valid.length > 0) {
				valid.forEach((item) => {
					if (status) {
						if (that.footerswitch) {
							if (item.attrStatus && !item.is_gift) {
								item.checked = true;
								selectValue.push(item.id);
							} else {
								item.checked = false;
							}
						} else {
							item.checked = true;
							selectValue.push(item.id);
						}
						that.isAllSelect = true;
					} else {
						item.checked = false;
						that.isAllSelect = false;
					}
				});
				that.$set(that, 'giftList', valid);
				that.selectValue = selectValue;
				that.switchSelect();
			}
		},
		switchSelect: function () {
			let that = this;
			let validList = that.giftList;
			let selectValue = that.selectValue;
			let selectCountPrice = 0.0;
			let cartId = [];
			if (selectValue.length < 1) {
				that.selectCountPrice = selectCountPrice;
			} else {
				for (let index in validList) {
					if (that.inArray(validList[index].id, selectValue)) {
						cartId.push(validList[index].id);
						selectCountPrice = that.$util.$h.Add(selectCountPrice, that.$util.$h.Mul(validList[index].cart_num, validList[index].truePrice));
					}
				}
				that.selectCountPrice = selectCountPrice;
			}
			let data = {
				cartId: cartId.join(','),
				is_send_gift: 1
			};
			if (cartId.length) {
				this.getCartCompute(data);
			}
		},
		/**
		 * 默认选中属性
		 *
		 */
		DefaultSelect: function () {
			let productAttr = this.attrs.productAttr;
			let value = [],
				stock = 0,
				attrValue = [];
			for (var key in this.productValue) {
				if (this.productValue[key].stock > 0) {
					value = this.attrs.productAttr.length ? key.split(',') : [];
					break;
				}
			}
			//isCart 1切换属性 0为重选
			if (this.isCart) {
				//购物车默认打开时，随着选中的属性改变
				// let attrValue = [];
				this.giftList.forEach((item) => {
					if (item.id == this.cartId) {
						attrValue = item.productInfo.attrInfo.suk.split(',');
					}
				});
				let key = attrValue.join(',');
				stock = this.productValue[key].stock;
				for (let i = 0; i < productAttr.length; i++) {
					this.$set(productAttr[i], 'index', stock ? attrValue[i] : value[i]);
				}
			} else {
				for (let i = 0; i < productAttr.length; i++) {
					this.$set(productAttr[i], 'index', value[i]);
				}
			}

			//sort();排序函数:数字-英文-汉字；
			let productSelect = this.productValue[this.isCart && stock ? attrValue.join(',') : value.join(',')];
			if (productSelect && productAttr.length) {
				this.$set(this.attrs.productSelect, 'store_name', this.storeInfo.store_name);
				this.$set(this.attrs.productSelect, 'image', productSelect.image);
				this.$set(this.attrs.productSelect, 'price', productSelect.price);
				this.$set(this.attrs.productSelect, 'stock', productSelect.stock);
				this.$set(this.attrs.productSelect, 'unique', productSelect.unique);
				this.$set(this.attrs.productSelect, 'cart_num', 1);
				this.$set(this, 'attrValue', value.join(','));
				this.$set(this.attrs.productSelect, 'vip_price', productSelect.vip_price);
				this.$set(this, 'attrTxt', '已选择');
			} else if (!productSelect && productAttr.length) {
				this.$set(this.attrs.productSelect, 'store_name', this.storeInfo.store_name);
				this.$set(this.attrs.productSelect, 'image', this.storeInfo.image);
				this.$set(this.attrs.productSelect, 'price', this.storeInfo.price);
				this.$set(this.attrs.productSelect, 'stock', 0);
				this.$set(this.attrs.productSelect, 'unique', '');
				this.$set(this.attrs.productSelect, 'cart_num', 0);
				this.$set(this.attrs.productSelect, 'vip_price', this.storeInfo.vip_price);
				this.$set(this, 'attrValue', '');
				this.$set(this, 'attrTxt', '请选择');
			} else if (!productSelect && !productAttr.length) {
				this.$set(this.attrs.productSelect, 'store_name', this.storeInfo.store_name);
				this.$set(this.attrs.productSelect, 'image', this.storeInfo.image);
				this.$set(this.attrs.productSelect, 'price', this.storeInfo.price);
				this.$set(this.attrs.productSelect, 'stock', this.storeInfo.stock);
				this.$set(this.attrs.productSelect, 'unique', this.storeInfo.unique || '');
				this.$set(this.attrs.productSelect, 'cart_num', 1);
				this.$set(this.attrs.productSelect, 'vip_price', this.storeInfo.vip_price);
				this.$set(this, 'attrValue', '');
				this.$set(this, 'attrTxt', '请选择');
			}
		},
		attrVal(val) {
			this.$set(this.attrs.productAttr[val.indexw], 'index', this.attrs.productAttr[val.indexw].attr_values[val.indexn]);
		},
		getCartCompute(cartId) {
			cartCompute(cartId)
				.then((res) => {
					this.discountInfo.coupon = res.data.coupon;
					this.discountInfo.deduction = res.data.deduction;
					this.discountInfo.svip_price = res.data.svip_price;
					this.discountInfo.svip_status = res.data.svip_status;
				})
				.catch((err) => {
					this.$util.Tips({
						title: err
					});
				});
		},
		changeNum(index, delta) {
			const item = this.giftList[index];
			const newNum = item.num + delta;
			if (newNum < 1) return;
			if (item.limit && newNum > item.limit) {
				return this.$util.Tips({
					title: `购买数量不能超过${item.limit}件`
				});
			}
			item.num = newNum;
			this.$emit('update:giftList', [...this.giftList]);
		},
		deleteItem(index) {
			this.giftList.splice(index, 1);
			this.$emit('update:giftList', [...this.giftList]);
		},
		changeSpec(item) {
			this.currentProduct = item;
			this.showSpecPopup = true;
			// 这里需要调用获取商品规格的接口
			// getAttr(item.id).then(res => {
			//   this.attrs.productAttr = res.data.productAttr;
			//   this.attrs.productSelect = res.data.productSelect;
			// });
		},
		closeSpecPopup() {
			this.showSpecPopup = false;
		},
		/**
		 * 属性变动赋值
		 *
		 */
		ChangeAttr(res) {
			let productSelect = this.productValue[res];
			if (productSelect && productSelect.stock > 0) {
				this.$set(this.attrs.productSelect, 'image', productSelect.image);
				this.$set(this.attrs.productSelect, 'price', productSelect.price);
				this.$set(this.attrs.productSelect, 'stock', productSelect.stock);
				this.$set(this.attrs.productSelect, 'unique', productSelect.unique);
				this.$set(this.attrs.productSelect, 'cart_num', 1);
				this.$set(this.attrs.productSelect, 'vip_price', productSelect.vip_price);
				this.$set(this, 'attrValue', res);
				this.$set(this, 'attrTxt', '已选择');
			} else {
				this.$set(this.attrs.productSelect, 'image', this.storeInfo.image);
				this.$set(this.attrs.productSelect, 'price', this.storeInfo.price);
				this.$set(this.attrs.productSelect, 'stock', 0);
				this.$set(this.attrs.productSelect, 'unique', '');
				this.$set(this.attrs.productSelect, 'cart_num', 0);
				this.$set(this.attrs.productSelect, 'vip_price', this.storeInfo.vip_price);
				this.$set(this, 'attrValue', '');
				this.$set(this, 'attrTxt', '请选择');
			}
		},
		submit() {
			if (this.selectedCount === 0) {
				return this.$util.Tips({
					title: '请至少选择一件礼物'
				});
			}
			const selectedGifts = this.giftList.filter((item) => item.checked);
			this.$emit('submit', selectedGifts);
			this.close();
		},

		// 添加删除方法
		subDel(event) {
			let that = this,
				selectValue = that.selectValue;
			if (selectValue.length > 0)
				cartDel(selectValue).then((res) => {
					that.loadend = false;
					that.page = 1;
					that.giftList = [];
					that.getCartList();
					that.selectValue = [];
				});
			else
				return that.$util.Tips({
					title: '请先选择商品'
				});
		}
	}
};
</script>

<style lang="scss" scoped>
.popup-content {
	background-color: #fff;
	border-radius: 40rpx 40rpx 0 0;
	padding-bottom: env(safe-area-inset-bottom);
	max-height: 80vh;
	display: flex;
	flex-direction: column;
}

.header {
	position: relative;
	padding: 32rpx;

	.title {
		font-size: 32rpx;
		font-weight: 500;
		color: #333;
		text-align: center;
	}

	.close {
		position: absolute;
		right: 32rpx;
		top: 32rpx;
		font-size: 42rpx;
		color: #999;
	}
}

.gift-list {
	overflow-y: scroll;
	max-height: 700rpx;
	.gift-list-item {
		padding: 28rpx 0rpx 28rpx 32rpx;
	}
}

.gift-item {
	padding: 32rpx 0;
	border-bottom: 1rpx solid #f5f5f5;
}

.gift-image {
	width: 120rpx;
	height: 120rpx;
	border-radius: 8rpx;
}

.gift-info {
	flex: 1;
	margin-left: 20rpx;

	.name {
		font-size: 28rpx;
		color: #333;
		line-height: 40rpx;
	}

	.price {
		margin-top: 12rpx;
		color: var(--view-theme);
		font-weight: 500;
	}

	.spec {
		margin-top: 12rpx;
		padding: 6rpx 12rpx;
		background: #f5f5f5;
		border-radius: 20rpx;
		font-size: 24rpx;
		color: #666;
		display: inline-flex;
		align-items: center;
	}
}

.num-input {
	width: 80rpx;
	height: 50rpx;
	text-align: center;
	font-size: 28rpx;
	margin: 0 12rpx;
	border: 1rpx solid #eee;
	border-radius: 8rpx;
}

.delete {
	font-size: 36rpx;
	color: #999;
}
.all {
	padding: 20rpx 32rpx;
	.del {
		font-size: 24rpx;
		color: var(--view-theme);
		border: 1rpx solid var(--view-theme);
		padding: 12rpx 24rpx;
		border-radius: 40rpx;
	}
}
.total-price {
	padding-left: 20rpx;

	font-size: 26rpx;
	color: #333;

	.price {
		color: var(--view-theme);
		font-weight: 500;
	}
}
.footer {
	padding: 20rpx 32rpx;
	background: #fff;
	border-top: 1rpx solid #f5f5f5;

	.select-all {
		margin: 0 20rpx;
		font-size: 26rpx;
		color: #333;
	}

	.submit-btn {
		width: 100%;
		height: 80rpx;
		background: var(--view-theme);
		color: #fff;
		font-size: 28rpx;
		border-radius: 40rpx;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0;
	}
}

.disabled-btn {
	color: #dedede;
}
</style>
