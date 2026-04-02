<template>
<!-- 供应商-供应商列表 -->
  <div>
    <Card :bordered="false" dis-hover class="ivu-mt" :padding="0">
      <Form
        inline
        class="formValidate mt20"
        :label-width="80"
        @submit.native.prevent
      >
        <FormItem label="搜索：" label-for="store_name">
          <Input
            placeholder="请输入供应商名称"
            v-model="keywords"
           class="input-add"
          />
          <Button type="primary" @click="getSupplier()">查询</Button>
        </FormItem>
      </Form>
    </Card>

    <Card :bordered="false" dis-hover class="ivu-mt">
      <!-- 操作 -->
      <Button type="primary" class="btn" @click="addSupplier"
        >添加供应商</Button
      >
      <!-- 表格 -->
      <Table
        :columns="columns"
        :data="tabList"
        ref="table"
        highlight-row
        no-data-text="暂无数据"
        no-filtered-data-text="暂无筛选结果"
        class="orderData ivu-mt"
      >
        <!-- 状态 -->
        <template slot-scope="{ row, index }" slot="is_show">
          <i-switch
            v-model="row.is_show"
            @on-change="onchangeIsShow(row)"
            :true-value="1"
            :false-value="0"
            size="large"
          >
            <span slot="open">开启</span>
            <span slot="close">关闭</span>
          </i-switch>
        </template>

        <!-- 操作 -->
        <template slot-scope="{ row, index }" slot="action">
          <a @click="goSupplier(row)">进入</a>
          <Divider type="vertical" />
          <a @click="edit(row)">编辑</a>
          <Divider type="vertical" />
          <a @click="del(row, '删除供应商', index)" class="del">删除</a>
        </template>
        <!-- no-data-text="暂无数据" highlight-row -->
        <!-- no-filtered-data-text="暂无筛选结果" -->
      </Table>
      <div>
        <!-- 分页 -->
        <div class="acea-row row-right page">
          <Page
            :total="page.total"
            :current="page.pageNum"
            show-elevator
            show-total
            @on-change="pageChange"
            :page-size="page.pageSize"
            @on-page-size-change="limitChange"
            show-sizer
          />
        </div>
      </div>
    </Card>
  </div>
</template>
<script>
import { mapState, mapActions } from 'vuex'
import util from '@/libs/util'
import Setting from '@/setting'
import { supplierList, putSupplierStatus, supplierLogin } from '@/api/supplier'
import Template from '../../setting/devise/template.vue';
import {
  getHeaderName,
  getHeaderSider,
  getMenuSider,
  getSiderSubmenu,
} from "@/libs/system";
export default {
  name: '',
  components: { Template },
  props: {},
  data() {
    return {
      keywords: '',
      BaseURL: Setting.apiBaseURL.replace(/adminapi/, 'supplier/home/'),
      formValidate: {
        status: '',
        extract_type: '',
        nireid: '',
        data: 'thirtyday',
        page: 1,
        limit: 20,
      },
      loading: false,
      page: {
        total: 0, // 总条数
        pageNum: 1, // 当前页
        pageSize: 15, // 每页显示条数
      },
      columns: [
        {
          title: 'ID',
          key: 'id',
          minWidth: 80,
        },
        {
          title: '供应商',
          minWidth: 100,
          key: 'supplier_name',
        },
        {
          title: '联系人姓名',
          key: 'name',
          minWidth: 100,
        },
        {
          title: '联系方式',
          key: 'phone',
          minWidth: 100,
        },
        {
          title: '供应商状态',
          slot: 'is_show',
          minWidth: 100,
        },
        {
          title: '创建时间',
          key: '_add_time',
          minWidth: 100,
        },
        {
          title: '备注',
          key: 'mark',
          minWidth: 100,
        },
        {
          title: '排序',
          key: 'sort',
          minWidth: 100,
        },
        {
          title: '操作',
          slot: 'action',
          minWidth: 150,
        },
      ],
      tabList: [],
    }
  },
  computed: {
    ...mapState('admin/layout', ['isMobile', 'logoutConfirm']),
    labelWidth() {
      return this.isMobile ? undefined : 80
    },
    labelPosition() {
      return this.isMobile ? 'top' : 'right'
    },
  },
  watch: {},
  created() {
    this.getSupplier()
  },
  mounted() {},
  methods: {
    ...mapActions('admin/account', [
        'logout'
    ]),
    addSupplier() {
      this.$router.push({ path: '/admin/supplier/supplierAdd' })
    },

    pageChange(index) {
      this.page.pageNum = index
      this.getSupplier()
    },

    limitChange(limit) {
      this.page.pageSize = limit
      this.getSupplier()
    },

    // 获取供应商列表
    getSupplier() {
      let data = {
        keywords: this.keywords,
        page: this.page.pageNum, // 当前页
        limit: this.page.pageSize, // 每页显示条数
      }
      supplierList(data)
        .then(async (res) => {
          this.tabList = res.data.list
          if (res.data.status == 200) {
          }
          this.page.total = res.data.count
        })
        .catch((res) => {
          this.$Message.error(res.msg)
        })
    },

    // 编辑
    edit(row) {
      this.$router.push({ path: '/admin/supplier/supplierAdd/' + row.id })
    },

    // 修改状态
    onchangeIsShow(row) {
      putSupplierStatus(row.id, row.is_show)
        .then(async (res) => {
          this.$Message.success(res.msg)
        })
        .catch((res) => {
          this.$$Message.error(res.msg)
        })
    },
    getChilden(data) {
      if (data.length && data[0].children) {
        return this.getChilden(data[0].children);
      }
      return data[0].path;
    },
    // 进入供应商
    goSupplier(row) {
      supplierLogin(row.id).then(async (res) => {
          let data = res.data
          let expires = data.expires_time
          // 记录用户登陆信息
          util.cookies.set("uuid", res.data.user_info.id, {
            expires: expires,
          });
          util.cookies.set("token", res.data.token, {
            expires: expires,
          });
          util.cookies.set("expires_time", res.data.expires_time, {
            expires: expires,
          });
          const db = await this.$store.dispatch("admin/db/database", {
            user: true,
          });
          // 保存菜单信息
          // db.set('menus', res.data.menus).set('unique_auth', res.data.unique_auth).set('user_info', res.data.user_info).write();
          db.set("unique_auth", res.data.unique_auth).set("user_info", res.data.user_info).write();
          const menuSider = res.data.menus;
          this.$store.commit("admin/menus/getmenusNav", menuSider);
          let headerSider = getHeaderSider(res.data.menus);
          this.$store.commit("admin/menu/setHeader", headerSider);
          let toPath = this.getChilden(res.data.menus);
          // 获取侧边栏菜单
          const headerName = getHeaderName(
            {
              path: toPath,
              query: {},
              params: {},
            },
            menuSider
          );
          const filterMenuSider = getMenuSider(menuSider, headerName);
          // 指定当前显示的侧边菜单
          this.$store.commit( "admin/menu/setSider",filterMenuSider[0].children);
          //设置首页path
          this.$store.commit("admin/menus/setIndexPath", toPath);
          // 记录用户信息
          this.$store.dispatch("admin/user/set", {
            name: res.data.user_info.account,
            access: res.data.unique_auth,
            logo: res.data.logo,
          });
          localStorage.setItem("isSupplier", 1);
          this.$router.replace({
            path: toPath
          }).then(() => {
            window.location.reload();
          });
          return
        })
        .catch((res) => {
          this.$Message.error(res.msg)
        })
    },

    // 删除
    del(row, tit, num) {
      let data = {
        ids: row.id,
      }
      let delfromData = {
        title: tit,
        num: 0,
        url: `/supplier/supplier/${data.ids}`,
        method: 'DELETE',
        ids: data,
      }
      this.$modalSure(delfromData)
        .then((res) => {
          this.$Message.success(res.msg)
          this.tabList.splice(num, 1);
          if (!this.tabList.length) {
            this.page.pageNum =
                this.page.pageNum == 1 ? 1 : this.page.pageNum - 1;
          }
          this.getSupplier()
        })
        .catch((res) => {
          this.$Message.error(res.msg)
        })
    },
  },
}
</script>
<style scoped lang="less">
.input-add {
 width: 250px;
 margin-right: 14px
}
.btn {
  margin: 0 0 10px 0px;
}
.del {
  margin-left: 10px;
}
// .card {
//   margin-top: 15px;
// }
.ivu-form-inline {
  padding-top: 20px;
}
</style>
