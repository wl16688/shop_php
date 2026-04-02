<template>
	<view>
		<view class="flex-col bg--w111-fff" :style="{ height: windowHeight + 'px' }">
			<view :style="{paddingTop: sysHeight + 'px'}" class="text--w111-999">
				<view class="h-80 px-32 flex-y-center">
					<!-- #ifdef MP -->
					<view class="w-508 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
					<!-- #endif -->
					<!-- #ifndef MP -->
					<view class="w-full h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
					<!-- #endif -->
						<text class="iconfont icon-ic_search fs-28"></text>
						<input v-model="keyword" placeholder="请输入商品名称" @confirm="goSearch" placeholder-class="text--w111-999" class="flex-1 fs-24 pl-18 text--w111-333" />
					</view>
				</view>
			</view>
				<view class="scroll_box flex flex-1">
					<view class="w-168 h-full bg--w111-f5f5f5">
						<scroll-view :scroll-top="0" scroll-y="true" class="h-full">
							<view class="w-168 h-96 flex-center fs-26 text--w111-666"
								v-for="(item,index) in categoryList":key="index"
								:class="index == navActive?'aside_active':''"
								@tap="tapNav(index,item)">
								{{item.cate_name}}
							</view>
							<view class="white-box"></view>
						</scroll-view>
					</view>
					<view class="relative w-full h-full">
						<view class="flex-1 pl-24 pr-20 abs-lt" v-if="categoryErList.length">
							<view class="flex mt-24 mb-16">
								<scroll-view scroll-x="true" class="white-nowrap vertical-middle w-464"
									show-scrollbar="false">
									<view class="inline-block mr-16"
										v-for="(item,index) in categoryErList" :key="index"
										@tap="longClick(index,item)">
										<view
											class="w-144 h-56 rd-30rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
											:class="index===tabClick?'cate_active':''">{{item.cate_name}}</view>
									</view>
								</scroll-view>
								<view class="w-56 h-56 rd-30rpx bg--w111-f5f5f5 flex-center ml-16"
									v-if="categoryErList.length"
									@tap="openCateDrawer(false)">
									<text class="iconfont icon-ic_downarrow fs-32 text--w111-333"></text>
								</view>
							</view>
						</view>
						<view class="h-96" v-if="categoryErList.length"></view>
						<view class="px-24">
							<scroll-view :scroll-top="0" scroll-y="true" @scrolltolower="lower" @scroll="scroll"
								:style="{'height':scrollHeight + 'px'}">
								<!-- 大图模板 -->
								<view v-if="false">
									<view class="mb-24" v-for="(item,index) in tempArr" :key="index"
										@tap="goDetail(item)">
										<view class="picture-box">
											<easy-loadimage
												mode="widthFix"
												:image-src="item.recommend_image ? item.recommend_image : item.image"
												:borderSrc="item.recommend_image ? '' :item.activity_frame.image"
												width="100%"
												borderRadius="20rpx 20rpx 0 0"></easy-loadimage>
										</view>
										<view class="bg--w111-fff rd-b-20rpx pt-16 pl-24 pr-24 pb-24">
											<view class="w-full line2 fs-28 text--w111-333 lh-40rpx">
												<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
											</view>
											<view class="flex-between-center mt-20">
												<view class="flex-y-center flex-wrap flex-1">
													<baseMoney :money="item.price" symbolSize="24" integerSize="40" decimalSize="24" color="#FF7E00" weight>
													</baseMoney>
												</view>
												<view
													class="w-172 h-58 rd-30rpx mer-bg flex-center fs-24 text--w111-fff relative"
													v-if="item.spec_type" @tap.stop="goCartDuo(item)">
													<text>{{item.cart_button == 0 ? '立即购买' : '加入购物车'}}</text>
													<uni-badge class="badge-style" v-if="item.cart_num > 0" :text="item.cart_num"></uni-badge>
												</view>
												<view v-if="!item.spec_type && !item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx mer-bg text--w111-fff "
														@tap.stop="goCartDan(item,index)">
														<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
													</view>
												</view>
												<view class="flex-y-center" v-if="!item.spec_type && item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg--w111-f5f5f5 text--w111-333"
														:class="{'disabled-btn': item.min_qty && item.cart_num == item.min_qty}"
														@tap.stop="ChangeCartNumDan(false,index,item)">
														<text class="iconfont icon-ic_Reduce fs-32"></text>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-if="item.showInput" @tap.stop="toggleInput(item,true)">
														<view class="w-48 text-center">{{item.cart_num}}</view>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-else @tap.stop="toggleInput(item,false)">{{item.cart_num}}</view>
													<view class="flex-center w-48 h-48 rd-30rpx mer-bg text--w111-fff"
														@tap.stop="CartNumAdd(index,item)">
														<text class="iconfont icon-ic_increase fs-32"></text>
													</view>
												</view>
											</view>
										</view>
									</view>
								</view>
								<!-- 小图模板 -->
								<view v-if="true">
									<view class="mb-24 flex justify-between" v-for="(item,index) in tempArr"
										:key="index" @tap="goDetail(item)">
										<easy-loadimage
											mode="aspectFit"
											:image-src="item.image"
											:borderSrc="item.activity_frame.image"
											width="176rpx"
											height="176rpx"
											borderRadius="16rpx"></easy-loadimage>
										<view class="flex-1 flex-col justify-between pl-20">
											<view class="w-full">
												<view class="line2 w-346 fs-28 text-#333 h-80 lh-40rpx">
													<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
												</view>
											</view>
											<view class="flex-between-center">
												<view class="flex-y-center flex-wrap flex-1">
													<baseMoney :money="item.price" symbolSize="24" integerSize="40" decimalSize="24" color="#FF7E00" weight>
													</baseMoney>
												</view>
												<view class="flex-center w-48 h-48 rd-30rpx mer-bg text--w111-fff"
													@tap.stop="goCartDuo(item)" v-if="item.spec_type">
													<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
												</view>
												<view v-else>
													<view
														class="flex-center w-48 h-48 rd-30rpx mer-bg text--w111-fff "
														@tap.stop="goCartDan(item,index)">
														<text class="iconfont icon-ic_ShoppingCart1 fs-30"></text>
													</view>
												</view>
												<!-- <view class="flex-y-center" v-if="!item.spec_type && item.cart_num">
													<view
														class="flex-center w-48 h-48 rd-30rpx bg--w111-f5f5f5 text--w111-333"
														:class="{'disabled-btn': item.min_qty && item.cart_num == item.min_qty}"
														@tap.stop="ChangeCartNumDan(false,index,item)">
														<text class="iconfont icon-ic_Reduce fs-32"></text>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-if="item.showInput" @tap.stop="toggleInput(item,true)">
														<view class="w-48 text-center" >{{item.cart_num}}</view>
													</view>
													<view class="fs-30 text--w111-333 px-20" v-else @tap.stop="toggleInput(item,false)">{{item.cart_num}}</view>
													<view class="flex-center w-48 h-48 rd-30rpx mer-bg text--w111-fff"
														@tap.stop="CartNumAdd(index,item)">
														<text class="iconfont icon-ic_increase fs-32"></text>
													</view>
												</view> -->
											</view>
										</view>
									</view>
								</view>
								<view v-if="!tempArr.length && !loading">
									<emptyPage title="暂无商品，去看点别的吧～" ></emptyPage>
								</view>
								<view class="white-box"></view>
							</scroll-view>
						</view>
					</view>
				</view>
			</view>
			<view class="more_box abs-lt w-full bg--w111-fff rd-b-32rpx z-20" v-show="showCateDrawer">
				<view :style="{paddingTop: sysHeight + 'px'}">
					<view class="h-80 px-32 flex-y-center">
						<!-- #ifdef MP -->
						<view class="w-508 h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
						<!-- #endif -->
						<!-- #ifndef MP -->
						<view class="w-full h-58 flex-y-center rd-30rpx bg--w111-f5f5f5 px-32">
						<!-- #endif -->
							<text class="iconfont icon-ic_search fs-28"></text>
							<text class="fs-24 text--w111-999 pl-18">请输入商品名称</text>
						</view>
					</view>
				</view>
				<view class="pt-32 pl-30 pr-30">
					<view>
						<view class="fs-32 text--w111-333" v-if="categoryErList.length">
							{{categoryErList[tabClick].cate_name}}
						</view>
						<view class="grid-column-4 grid-gap-24rpx mt-24">
							<view class="w-154 h-56 rd-30rpx flex-center fs-24 text--w111-333 bg--w111-f5f5f5"
								v-for="(item,index) in categoryErList" :key="index" @tap="longClick(index,item)"
								:class="index===tabClick?'cate_active':''">
								{{item.cate_name}}
							</view>
						</view>
					</view>
					<view class="flex-center fs-24 text--w111-999 h-80 mt-32" @tap="closeCateDrawer">
						<text>点击收起 <text class="iconfont icon-ic_uparrow fs-24 pl-4"></text> </text>
					</view>
				</view>
			</view>
			<view class="mask" v-show="showCateDrawer" @tap="closeCateDrawer"></view>
			<base-drawer mode="bottom" :visible="showSku" background-color="transparent" zIndex="3000" mask maskClosable
				@close="closeDrawer">
				<view>
					<view class="bg--w111-fff rd-t-40rpx">
					<view class="w-full pt-32">
						<view class="px-32 flex">
							<image class="w-180 h-180 rd-16rpx" :src="attr.productSelect.image"></image>
							<view class="pl-24">
								<baseMoney :money="attr.productSelect.price" symbolSize="32" integerSize="48"
									decimalSize="32" color="#FF7E00" weight></baseMoney>
								<view class="mt-20 fs-24 text--w111-999">库存:{{ attr.productSelect.stock }}</view>
							</view>
						</view>
					</view>
					<scroll-view class="px-32" scroll-y="true" style="max-height: 400rpx;">
						<view class="item mt-32" v-for="(item, indexw) in attr.productAttr" :key="indexw">
							<view class="fs-28">{{ item.attr_name }}</view>
							<view class="flex-y-center flex-wrap">
								<view class="sku-item" :class="item.index === itemn.attr ? 'active-sku' : ''"
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
						<view class="mt-52 h-72 flex-center rd-36px mer-bg fs-26 text--w111-fff" @click="confirmCartAdd">
							确定</view>
					</view>
					</view>
				</view>
			</base-drawer>
		</view>
</template>

<script>
	let windowHeight = uni.getWindowInfo().windowHeight;
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import {
		getCategoryList,
		getProductslist,
		getAttr,
		postCartNum
	} from '@/api/store.js';
	import {
		vcartList,
		getCartCounts,
		cartDel,
		changeCartNum
	} from '@/api/order.js';
	import productWindow from '../../components/skuSelect';
	import cartList from '@/components/cartList';
	import {
		mapState,
		mapGetters
	} from 'vuex';
	import {
		goShopDetail
	} from '@/libs/order.js';
	import {toLogin} from '@/libs/login.js';
	import emptyPage from '@/components/emptyPage.vue';
	import cusPreviewImg from '@/components/cusPreviewImg';
	export default {
		props: {
			showType: {
				type: Number,
				default: 1
			},
			isFooter:{
				type: Boolean,
				default: false
			}
		},
		data() {
			return {
				windowHeight: windowHeight - 50,
				showCateDrawer: false,
				sysHeight: sysHeight,
				categoryList: [],
				navActive: 0,
				categoryTitle: '',
				categoryErList: [],
				tabLeft: 0,
				isWidth: 0, //每个导航栏占位
				tabClick: 0, //导航栏被点击
				iSlong: false,
				tempArr: [],
				loading: false,
				loadend: false,
				loadTitle: '加载更多',
				page: 1,
				limit: 10,
				cid: 0, //一级分类
				sid: 0, //二级分类
				tid: 0, //三级分类
				isAuto: false, //没有授权的不会自动授权
				isShowAuth: false, //是否隐藏授权
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				productAttr: [],
				productValue: [],
				attrValue: '', //已选属性
				storeName: '', //多属性产品名称
				id: 0,
				cartData: {
					cartList: [],
					iScart: false
				},
				totalPrice: 0.00,
				lengthCart: 0,
				is_vip: 0, //是否是会员
				cart_num: 0,
				storeInfo: {},
				scrollHeight: 0,
				threeCateList: [],
				threeClick: 0,
				topNavShow: true,
				selectSku: {},
				skuArr: [],
				showSku: false,
				product_id: "",
				keyword: ""
			}
		},
		components: {
			productWindow,
			cartList,
			emptyPage,
			cusPreviewImg
		},
		computed: {
			...mapState({
				cartNum: state => state.indexData.cartNum
			}),
			...mapGetters(['isLogin', 'uid', 'cartNum'])
		},
		mounted() {
			this.getAllCategory();
			setTimeout(() => {
				this.getScrollHeight();
			}, 500)
		},
		methods: {
			getScrollHeight() {
				let sysH = uni.getWindowInfo().statusBarHeight;
				this.scrollHeight = windowHeight - 88 - sysH;
			},
			getAllCategory() {
				let that = this;
				getCategoryList().then(res => {
					if (!res.data.length) return
					res.data.map(item=>{
						if(item.children && item.children.length){
							item.children.unshift({
								id:item.id,
								cate_name: '全部商品'
							})
						}
					})
					let data = res.data;
					that.categoryTitle = data[0].cate_name;
					that.cid = data[0].id;
					that.sid = 0;
					that.tid = 0;
					that.navActive = 0;
					that.tabClick = 0;
					that.categoryList = data;
					that.categoryErList = res.data[0].children ? res.data[0].children : [];
					that.page = 1;
					that.loadend = false;
					that.tempArr = [];
					that.productslist();
				})
			},
			// 产品列表
			productslist() {
				let that = this;
				if (that.loadend) return;
				if (that.loading) return;
				that.loading = true;
				that.loadTitle = '';
				getProductslist({
					page: that.page,
					limit: that.limit,
					type: 1,
					cid: that.cid,
					sid: that.sid,
					tid: that.tid,
					keyword: that.keyword,
					is_channel:1
				}).then(res => {
					let list = res.data,
						loadend = list.length < that.limit;
					that.tempArr = that.$util.SplitArray(list, that.tempArr);
					that.$set(that, 'tempArr', that.tempArr);
					that.loading = false;
					that.loadend = loadend;
					that.loadTitle = loadend ? "没有更多内容啦~" : "加载更多";
					that.page = that.page + 1;
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多'
				});
			},
			tapNav(index, item) {
				uni.pageScrollTo({
					duration: 0,
					scrollTop: 0
				})
				let list = this.categoryList[index];
				this.navActive = index;
				this.categoryTitle = list.cate_name;
				this.categoryErList = item.children ? item.children : [];
				this.tabClick = 0;
				this.tabLeft = 0;
				this.cid = list.id;
				this.sid = 0;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			// 导航栏点击
			longClick(index, item) {
				this.tabClick = index; //设置导航点击了哪一个
				this.sid = this.categoryErList[index].id;
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			deepClone(obj) {
				let newObj = Array.isArray(obj) ? [] : {}
				if (obj && typeof obj === "object") {
					for (let key in obj) {
						if (obj.hasOwnProperty(key)) {
							newObj[key] = (obj && typeof obj[key] === 'object') ? this.deepClone(obj[key]) : obj[key];
						}
					}
				}
				return newObj
			},
			// 生成订单；
			subOrder() {
				let that = this,
					list = that.cartData.cartList,
					ids = [];
				if (list.length) {
					list.forEach(item => {
						if (item.attrStatus && item.status) {
							ids.push(item.id)
						}
					});
					uni.navigateTo({
						url: '/pages/goods/order_confirm/index?cartId=' + ids.join(',')
					});
					that.cartData.iScart = false;
				} else {
					return that.$util.Tips({
						title: '请选择产品'
					});
				}
			},
			CartNumAdd(type){
				if(type){
					this.attr.productSelect.cart_num++;
				}else{
					if(this.attr.productSelect.cart_num == 1) return
					this.attr.productSelect.cart_num--;
				}
			},
			goCartDuo(item) {
				if (!this.isLogin) {
					toLogin();
				} else {
					if(item.cart_button == 0){
						uni.showToast({
							title: '该商品不可加入购物车',
							icon: 'none'
						})
					}else{
						this.storeName = item.store_name;
						this.product_id = item.id;
						this.getAttrs(item.id);
						this.$set(this, 'id', item.id);
						this.$set(this.attr, 'cartAttr', true);
					}
				}
			},
			// 点击默认单属性购物车
			goCartDan(item, index) {
				if (!this.isLogin) {
					toLogin();
				} else {
					if(item.cart_button == 0){
						uni.navigateTo({
							url: `/pages/merchant/goodsDetails/index?id=${item.id}`
						});
					}else{
						item.cart_num = item.min_qty ? item.min_qty : 1;
						let params = {
							num: item.min_qty ? item.min_qty : 1,
							product_id: item.id,
							is_channel:1,
							type: 1,
						};
						postCartNum(params).then(res=> {
							this.$util.Tips({
								title: '添加购物车成功',
							});
							this.$store.dispatch('indexData/getCartNum')
						}).catch(err => {
							return this.$util.Tips({
								title: err
							});
						});
					}
				}
			},
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0, {is_channel:1}).then(res => {
					this.$set(this.attr, 'productAttr', res.data.productAttr);
					this.productAttr = res.data.productAttr;
					this.$set(this, 'productValue', res.data.productValue);
					this.$set(this, 'storeInfo', res.data.storeInfo);
					this.DefaultSelect();
					this.showSku = true;
				})
			},
			DefaultSelect(){
				let productAttr = this.attr.productAttr;
				let value = [];
				for (var key in this.productValue) {
					if (this.productValue[key].stock > 0) {
						value = productAttr.length ? key.split(',') : [];
						break;
					}
				}
				for (let i = 0; i < productAttr.length; i++) {
					this.$set(productAttr[i], 'index', value[i]);
				}
				let productSelect = this.productValue[value.join(',')];
				this.attr.productSelect = {
					image: productSelect.image,
					price: productSelect.channel_price,
					unique: productSelect.unique || '',
					suk: productSelect.suk || '默认',
					stock: productSelect.stock,
					cart_num: 1
				};
			},
			tapAttr: function(indexw, indexn) {
				let that = this;
				this.$set(this.attr.productAttr[indexw], 'index', this.attr.productAttr[indexw].attr_values[indexn]);
				let value = that.getCheckedValue().join(",");
				this.attr.productSelect = {
					price: this.productValue[value].channel_price,
					image: this.productValue[value].image,
					stock: this.productValue[value].stock,
					unique: this.productValue[value].unique || '',
					suk: this.productValue[value].suk || '默认',
					cart_num: 1
				};
			},
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
			closeDrawer(){
				this.showSku = false;
			},
			//点击sku图片打开轮播图
			showImg(index) {
				this.$refs.cusPreviewImg.open(this.selectSku.suk)
			},
			// 去详情页
			goDetail(item) {
				uni.navigateTo({
					url: `/pages/merchant/goodsDetails/index?id=${item.id}`
				});
			},
			closeCateDrawer() {
				this.showCateDrawer = false;
			},
			openCateDrawer(type) {
				this.topNavShow = type;
				this.showCateDrawer = true;
			},
			lower(e) {
				this.productslist();
			},
			scroll(e) {
				uni.$emit('scroll');
			},
			goSearch() {
				this.page = 1;
				this.loadend = false;
				this.tempArr = [];
				this.productslist();
			},
			toggleInput(item,type){
				if(type) return
				this.$set(item,'showInput',true);
			},
			inputClick(e,item) {
				const o = e.detail.value
				const inputRule = /[^\d]/g
				this.$nextTick(() => {
					item.copy_cart_num = o.replace(inputRule, '');
				})
			},
			confirmCartAdd(){
				let data = {
					product_id:this.product_id,
					num: this.attr.productSelect.cart_num,
					type: 1,
					uniqueId: this.attr.productSelect.unique,
					is_channel:1
				};
				postCartNum(data).then(res=> {
					this.showSku = false;
					this.$store.dispatch('indexData/getCartNum')
					uni.showToast({
						title: '添加购物车成功',
						icon: 'none'
					})
				}).catch(err => {
					return this.$util.Tips({
						title: err
					});
				});
			}
		}
	}
</script>

<style lang="scss" scoped>
	.scroll_box {
		overflow: hidden;
	}

	.aside_active {
		background-color: #fff;
		width: 100%;
		text-align: center;
		color: $primary-merchant;
		font-weight: 500;
		position: relative;

		&::after {
			content: '';
			width: 6rpx;
			height: 48rpx;
			background-color: $primary-merchant;
			position: absolute;
			left: 0;
			top: 50%;
			transform: translateY(-50%);
		}
	}

	.cate_active {
		color: $primary-merchant;
		background: $light-primary-merchant;
		border: 1rpx solid $primary-merchant;
	}
	.picture-box{
		max-height: 382px;
		overflow-y: hidden;
	}
	.jianbian {
		background: linear-gradient(90deg, #ff7931 0%, #e93323 100%);
	}

	.text-primary-con {
		color: $primary-merchant;
	}

	.bg-primary-light {
		background: $light-primary-merchant;
	}

	.pl-30 {
		padding-left: 30rpx;
	}

	.con_border {
		border: 1px solid $primary-merchant;
	}

	.border_e {
		border: 1px solid #eee;
	}

	.active_pic {
		width: 104rpx;
		height: 104rpx;
		background-color: #fff;
		padding: 3rpx;
		border-radius: 50%;
		border: 3rpx solid $primary-merchant;

		image {
			width: 100%;
			height: 100%;
			border-radius: 50%;
		}
	}

	.scroll_pic {
		image {
			width: 92rpx;
			height: 92rpx;
			border-radius: 50%;
		}
	}

	.active_cate_text {
		background: $primary-merchant;
		color: #fff;
		border-radius: 20rpx;
		margin-top: 8rpx;
	}
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

	.active-sku {
		color: $primary-merchant;
		background: $light-primary-merchant;
		border-color: $primary-merchant;
	}
	.w-346 {
		width: 346rpx;
	}
	.badge-style {
		position: absolute;
		top: -10rpx;
		right: -20rpx;
		/deep/ .uni-badge--error {
			background-color: $primary-merchant !important;
		}
		.uni-badge {
			color: $primary-merchant;
			border: 1px solid $primary-merchant;
			z-index: 29;
		}
	}
	.bg-gradient-mer{

	}
	.white-box{
		height: calc(300rpx + env(safe-area-inset-bottom));
	}
	.disabled-btn{
		color: #DEDEDE;
	}
	/deep/.brand-tag{
		background-color: $primary-merchant;
	}
	.pb-box{
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	}
</style>
