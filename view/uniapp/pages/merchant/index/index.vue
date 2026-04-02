<template>
	<!-- 商品列表 -->
	<view>
		<view class="header_box fixed-lt w-full z-20 bg--w111-fff" :style="{'padding-top': sysHeight + 'px'}">
			<view class="h-80 px-20 flex-y-center">
				<!-- <text class="iconfont icon-ic_leftarrow fs-40 mr-16" @tap="goBack"></text> -->
				<!--  #ifdef  MP-WEIXIN -->
				<view class="h-58 rd-30 bg--w111-f5f5f5 flex-y-center px-32 relative" :style="[serchWidth]">
				<!--  #endif -->
				<!--  #ifndef  MP-WEIXIN -->
				<view class="flex-1 h-58 rd-30 bg--w111-f5f5f5 flex-y-center px-32 relative">
				<!--  #endif -->
					<text class="iconfont icon-ic_search fs-24"></text>
					<input :value='where.keyword' class="pl-18 w-460 line1 fs-24" placeholder="请输入商品名称" placeholder-class="text--w111-999" @confirm="searchSubmit" />
					<text class="iconfont icon-ic_close1 fs-28 text--w111-999 z-10" v-if="where.keyword" @tap="clearWord"></text>
				</view>
			</view>
			<view class="h-88 px-32 flex-between-center relative">
				<view class="text--w111-333 fs-26 fw-500 flex-y-center" @tap="toggleSortable">
					<text>综合排序</text>
					<text class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26" @tap='set_where(3)'>
					<text :class="[1,2].includes(stock) ? 'text-primary-con' : ''">销量</text>
					<text v-show="stock==2" class="iconfont icon-ic_down2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="stock==1" class="iconfont icon-ic_up2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="stock==0" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26" @tap='set_where(2)'>
					<text :class="[1,2].includes(price) ? 'text-primary-con' : ''">价格</text>
					<text v-show="price==2" class="iconfont icon-ic_down2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="price==1" class="iconfont icon-ic_up2 fs-14 ml-6 text-primary-con"></text>
					<text v-show="price==0" class="iconfont icon-ic_down2 fs-14 ml-6"></text>
				</view>
				<view class="text--w111-666 flex-y-center fs-26">
					<text class="iconfont" :class="is_switch ? 'icon-a-ic_Imageandtextsorting' : 'icon-a-ic_QRcode'" @tap='Changswitch'></text>
					<text class="menu_line"></text>
					<view @tap="openDrawer">筛选</view>
					<text class="iconfont icon-ic_sort pl-8" @tap="openDrawer"></text>
				</view>
				<view class="sortable-box w-full bg--w111-fff rd-b-24rpx z-999" v-if="showSortAbleBox">
					<view class="flex-between-center pb-40"
						v-for="(item, index) in sortableShowTab" :key="index" @tap="checkSortable(item.value)">
						<text class="fs-26" :class="item.value == where.defaultOrder ? 'text-primary-con' : 'text--w111-333'">{{item.title}}</text>
						<image class="w-28 h-28" src="../static/dui-icon.png" v-show="item.value == where.defaultOrder"></image>
					</view>
				</view>
			</view>
		</view>
		<view class="pt-16 pl-20 pr-20 bg--w111-fff" :style="{'margin-top':marTop + 'rpx'}" v-if="is_switch==false">
			<view class="flex mb-32 pro_item" v-for="(item,index) in tempArr" :key="index" @tap="goDetail(item)">
				<view class="list-pic w-240 h-240 relative">
					<easy-loadimage
					:image-src="item.image"
					:borderSrc="item.activity_frame.image"
					width="240rpx"
					height="240rpx"
					borderRadius="20rpx"></easy-loadimage>
				</view>
				<view class="flex-1 pl-20 flex-col justify-between">
					<view class="w-full">
						<view class="w-full line2 fs-28 text--w111-333 lh-40rpx">
							<text v-if="item.brand_name" class="brand-tag">{{ item.brand_name }}</text>{{item.store_name}}
						</view>
					</view>
					<view class="flex-between-center">
						<baseMoney :money="item.price" symbolSize="24" integerSize="40" decimalSize="24" color="#FF7E00" weight></baseMoney>
						<view class="w-44 h-44 rd-24 mer-bg flex-center" @tap.stop="addCartTap(item)">
							<text class="iconfont icon-ic_ShoppingCart1 text--w111-fff fs-26"></text>
						</view>
					</view>
				</view>
			</view>
			<view class='loadingicon acea-row row-center-wrapper' v-if='tempArr.length > 0'>
				<text class='loading iconfont icon-jiazai' :hidden='loading==false'></text>
			</view>
		</view>
		<view class="p-20 relative" :style="{'margin-top':marTop + 'rpx'}" v-else>
			<view class="jinabian"></view>
			<waterfallsFlow :wfList="tempArr" isMerchant @itemTap="goDetail"></waterfallsFlow>
		</view>
		<view class="pb-safe">
			<view class="h-200"></view>
		</view>
		<view class='px-20' v-if="tempArr.length==0 && where.page > 1">
			<emptyPage :title="where.keyword ? '无搜索结果,换个词试试吧' : '暂无商品，去看点别的吧～'"
				:src="where.keyword ? '/statics/images/noSearch.gif' : '/statics/images/empty-box.gif'"></emptyPage>
			<recommend :hostProduct="hostProduct"></recommend>
		</view>
		<filterDrawer
			:visible="showFilterDrawer"
			:brandList="brandList"
			:cateList="cateList"
			@closeDrawer="closeFilter"
			@filterChange="filterConfirm"></filterDrawer>
		<view class="mask" v-if="showSortAbleBox" @tap="showSortAbleBox = false"></view>
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
		<tab-bar ref="tabBar" v-show="showTabBar"></tab-bar>
	</view>
</template>

<script>
	let sysHeight = uni.getWindowInfo().statusBarHeight;
	import productWindow from '@/components/productWindow';
	import waterfallsFlow from "@/components/WaterfallsFlow/WaterfallsFlow.vue";
	import {
		getProductslist,
		getProductHot,
		searchFilterApi,
		levelCategoryApi,
		getAttr,
		postCartAdd,
		postCartNum,
		getCategoryList
	} from '@/api/store.js';
	import {cartDel} from "@/api/order.js";
	import { toLogin } from '@/libs/login.js';
	import recommend from '@/components/recommend';
	import filterDrawer from '../components/filterDrwer/index.vue';
	import emptyPage from '@/components/emptyPage.vue';
	import tabBar from "../components/tabBar/index.vue"
	import { mapGetters } from "vuex";
	import { goShopDetail } from '@/libs/order.js';
	import { vcartList, getCartCounts, cartCompute } from '@/api/order.js';
	import colors from '@/mixins/color.js';
	import { HTTP_REQUEST_URL } from '@/config/app';
	export default {
		computed: {
			...mapGetters(['uid', 'isLogin']),
			serchWidth(){
				let res = wx.getMenuButtonBoundingClientRect();
				let windowWidth = wx.getWindowInfo().windowWidth;
				return {
					width: windowWidth - res.width - (windowWidth-res.right) - 20 + 'px'
				};
			}
		},
		components: {
			recommend,
			waterfallsFlow,
			filterDrawer,
			emptyPage,
			tabBar
		},
		mixins: [colors],
		data() {
			return {
				id: 0,
				productValue: [], //系统属性
				is_vip: 0, //是否是会员
				attr: {
					cartAttr: false,
					productAttr: [],
					productSelect: {}
				},
				productAttr: [],
				attrValue: '', //已选属性
				storeName: '',
				sysHeight: sysHeight,
				goodList: true,
				currentPage: false,
				tempArr: [],
				is_switch: true,
				where: {
					sid: 0,
					keyword: '',
					priceOrder: '',
					salesOrder: '',
					news: 0,
					page: 1,
					limit: 10,
					cid: 0,
					tid: 0,
					brand_id: '',
					defaultOrder:0,
					is_channel:1
				},
				price: 0,
				stock: 0,
				nows: false,
				loadend: false,
				loading: false,
				loadTitle: '加载更多',
				title: '',
				hostProduct: [],
				hotPage: 1,
				hotLimit: 10,
				hotScroll: false,
				brandList: [],
				promotionList:[],
				labelList:[],
				filterCateList:[],
				storeInfo: {},
				totalPrice: 0,
				promotionsInfo: {},
				totalNum: 0,
				imgHost: HTTP_REQUEST_URL,
				isShowAuth: false,
				marTop:'',
				showFilterDrawer:false,
				cateSearch:false,
				showSortAbleBox:false,
				sortableShowTab:[
					{title:'综合排序',value:0},
					{title:'好评优先',value:1},
					{title:'新品优先',value:2},
				],
				cartData: {
					cartList: [],
					iScart: false
				},
				cart_num: 0,
				discountInfo:{
					deduction:{},
				},
				showSku: false,
				cateList: [],
				showTabBar: true,
				product_id: ''
			};
		},
		provide() {
		    return {
				addCartClick: this.addCartTap,
				isVip: false,
				merchant: true
		    };
		  },
		onLoad(options) {
			this.getMarTop();
			this.getCateList()
			this.getProductList();
			this.getSearchFilter();
			this.getChannel();
		},
		methods: {
			getChannel(){
				let identity = this.$store.state.app.identity;
				let styleType = this.$store.state.app.system_channel_style;
				if(identity != 1 && this.isLogin){
					setTimeout(()=>{
						uni.switchTab({
							url: "/pages/index/index"
						})
					},500)
				}else if(identity == 2 && this.isLogin){
					uni.redirectTo({
						url: "/pages/admin/work/index"
					})
				}
			},
			addCartTap(data){
				if(data.spec_type){
					this.product_id = data.id;
					this.goCartDuo(data);
				}else{
					if (!this.isLogin) {
						toLogin();
					} else {
						if(data.cart_button == 0){
							uni.showToast({
								title: '该商品不可加入购物车',
								icon: 'none'
							})
						}else{
							let params = {
								num: 1,
								product_id: data.id,
								is_channel:1,
								type: 1,
							};
							postCartNum(params).then(res=> {
								this.$util.Tips({
									title: '添加购物车成功',
								});
								this.$refs.tabBar.getCartNum()
							}).catch(err => {
								return this.$util.Tips({
									title: err
								});
							});
						}
					}
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
						this.getAttrs(item.id);
						this.$set(this, 'id', item.id);
						this.$set(this.attr, 'cartAttr', true);
					}
				}
			},
			// 商品详情接口；
			getAttrs(id) {
				let that = this;
				getAttr(id, 0).then(res => {
					this.$set(this.attr, 'productAttr', res.data.productAttr);
					this.productAttr = res.data.productAttr;
					this.$set(this, 'productValue', res.data.productValue);
					this.$set(this, 'storeInfo', res.data.storeInfo);
					this.DefaultSelect();
					this.showSku = true;
				})
			},
			DefaultSelect(){
				let productAttr = this.productAttr;
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
					price: productSelect.price,
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
					price: this.productValue[value].price,
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
			// 筛选
			getSearchFilter() {
				searchFilterApi({}).then(res => {
					this.brandList = res.data.brand;
				}).catch(err => {
					return this.$util.Tips({
						title: err.msg
					});
				})
			},
			toggleSortable(){
				this.showSortAbleBox = !this.showSortAbleBox;
			},
			checkSortable(val){
				this.where.defaultOrder = val;
				this.showSortAbleBox = false;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			clearWord(){
				this.where.keyword = ''
			},
			cateCheck(item){
				this.where.sid = item.id;
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			openDrawer(){
				this.showFilterDrawer = true;
				this.showTabBar = false;
			},
			closeFilter(){
				this.showFilterDrawer = false;
				this.showTabBar = true;
			},
			filterConfirm(data){
				this.showFilterDrawer = false;
				this.showTabBar = true;
				Object.assign(this.where,data);
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			CartNumAdd(type){
				if(type){
					this.attr.productSelect.cart_num++;
				}else{
					if(this.attr.productSelect.cart_num == 1) return
					this.attr.productSelect.cart_num--;
				}
			},
			// 去详情页
			goDetail(item) {
				this.currentPage = false;
				uni.navigateTo({
					url: `/pages/merchant/goodsDetails/index?id=${item.id}`
				});
			},
			Changswitch: function() {
				let that = this;
				this.currentPage = false
				that.is_switch = !that.is_switch
			},
			searchSubmit: function(e) {
				let that = this;
				this.currentPage = false
				that.$set(that.where, 'keyword', e.detail.value);
				that.loadend = false;
				that.$set(that.where, 'page', 1)
				this.getProductList(true);
				this.getSearchFilter();
			},
			/**
			 * 获取我的推荐
			 */
			get_host_product: function() {
				let that = this;
				if (that.hotScroll) return
				getProductHot(
					that.hotPage,
					that.hotLimit,
				).then(res => {
					that.hotPage++
					that.hotScroll = res.data.length < that.hotLimit
					that.hostProduct = that.hostProduct.concat(res.data)
					// that.$set(that, 'hostProduct', res.data)
				});
			},
			//点击事件处理
			set_where: function(e) {
				this.currentPage = false
				switch (e) {
					case 1:
						// #ifdef H5
						return history.back();
						// #endif
						// #ifndef H5
						return uni.navigateBack({
							delta: 1,
						})
						// #endif
						break;
					case 2:
						if (this.price == 0) this.price = 1;
						else if (this.price == 1) this.price = 2;
						else if (this.price == 2) this.price = 0;
						this.stock = 0;
						break;
					case 3:
						if (this.stock == 0) this.stock = 1;
						else if (this.stock == 1) this.stock = 2;
						else if (this.stock == 2) this.stock = 0;
						this.price = 0
						break;
					case 4:
						this.nows = !this.nows;
						break;

				}
				this.loadend = false;
				this.$set(this.where, 'page', 1);
				this.getProductList(true);
			},
			//设置where条件
			setWhere: function() {
				if (this.price == 0) this.where.priceOrder = '';
				else if (this.price == 1) this.where.priceOrder = 'asc';
				else if (this.price == 2) this.where.priceOrder = 'desc';
				if (this.stock == 0) this.where.salesOrder = '';
				else if (this.stock == 1) this.where.salesOrder = 'asc';
				else if (this.stock == 2) this.where.salesOrder = 'desc';
				this.where.news = this.nows ? 1 : 0;
			},
			//查找产品
			getProductList: function(isPage) {
				let that = this;
				that.setWhere();
				if (that.loadend) return;
				if (that.loading) return;
				if (isPage === true) {
					that.$set(that, 'tempArr', []);
				}
				that.loading = true;
				that.loadTitle = '';
				getProductslist(that.where).then(res => {
					let list = res.data;
					let productList = that.$util.SplitArray(list, that.tempArr);
					let loadend = list.length < that.where.limit;
					that.loadend = loadend;
					that.loading = false;
					that.loadTitle = loadend ? '没有更多内容啦~' : '加载更多';
					that.$set(that, 'tempArr', productList);
					that.$set(that.where, 'page', that.where.page + 1);
					if (!that.tempArr.length) this.get_host_product();
				}).catch(err => {
					that.loading = false;
					that.loadTitle = '加载更多';
				});
			},
			getMarTop(){
				this.$nextTick(()=>{
					this.marTop = (75 + sysHeight) * 2
				})
			},
			goBack(){
				// uni.navigateBack()
				let pages = getCurrentPages(); // 获取当前打开过的页面路由数，
				if (pages.length > 1) {
					uni.navigateBack()
				} else {
					uni.switchTab({
						url: '/pages/index/index'
					});
				}
			},
			getCateList(){
				getCategoryList().then(res => {
					this.cateList = res.data;
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
		},
		onPageScroll(e) {
			this.currentPage = false;
			uni.$emit('scroll');
		},
		onReachBottom() {
			if (this.tempArr.length > 0) {
				this.getProductList();
			} else {
				this.get_host_product();
			}
		},
		onPullDownRefresh() {
			if (this.tempArr.length > 0) {
				this.getProductList();
			} else {
				this.get_host_product();
			}
			setTimeout(function () {
				uni.stopPullDownRefresh();
			}, 1000);
		},
	}
</script>
<style scoped lang="scss">
	.z-200{
		z-index: 200;
	}
	.z-101{
		z-index: 101;
	}
	.pro_item ~ .pro_item{
		margin-top: 32rpx;
	}
	.border-picture {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		border-radius: 16rpx 16rpx 0 0;
		background: center/cover no-repeat;
	}
	.menu_line {
		width: 1px;
		height: 30rpx;
		background: #B3B3B3;
		margin: 0 20rpx;
	}
	.icon-ic_close1{
		position: absolute;
		right: 34rpx;
		top: 50%;
		transform: translateY(-50%);
	}
	.select_cate{
		&:after{
			content: '';
			position: absolute;
			top: 42rpx;
			left:50%;
			transform: translateX(-50%);
			width:36rpx;
			height:10rpx;
			background-image: url('../static/select_zs.png');
			background-size: 100%;
		}
	}
	.jinabian{
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 330rpx;
		background: linear-gradient(180deg, #FFFFFF 0%, rgba(255,255,255,0) 100%);
	}
	.info_box{
		padding: 16rpx 20rpx;
		border-radius: 0 0 20rpx 20rpx;
		background-color: #fff;
	}
	.text-primary-con{
		color: var(--view-theme);
	}
	.bg-primary-light{
		background: var(--view-minorColorT);
	}
	.bg--w111-484643{
		background: linear-gradient(90deg, #484643 0%, #1F1B17 100%);
	}
	.text--w111-FDDAA4{
		color: #FDDAA4;
	}
	.svip_rd{
		border-radius: 14rpx 0 8rpx 14rpx;
	}
	.op-border{
		border: 1px solid #f5f5f5;
	}
	.active{
		border: 1px solid var(--view-theme);
		color: var(--view-theme);
		background: var(--view-minorColorT);
	}
	.sortable-box{
		padding: 32rpx 32rpx 0;
		position: absolute;
		top: 88rpx;
		left: 0;
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
	.pb-box{
		padding-bottom: calc(20rpx + env(safe-area-inset-bottom));
	}
	/deep/.brand-tag{
		background-color: $primary-merchant;
	}
</style>
