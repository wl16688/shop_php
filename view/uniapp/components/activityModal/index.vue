<template>
	<view>
		<uni-popup ref="popup" :mask-click="false">
		    <view class="w-590 activity-popup">
		        <view class="w-full h-800">
					<swiper class="w-full h-full"
						:circular="true" 
						:indicator-dots="modalSwiper.length > 1 ? true : false" 
						autoplay
						:interval="3000" 
						:duration="500"
						indicator-color="rgba(255,255,255,0.5)"
						indicator-active-color="#ffffff">
						<swiper-item v-for="(item ,index) in modalSwiper" :key="index">
							<image :src="item.pic" mode="aspectFill" class="w-full h-full" @tap="pageJump(item)"></image>
						</swiper-item>
					</swiper>
				</view>
				<view class="flex-x-center mt-54">
					<text class="iconfont icon-ic_close1 fs-60 text--w111-fff" @tap="closeModal"></text>
				</view>
		    </view>
		</uni-popup>
	</view>
</template>

<script>
	import { getActivityModalRecordApi } from "@/api/activity.js"
	export default {
		name:"activityModal",
		props:{
			pageIndex:{
				type: Number,
				default: 0
			},
			pageId:{
				type: [Number, String],
				default: 0
			}
		},
		computed:{
			
		},
		data() {
			return {
				modalSwiper:[],
			};
		},
		computed:{
			activityModalData(){
				return this.$store.state.app.activityModalList
			}
		},
		watch:{
			pageIndex:{
				handler(val){
					setTimeout(()=>{
						if (!this.activityModalData.length) return;
						let dataList = this.activityModalData.filter(item => item.show_page.includes(val))
						.map(item => item.wechat_image).flat();
						let ids = this.activityModalData.filter(item => item.show_page.includes(val)).map(item => item.id);
						let closeModalSwiper = this.$Cache.get('closeModalSwiper');
						if (dataList.length && !closeModalSwiper) {
							this.modalSwiper = dataList;
							this.$refs.popup.open();
							this.recordLog(ids);
						}
					},500)
				},
				immediate: true
			}
		},
		methods:{
			pageJump(item){
				this.$util.JumpPath(item.url);
				this.$refs.popup.close();
			},
			closeModal(){
				this.$refs.popup.close();
			},
			recordLog(ids){
				getActivityModalRecordApi({ids: ids.join()}).then(res=>{
					this.$Cache.set('closeModalSwiper', true);
				}).catch(err=>{
					console.log(err);
				})
			}
		}
	}
</script>

<style lang="scss">
/* #ifdef H5 || APP-PLUS */
.activity-popup /deep/uni-swiper .uni-swiper-dot{
	width: 14rpx;
	height: 14rpx;
}
/* #endif */
/* #ifdef MP-WEIXIN */
.activity-popup /deep/.wx-swiper-dot~.wx-swiper-dot {
	width: 10rpx;
	height: 10rpx;
}
/* #endif */

</style>