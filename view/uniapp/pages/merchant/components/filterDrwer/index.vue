<script>
	import baseDrawer from '@/components/tui-drawer/tui-drawer.vue';
	export default {
		props: {
			visible: {
				type: Boolean,
				default: false,
			},
			cateList:{
				type:Array,
				default: () => []
			},
			brandList:{
				type:Array,
				default: () => []
			},
		},
		data(){
			return {
				cateGoryList:[],
				brand_id: [],
				cateId:[],
				sid:''
			}
		},
		watch:{
			cateList:{
				handler(val){
					this.cateGoryList = val;
					this.cateGoryList.map(item=>{
						this.$set(item,'more', true);
					})
				},
				immediate: true
			},
		},
		computed: {
			fixedTop() {
				// #ifdef MP || APP-PLUS
				return uni.getWindowInfo().statusBarHeight
				// #endif
				// #ifdef H5
				return 20
				// #endif
			},
		},
		components: {
			baseDrawer
		},
		methods: {
			closeDrawer() {
				this.$emit('closeDrawer');
			},
			checkBrand(item){
				if(this.brand_id.includes(item.id)){
					this.brand_id = this.brand_id.filter(function (ele){return ele != item.id;});
				}else{
					this.brand_id.push(item.id);
				}
			},
			checkCate(item){
				this.sid = item.id;
			},
			checkMore(item){
				item.more = !item.more
			},
			confirmFilter(){
				let data = {
					brand_id: this.brand_id.join(','),
					sid: this.sid
				};
				this.$emit('filterChange',data);
			},
			resetFilter(){
				this.brand_id = [];
				this.sid = '';
				let data = {
					brand_id: '',
					sid: ''
				};
				this.$emit('filterChange',data);
			}
		}
	}
</script>
<template>
	<view>
		<base-drawer mode="right" :visible="visible" background-color="transparent" mask maskClosable
			@close="closeDrawer">
			<view class="drawer_box bg--w111-fff px-32 h-full">
				<scroll-view scroll-y="true" style="height: 100vh;">
					<view :style="{height:fixedTop + 'px'}"></view>
					<view class="h-80 flex-center fs-34 fw-500 text--w111-333">筛选</view>
					<view class="activity_box py-24">
						<view v-if="brandList.length">
							<view class="fs-28 text--w111-333 fw-500 mt-24">品牌</view>
							<view class="grid-column-3 box_gap mt-24">
								<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
								v-for="item in brandList" :key="item.id"
								:class="{active: brand_id.includes(item.id)}"
								@tap="checkBrand(item)">
									<text class="inline-block w-full line1 px-12 text-center">{{item.brand_name}}</text>
								</view>
							</view>
						</view>
						<view v-if="cateGoryList.length">
							<view class="fs-28 text--w111-333 fw-500 mt-48">分类</view>
							<view v-for="(item,i) in cateGoryList" :key="i">
								<view class="flex-between-center mt-48">
									<text class='fs-24'>{{item.cate_name}}</text>
									<view class="flex-y-center fs-20 text--w111-999 lh-34rpx" @tap="checkMore(item)">
										<text>{{ item.more ? '收起' : '展开' }}</text>
										<text class="iconfont fs-20" :class="item.more ? 'icon-ic_uparrow' : 'icon-ic_downarrow'"></text>
									</view>
								</view>
								<view class="grid-column-3 box_gap mt-24" v-show="item.more">
									<view class="h-56 rd-28rpx bg--w111-f5f5f5 flex-center fs-24 text--w111-333"
									v-for="(cate,k) in item.children" :key="k"
									:class="{active: cate.id == sid}"
									@tap="checkCate(cate)">
										<text class="inline-block w-full line1 px-12 text-center">{{cate.cate_name}}</text>
									</view>
								</view>
							</view>

						</view>
					</view>
					<view class="pb-safe">
						<view class="h-112"></view>
					</view>
				</scroll-view>
				<view class="fixed-lb pb-safe bg--w111-fff w-full">
					<view class="px-32 flex-between-center h-112">
						<view class="w-296 h-72 rd-40rpx flex-center text-mer con_border bg--w111-fff"
							@tap="resetFilter()">重置</view>
						<view class="w-296 h-72 rd-40rpx flex-center text--w111-fff mer-bg" @tap="confirmFilter()">确定</view>
					</view>
				</view>
			</view>
		</base-drawer>
	</view>
</template>
<style lang="scss">
	.drawer_box {
		width: 668rpx;
		border-radius: 40rpx 0 0 40rpx;
		overflow: auto;
	}
	.box_gap {
		grid-row-gap: 24rpx;
		grid-column-gap: 26rpx;
	}
	.con_border{
		border: 1rpx solid $primary-merchant;
	}
	.active{
		border: 1px solid $primary-merchant;
		color: $primary-merchant;
		background: $light-primary-merchant;
	}
	.text-mer{
		color: $primary-merchant;
	}
</style>
