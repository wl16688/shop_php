// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

export default {
	token: state => state.app.token,
	isLogin: state => !!state.app.token,
	backgroundColor: state => state.app.backgroundColor,
	userInfo: state => state.app.userInfo || {},
	uid: state => state.app.uid,
	homeActive: state => state.app.homeActive,
	divisionApplyPhoneVerify: state => state.app.division_apply_phone_verify,
	brokerageApplyPhoneVerify: state => state.app.brokerage_apply_phone_verify,
	channelApplyPhoneVerify: state => state.app.channel_apply_phone_verify,
	supplierApplyPhoneVerify: state => state.app.supplier_apply_phone_verify,
	home: state => state.app.home,
	cartNum: state => state.indexData.cartNum,
	diyProduct: state => state.app.diyProduct,
	diyCategory: state => state.app.diyCategory,
	productVideoStatus: state => state.app.productVideoStatus,
	brokerageFuncStatus: state => state.app.brokerageFuncStatus,
	tabBarData: state => state.app.tabBarData,
	storeBrokerageStatus: state => state.app.store_brokerage_status,
	storeBrokeragePrice: state => state.app.store_brokerage_price,
	activityModalData: state => state.app.activityModalList,
};
