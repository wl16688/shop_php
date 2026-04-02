<template>
  <div>
    <PageHeader class="product_tabs" hidden-breadcrumb>
      <div slot="title" class="acea-row row-middle">
        <span v-text="'抽奖配置'" class="mr20"></span>
      </div>
    </PageHeader>
    <Card :bordered="false" dis-hover class="mt-16">
      <Form :model="formData" :label-width="100">
        <FormItem label="积分抽奖：">
          <Select v-model="formData.point" clearable class="input-add">
            <Option
              v-for="item in list.point"
              :value="item.id"
              :key="item.id"
              :label="item.name"
            ></Option>
          </Select>
        </FormItem>
        <FormItem label="支付抽奖：">
          <Select v-model="formData.pay" clearable class="input-add">
            <Option
              v-for="item in list.pay"
              :value="item.id"
              :key="item.id"
              :label="item.name"
            ></Option>
          </Select>
        </FormItem>
        <FormItem label="评价抽奖：">
          <Select v-model="formData.evaluate" clearable class="input-add">
            <Option
              v-for="item in list.evaluate"
              :value="item.id"
              :key="item.id"
              :label="item.name"
            ></Option>
          </Select>
        </FormItem>
        <FormItem label="关注公众号：">
          <Select v-model="formData.follow" clearable class="input-add">
            <Option
              v-for="item in list.follow"
              :value="item.id"
              :key="item.id"
              :label="item.name"
            ></Option>
          </Select>
        </FormItem>
        <FormItem>
          <Button type="primary" @click="save">保存</Button>
        </FormItem>
      </Form>
    </Card>
  </div>
</template>

<script>
import { factorListApi, factorUseApi } from "@/api/lottery";
export default {
  name: "lotteryConfig",
  data() {
    return {
      formData: {
        evaluate: "", // 评价支付
        pay: "", // 支付
        point: "", // 积分
        follow: "", // 关注
      },
      list: {
        evaluate: [], // 评价支付
        pay: [], // 支付
        point: [], // 积分
        follow: [], // 关注
      },
    };
  },
  computed: {},
  created() {
    this.getList();
  },
  methods: {
    getList() {
      factorListApi().then((res) => {
        this.list = res.data;
        this.formData = res.data.info;
        console.log(res);
      });
    },
    save() {
      factorUseApi(this.formData).then((res) => {
        this.$Message.success(res.msg);
      }).catch((err) => {
        this.$Message.error(err.msg);
      });
    },
  },
};
</script>

<style scoped lang="less">
.content_width {
  width: 414px;
}

.info {
  color: #888;
  font-size: 12px;
}
</style>
