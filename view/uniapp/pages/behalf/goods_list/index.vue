<template>
	<view>
		<template1 v-if="level == 3" :userId="userId" :touristId="touristId" :is_channel="is_channel" ref="classOne"></template1>
		<template2 v-if="level == 2" :userId="userId" :touristId="touristId" :is_channel="is_channel" ref="classTwo"></template2>
	</view>
</template>

<script>
	import { mapGetters } from 'vuex';
	import template1 from './template/template1.vue';
	import template2 from './template/template2.vue';
	export default {
		name:'behalfGoodsList',
		data() {
			return {
				userId:0,
				touristId:0,
				is_channel: ''
			}
		},
		components: {
			template1,
			template2
		},
		computed:{
			...mapGetters(['isLogin', 'uid', 'diyCategory']),
			level(){
				return this.diyCategory.level
			},
		},
		onLoad(e) {
			this.userId = e.uid || 0;
			this.is_channel = e.is_channel || '';
			if(e.uid == 0 && !this.$Cache.get('touristId')){
				let tid =  Math.floor(Math.random() * 90000) + 10000;
				this.$Cache.set('touristId', tid)
				this.touristId = tid;
			}else if(e.uid == 0 && this.$Cache.get('touristId')){
				this.touristId = this.$Cache.get('touristId');
			}else if(e.uid != 0){
				this.touristId = 0;
			}
		},
	}
</script>

<style>

</style>
