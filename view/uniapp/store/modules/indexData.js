// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
import {getCartCounts} from '@/api/order.js';

export default {
	namespaced: true,
	state: {
		cartNum: 0
	},
	getters: {},
	actions:{
		getCartNum(context, value) {
			getCartCounts('','',1).then(res => {
				context.commit("setCartNum", res.data.count + '');
			}).catch(err=>{
				console.log(err.msg);
			})
		},
	},
	mutations: {
		setCartNum(state, data) {
			state.cartNum = data;
		}
	}
}
