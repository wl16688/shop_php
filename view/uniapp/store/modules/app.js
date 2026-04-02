// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

import { getUserInfo } from "@/api/user.js";
import { diyProductApi } from "@/api/store.js";
import { getNavigation, systemConfigApi } from '@/api/public.js';
import { getDiyVersion } from "@/api/api.js";
import { getActivityModalListApi } from "@/api/activity";
import {
	LOGIN_STATUS,
	NON_WIFI_AUTOPLAY,
	UID,
	USER_INFO
} from '@/config/cache';
import Cache from '@/utils/cache';

const state = {
	token: Cache.get(LOGIN_STATUS) || false,
	backgroundColor: "#fff",
	userInfo: Cache.get(USER_INFO) || {},
	uid: Cache.get(UID) || 0,
	homeActive: false,
	phoneStatus: true,
	autoplay: Cache.get(NON_WIFI_AUTOPLAY) || false,
	// 商品详情可视化数据
	diyProduct: {
		navList: [0, 1, 2, 3, 4], // 顶部菜单内容
		openShare: 1, //是否开启分享
		pictureConfig: 0, //轮播图模式 0 固定方图 1 高度自适应
		swiperDot: 1, //是否展示轮播指示点
		showPrice: [0, 1], //是否显示付费会员价和等级会员
		isOpen: [0, 1, 2], //是否展示 0 原价 1 累计销量 2 库存
		showSvip: 1, //是否展示付费会员卡片
		showRank: 1, // 是否展示 排行榜卡片
		showService: [0, 1, 2, 3], //服务区卡片 0 营销活动入口 1 sku选择 2 服务保障 3 参数
		showReply: 1, //是否展示评论区
		replyNum: 3, //评论数量
		showMatch: 1, //是否展示搭配购
		matchNum: 3, //搭配套餐数量
		showRecommend: 1, //是否展示推荐商品
		recommendNum: 12, //推荐商品数量
		menuList: [0, 1, 2], //底部左侧菜单
		showCart: 1, //是否显示购物车
		showCommunity: 0, //是否显示种草
		communityNum: 3,
		showPromoter: 1, //是否显示推广
		showPromoterType: 0, //0 全部用户 1 分销员 2 普通用户
		menuStatus: 0, //底部菜单状态 0 常规版 1 分销版
		showMenuPromoterShare: 0, //0 全部用户 1 分销员 2 普通用户
		system_channel_style: 1
	},
	// 商品分类可视化数据
	diyCategory: {
		level: 2,
		index: 0
	},
	store_brokerage_status: 1, //分销模式 1,指定分销,2人人分销,3满额分销
	store_brokerage_price: '', //满额分销金额
	productVideoStatus: true,
	brokerage_func_status: 0,
	tabBarData: {
		menuList: [],
		navConfig: {}
	},
	identity: Cache.get('identity'),
	division_apply_phone_verify: Cache.get('division_apply_phone_verify') || 0, // 是否开启短信验证
	brokerage_apply_phone_verify: Cache.get('brokerage_apply_phone_verify') || 0, //分销员
	channel_apply_phone_verify: Cache.get('channel_apply_phone_verify') || 0, //采购商
	supplier_apply_phone_verify: Cache.get('supplier_apply_phone_verify') || 0, //供应商
	brokerage_apply_explain: '', //分销员说明
	division_apply_explain: '', // 代理商说明
	channel_apply_explain: '', //采购商说明
	supplier_apply_explain: '', //供应商说明
	activityModalList:[], //自动化运营设置的广告弹窗
};

const mutations = {
	SETPHONESTATUS(state, val) {
		state.phoneStatus = val;
	},
	LOGIN(state, opt) {
		state.token = opt.token;
		Cache.set(LOGIN_STATUS, opt.token, opt.time);
	},
	SETUID(state, val) {
		state.uid = val;
		Cache.set(UID, val);
	},
	UPDATE_LOGIN(state, token) {
		state.token = token;
	},
	LOGOUT(state) {
		Cache.clear(LOGIN_STATUS);
		Cache.clear(UID);
		Cache.clear(USER_INFO);
		Cache.clear('newcomerGift');
		state.token = undefined;
		state.uid = undefined
		state.userInfo = {}
	},
	BACKGROUND_COLOR(state, color) {
		state.color = color;
		document.body.style.backgroundColor = color;
	},
	UPDATE_USERINFO(state, userInfo) {
		state.userInfo = userInfo;
		state.identity = userInfo.identity;
		Cache.set(USER_INFO, userInfo);
		Cache.set('identity', userInfo.identity);
	},
	OPEN_HOME(state) {
		state.homeActive = true;
	},
	CLOSE_HOME(state) {
		state.homeActive = false;
	},
	SET_AUTOPLAY(state, data) {
		state.autoplay = data
		Cache.set(NON_WIFI_AUTOPLAY, data)
	},
	SET_PAGE_FOOTER(state, data) {
		state.tabBarData = data;
	},
	SET_BASIC_CONFIG(state, data) {
		state.diyProduct = data.product_detail;
		state.diyCategory = data.product_category;
		state.productVideoStatus = data.product_video_status;
		state.brokerageFuncStatus = data.brokerage_func_status;
		state.store_brokerage_status = data.store_brokerage_statu;
		state.store_brokerage_price = data.store_brokerage_price;
		state.system_channel_style = data.system_channel_style;
		state.division_apply_phone_verify = data.division_apply_phone_verify == 1
		state.brokerage_apply_phone_verify = data.brokerage_apply_phone_verify == 1
		state.channel_apply_phone_verify = data.channel_apply_phone_verify == 1
		state.supplier_apply_phone_verify = data.supplier_apply_phone_verify == 1
		state.brokerage_apply_explain = data.brokerage_apply_explain
		state.division_apply_explain = data.division_apply_explain
		state.channel_apply_explain = data.channel_apply_explain
		state.supplier_apply_explain = data.supplier_apply_explain
		Cache.set('division_apply_phone_verify', data.division_apply_phone_verify == 1);
		Cache.set('brokerage_apply_phone_verify', data.brokerage_apply_phone_verify == 1);
		Cache.set('channel_apply_phone_verify', data.channel_apply_phone_verify == 1);
		Cache.set('supplier_apply_phone_verify', data.supplier_apply_phone_verify == 1);
	},
	SET_ACTIVITY_MODAL(state, data){
		state.activityModalList = data.list;
		Cache.clear('closeModalSwiper'); //每次重新进入程序删除缓存标识
	},
};

const actions = {
	USERINFO({
		state,
		commit
	}, force) {
		if (state.userInfo !== null && !force)
			return Promise.resolve(state.userInfo);
		else
			return new Promise(reslove => {
				getUserInfo().then(res => {
					commit("UPDATE_USERINFO", res.data);
					Cache.set(USER_INFO, res.data);
					reslove(res.data);
				});
			}).catch(() => {

			});
	},
	async getPageFooter({
		commit
	}) {
		let res = await getDiyVersion(0);
		if (res.status == 200) {
			let diyVersion = uni.getStorageSync('diyVersionNav');
			if (res.data.version === diyVersion) {
				let pageFooter = uni.getStorageSync('pageFooterData');
				commit("SET_PAGE_FOOTER", pageFooter);
			} else {
				uni.setStorageSync('diyVersionNav', res.data.version);
				let result = await getNavigation();
				if (result.status == 200) {
					commit("SET_PAGE_FOOTER", result.data);
					uni.setStorageSync('pageFooterData', result.data);

				}
			}
		}
	},
	async getBasicConfig({commit}) {
		let result = await systemConfigApi();
		if (result.status == 200) {
			commit("SET_BASIC_CONFIG", result.data);
		}
	},
	async getActivityModal({commit}){
		let result = await getActivityModalListApi();
		if (result.status == 200 && result.data.list && result.data.list.length) {
			commit("SET_ACTIVITY_MODAL", result.data);
		}
	}
};

export default {
	state,
	mutations,
	actions
};