<template>
	<view>
		<template3 v-if="level == 2" :level="level"></template3>
		<template2 v-else :level="level"></template2>
		<tab-bar></tab-bar>
	</view>
</template>

<script>
	import template2 from './template/template2.vue';
	import template3 from './template/template3.vue';
	import tabBar from "../components/tabBar/index.vue"
	import { mapGetters } from 'vuex';
	export default {
		data() {
			return {

			};
		},
		components: {
			template2,
			template3,
			tabBar
		},
		computed:{
			...mapGetters(['isLogin', 'uid', 'diyCategory']),
			level(){
				return this.diyCategory.level
			},
		},
		onLoad() {
			let identity = this.$store.state.app.identity;
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
		}

	}
</script>

<style lang="scss">

</style>
