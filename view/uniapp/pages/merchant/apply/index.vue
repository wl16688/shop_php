<template>
	<view :style="colorStyle">
		<view class="page-container">
			<view class="header-top relative" id="headerTop" :style="[headerBg]">
				<view class="w-full fixed-lt z-20">
					<view :style="{'height': sysHeight * 2 + 'rpx'}"></view>
					<view class="h-80 px-20 flex-between-center">
						<text class="iconfont icon-ic_leftarrow fs-40 text--w111-fff" @tap="pageBack"></text>
					</view>
				</view>
				<!-- <view class="rule-btn w-124 flex-center fs-24 text--w111-fff" :style="{ top: 100 + sysHeight + 'px' }" @tap="goRecord">申请记录</view> -->
			</view>
			<view class="bg-v-gradient pl-20 pr-20 pb-24" :style="{ minHeight: contentHeight + 'rpx' }">
				<scroll-view scroll-y="true" class="rd-24rpx" :style="{'height': contentHeight - 100 + 'rpx'}">
				<view class="bg--w111-fff rd-24rpx content-box">
					<view class="fs-30 fw-500 lh-42rpx">请填写以下信息</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							采购商名称
						</view>
						<input type="text" v-model="form.channel_name" placeholder="请输入采购商名称" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
					</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							联系人
						</view>
						<input type="text" v-model="form.real_name" placeholder="请输入联系人" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
					</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							联系电话
						</view>
						<input type="number" v-model="form.phone" placeholder="请输入联系电话" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
					</view>
					<view v-if="channelApplyPhoneVerify" class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							验证码
						</view>
						<view class="flex-y-center">
							<input type="number" v-model="form.code" placeholder="请输入验证码" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
							<button class="code-btn w-168 h-56 flex-center fs-24 ml-20" :disabled="disabled" @tap="code">{{ text }}</button>
						</view>
					</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							省市区
						</view>
						<view class="flex-1 flex-between-center" @tap="changeRegion">
							<view class="w-full fs-28 text-right text--w111-ccc pr-10" v-if="!addressInfo.length">请选择省市区</view>
							<view class="w-full fs-28 text-right pr-10" v-else>{{ addressText }}</view>
							<text class="iconfont icon-ic_rightarrow fs-24 text--w111-999"></text>
						</view>
					</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx">
							<text class="prefix">*</text>
							详细地址
						</view>
						<input type="text" v-model="form.address" placeholder="请输入详细地址" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
					</view>
					<view class="cell flex-between-center mt-64">
						<view class="fs-28 lh-40rpx pl-20">申请备注</view>
						<input type="text" v-model="form.remark" placeholder="请输入申请备注" placeholder-class="text--w111-ccc" class="fs-28 text-right" />
					</view>
					<view class="fs-28 lh-40rpx mt-64 flex-y-center">
						<text class="prefix">*</text>
						资质图片
					</view>
					<view class="fs-24 lh-34rpx text--w111-ccc mt-12">(图片最多可上传8张，图片格式支持JPG、PNG、JPEG）</view>
					<view class="grid-column-4 grid-gap-24rpx mt-24">
						<view class="relative h-148" v-for="(item, index) in form.certificate" :key="index">
							<image :src="item" mode="aspectFill" class="w-148 h-148 rd-16rpx"></image>
							<view class="abs-rt w-32 h-32 del-pic flex-center fs-24" @click="DelPic(index)">
								<text class="iconfont icon-ic_close text--w111-fff"></text>
							</view>
						</view>
						<view class="h-148 flex-col flex-center upload bg--w111-f5f5f5 text--w111-999 rd-16rpx" @click="uploadpic" v-if="form.certificate.length < 8">
							<text class="iconfont icon-ic_camera fs-42"></text>
							<text class="fs-24 lh-34rpx pt-8">上传图片</text>
						</view>
					</view>
					<view class="cell mt-64">
						<view class="fs-28 lh-40rpx">申请说明</view>
						<view class="text--w111-ccc mt-12">
							{{ channel_apply_explain }}
						</view>
					</view>
					<view class="flex-y-center mt-32">
						<text class="iconfont fs-30" :class="isSelect ? 'icon-a-ic_CompleteSelect' : 'icon-ic_unselect'" @tap="proviceSelect"></text>
						<text class="fs-24 text--w111-999 pl-12">已阅读并同意</text>
						<text class="font-red" @tap="getAgreement">《采购商认证协议》</text>
					</view>
					<button class="w-full h-88 rd-44rpx flex-center text--w111-fff fs-28 mt-48" :disabled="disabled" :class="isSelectStar ? 'bg-red' : 'bg-disabled'" @tap="submitSupply">
						提交申请
					</button>
				</view>
				</scroll-view>
			</view>
		</view>
		<Verify @success="success" :captchaType="captchaType" :imgSize="{ width: '330px', height: '155px' }" ref="verify"></Verify>
		<tui-modal :show="showModal" maskClosable custom @cancel="hideModal">
			<view class="tui-modal-custom">
				<view class="fs-32 fw-500 lh-44rpx text-center">采购商认证协议</view>
				<view class="fs-28 text--w111-666 lh-44rpx mt-24 desc-box">
					<!-- #ifdef MP-WEIXIN -->
					<rich-text :nodes="description"></rich-text>
					<!-- #endif -->
					<!-- #ifdef H5 || APP-PLUS -->
					<view v-html="description"></view>
					<!-- #endif -->
				</view>
				<view class="w-full h-72 rd-36rpx flex-center bg-red fs-26 text--w111-fff mt-32" @tap="hideModal">知道了</view>
			</view>
		</tui-modal>
		<areaWindow ref="areaWindow" :display="display" :address="addressInfo" @submit="OnChangeAddress" @changeClose="changeClose"></areaWindow>
	</view>
</template>

<script>
let sysHeight = uni.getWindowInfo().statusBarHeight;
import { toLogin } from '@/libs/login.js';
import { mapGetters } from 'vuex';
import { channelApplyApi, getCodeApi, registerVerify, channelApplyInfoApi, getUserAgreement, getAddressDetail } from '@/api/user.js';
import colors from '@/mixins/color';
import sendVerifyCode from '@/mixins/SendVerifyCode';
import { HTTP_REQUEST_URL, CAPTCHA_TYPE } from '@/config/app';
import Verify from '../components/verify/verify.vue';
import tuiModal from '@/components/tui-modal/index.vue';
import areaWindow from '@/components/areaWindow';
export default {
	data() {
		return {
			captchaType: CAPTCHA_TYPE,
			sysHeight: sysHeight,
			form: {
				channel_name: '', //采购商名称
				real_name: '', //联系人
				phone: '', //联系电话
				province: '', //省市区
				province_ids: [], //省市区ID
				address: '', //详细地址
				remark: '', //备注
				code: '', //验证码
				certificate: [] //资质图片
			},
			addressInfo: [],
			canvasWidth: '',
			canvasHeight: '',
			canvasStatus: false,
			isSelect: false,
			keyCode: '',
			id: 0,
			showModal: false,
			channelAgreement: '',
			tagStyle: {
				img: 'width:100%;display:block;'
			},
			display: false,
			disabled: false,
			back: '',
			channel_apply_explain:'',
			contentHeight: 0
		};
	},
	components: {
		Verify,
		tuiModal,
		areaWindow
	},
	mixins: [sendVerifyCode, colors],
	watch: {
		isLogin: {
			handler: function (newV, oldV) {
				if (newV) {
					// #ifndef MP
					this.getOrderProduct();
					// #endif
				}
			},
			deep: true
		}
	},
	computed: {
		...mapGetters(['isLogin', 'channelApplyPhoneVerify']),
		addressText() {
			return this.addressInfo.map((v) => v.label).join('/');
		},
		isSelectStar() {
			if (this.form.channel_name && this.form.real_name && this.form.phone && this.form.province && this.form.address && this.form.certificate.length) {
				if (this.channelApplyPhoneVerify && !this.form.code) {
					return false;
				}
				return true;
			}
		},
		headerBg() {
			return {
				backgroundImage: 'url(' + HTTP_REQUEST_URL + '/statics/images/activity/merchant_apply.png' + ')'
			};
		},
		description() {
			let description = this.channelAgreement;
			if (description) {
				description = description.replace(/<img/gi, '<img style="max-width:100%;height:auto;float:left;display:block" ');
				description = description.replace(/<video/gi, '<video style="width:100%;height:auto;display:block" ');
			}
			return description;
		}
	},
	onLoad(options) {
		this.back = options.back || '';
		this.getHeight();
		this.getInfo();
	},
	methods: {
		getHeight(){
			let {windowHeight, statusBarHeight } = uni.getWindowInfo();
			let that = this;
			const query = uni.createSelectorQuery().in(this);
			query.select("#headerTop")
			  .boundingClientRect((data) => {
					that.contentHeight = (windowHeight - statusBarHeight - data.height) * 2;
			  })
			  .exec();
		},
		/*** 删除图片* */
		DelPic: function (index) {
			let that = this,
				pic = this.form.certificate[index];
			that.form.certificate.splice(index, 1);
			that.$set(that.form, 'certificate', that.form.certificate);
		},
		/*** 上传文件 **/
		uploadpic: function () {
			let that = this;
			this.canvasStatus = true;
			that.$util.uploadImageChange(
				{ count: 8, url: 'upload/image' },
				function (res) {
					if (that.form.certificate.length < 8) that.form.certificate.push(res.data.url);
				},
				(res) => {
					this.canvasStatus = false;
				},
				(res) => {
					this.canvasWidth = res.w;
					this.canvasHeight = res.h;
				}
			);
		},
		code() {
			if (!this.form.phone)
				return this.$util.Tips({
					title: '请填写手机号码'
				});
			if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(this.form.phone))
				return this.$util.Tips({
					title: '请输入正确的手机号码'
				});
			this.$refs.verify.show();
		},
		success(data) {
			this.$refs.verify.hide();
			if (this.channelApplyPhoneVerify) {
				getCodeApi()
					.then((res) => {
						this.keyCode = res.data.key;
						this.getCode(data);
					})
					.catch((res) => {
						this.$util.Tips({
							title: res
						});
					});
			} else {
				this.channelRequest();
			}
		},
		async getCode(data) {
			let that = this;
			if (!this.form.phone)
				return that.$util.Tips({
					title: '请填写手机号码'
				});
			if (!/^1(3|4|5|7|8|9|6)\d{9}$/i.test(this.form.phone))
				return this.$util.Tips({
					title: '请输入正确的手机号码'
				});

			await registerVerify({
				phone: that.form.phone,
				type: 'channel',
				key: that.keyCode,
				captchaType: CAPTCHA_TYPE,
				captchaVerification: data.captchaVerification
			})
				.then((res) => {
					that.$util.Tips({
						title: res.msg
					});
					that.sendCode();
				})
				.catch((res) => {
					that.$util.Tips({
						title: res
					});
				});
		},
		proviceSelect() {
			this.isSelect = !this.isSelect;
		},
		getInfo() {
			channelApplyInfoApi()
				.then((res) => {
					this.channel_apply_explain = this.$store.state.app.channel_apply_explain;
					//从状态页返回
					if (this.back && res.data.id) {
						let keys = Object.keys(this.form);
						keys.map((i) => {
							this.form[i] = res.data[i];
						});
						this.addressInfo = res.data.province.map((item) => ({ label: item }));
						//从个人中心点进来并且填写过，需要先进入状态页
					} else if (!this.back && res.data.id) {
						uni.navigateTo({
							url: '/pages/merchant/apply/state'
						});
					}
				})
				.catch((err) => {
					return this.$util.Tips({
						title: err
					});
				});
		},
		submitSupply() {
			if (!this.isSelectStar)
				return this.$util.Tips({
					title: '请完整填写表单信息'
				});
			if (!this.isSelect)
				return this.$util.Tips({
					title: '请阅读并同意协议'
				});
			if (this.channelApplyPhoneVerify) {
				this.channelRequest();
			} else {
				this.code();
			}
		},
		channelRequest() {
			channelApplyApi(this.form)
				.then((res) => {
					uni.navigateTo({
						url: '/pages/merchant/apply/state'
					});
				})
				.catch((err) => {
					return this.$util.Tips({
						title: err
					});
				});
		},
		pageBack() {
			uni.reLaunch({
				url: '/pages/user/index'
			});
		},
		goRecord() {
			uni.navigateTo({
				url: '/pages/users/agent/record'
			});
		},
		getAgreement() {
			getUserAgreement('channel')
				.then((res) => {
					this.channelAgreement = res.data.content;
					this.showModal = true;
				})
				.catch((err) => {
					that.$util.Tips({
						title: err.msg
					});
				});
		},
		hideModal() {
			this.showModal = false;
		},
		changeRegion() {
			this.display = true;
		},
		changeClose() {
			this.display = false;
		},
		OnChangeAddress(address) {
			this.latitude = '';
			this.longitude = '';
			this.addressInfo = address;
			console.log(address);
			this.form.province = address.map((v) => v.label).join(',');
			this.form.province_ids = address.map((v) => v.id);
		}
	}
};
</script>

<style>
.page-container{
	height: 100vh;
	background: #eb3b26;
}
.header-top {
	width: 100%;
	height: 358rpx;
	background-size: cover;
}
.rule-btn {
	height: 48rpx;
	background: rgba(0, 0, 0, 0.15);
	border-radius: 24rpx 0 0 24rpx;
	position: absolute;
	right: 0;
}
.prefix {
	color: #e93323;
	font-size: 20rpx;
	padding-right: 12rpx;
}
.bg-v-gradient {
	background: linear-gradient(180deg, #fe7015 0%, #eb3b26 100%);
}
.content-box {
	padding: 48rpx 32rpx 40rpx;
}
.code-btn {
	border: 1px solid #e93323;
	color: #e93323;
	border-radius: 28rpx;
}
.upload {
	border: 1rpx dashed #ccc;
}
.del-pic {
	background-color: #999;
	border-radius: 0 16rpx 0 16rpx;
}
.icon-a-ic_CompleteSelect,
.font-red {
	color: #e93323;
}
.icon-ic_unselect {
	color: #ccc;
}
.bg-red {
	background-color: #e93323;
}
.bg-disabled {
	background-color: rgba(233, 51, 35, 0.5);
}
.desc-box {
	max-height: 700rpx;
	overflow-y: auto;
}
.modal-bottom {
	height: 136rpx;
	border-radius: 0 0 32rpx 32rpx;
	background-color: #fff;
}
</style>
