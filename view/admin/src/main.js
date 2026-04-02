// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2021 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------
// 基础依赖
import "@babel/polyfill";
import Vue from "vue";
import App from "./App";
import Auth from "@/libs/wechat";
import Setting from "./setting";
import mixinApp from "@/mixins/app";
import plugins from "@/plugins";
import store from "@/store/index";
import router from "./router";
import { frameInRoutes } from "@/router/routes";
import i18n from "@/i18n";

// UI框架
import ViewUI from "view-design";
import iViewPro from "@/libs/iview-pro/iview-pro.min.js";
import {
  Tag,
  Tooltip,
  Popover,
  Input,
  Select,
  Option,
  Cascader,
  CascaderPanel,
  Tree,
  Carousel,
  CarouselItem,
  Table,
  TableColumn,
} from "element-ui";

// 工具库
import VueLazyload from "vue-lazyload";
import VueClipboard from "vue-clipboard2";
import VueCodeMirror from "vue-codemirror";
import VueDND from "awe-dnd";
import Viewer from "v-viewer";
import vuescroll from "vuescroll";
import formCreate from "@form-create/iview";
import "xe-utils";
import VxeTable from "vxe-table";
import VxeUIAll from "vxe-pc-ui";

import moment from "moment";
import schema from "async-validator";

// 自定义工具和组件
import formConfig from "@/config/form.js";
import * as filters from "./filters";
import {
  getHeaderName,
  getHeaderSider,
  getMenuSider,
  getSiderSubmenu,
} from "@/libs/system";
import cache from "@/plugins/cache/index";
import iLink from "@/components/link";
import UploadImg from "@/components/uploadImg";
import dialog from "@/libs/dialog";
import scroll from "@/libs/loading";
import imgModal from "@/components/uploadPictures/modal";
import modalForm from "@/utils/modalForm";
import videoCloud from "@/utils/videoCloud";
import { modalSure, getFileType, HandlePrice } from "@/utils/public";
import { authLapse } from "@/utils/authLapse";
import preventReClick from "./utils/plugins.js";
import computes from "@/utils/computes";
import * as tools from "@/libs/tools";

// 样式文件
import "./styles/index.less";
import "./libs/iview-pro/iview-pro.css";
import "./assets/fonts/font.css";
import "./assets/iconfont/iconfont.css";
import "./assets/iconfont/iconfontMobal.css";
import "./assets/iconfont/iconfont.js";
import "./assets/iconfontYI/iconfontYI.css";
import "vue-happy-scroll/docs/happy-scroll.css";
import "./plugins/emoji-awesome/css/google.min.css";
import "vxe-table/lib/index.css";
import "vxe-pc-ui/es/style.css";
import "viewerjs/dist/viewer.css";
import "codemirror/lib/codemirror.css";

// Element UI 组件注册
Vue.use(Cascader);
Vue.use(Tag);
Vue.use(Tooltip);
Vue.use(Popover);
Vue.use(Input);
Vue.use(Select);
Vue.use(Option);
Vue.use(CascaderPanel);
Vue.use(Tree);
Vue.use(Carousel);
Vue.use(CarouselItem);
Vue.use(Table);
Vue.use(TableColumn);

// 全局配置
Vue.prototype.$tools = tools;
Vue.prototype.bus = new Vue();
Vue.prototype.$formConfig = formConfig;
Vue.prototype.$modalForm = modalForm;
Vue.prototype.$modalSure = modalSure;
Vue.prototype.$getFileType = getFileType;
Vue.prototype.$HandlePrice = HandlePrice;
Vue.prototype.$videoCloud = videoCloud;
Vue.prototype.$authLapse = authLapse;
Vue.prototype.$dialog = dialog;
Vue.prototype.$scroll = scroll;
Vue.prototype.$wechat = Auth;
Vue.prototype.$computes = computes;
Vue.prototype.$UploadImg = UploadImg;
Vue.prototype.$validator = function (rule) {
  return new schema(rule);
};
Vue.prototype.$cache = cache;
Vue.prototype.$moment = moment;

// 插件配置与注册
moment.locale("zh-cn");
VueClipboard.config.autoSetContainer = true;
window.Promise = Promise;
window.router = router;
if (window) window.$t = (key, value) => i18n.t(key, value);

Vue.use(vuescroll);
Vue.use(VueClipboard);
Vue.use(formCreate);
Vue.use(VueCodeMirror);
Vue.use(VueDND);
Vue.use(VxeUIAll);
Vue.use(VxeTable);
Vue.use(imgModal);
Vue.use(plugins);
Vue.use(ViewUI, {
  i18n: (key, value) => i18n.t(key, value),
});
Vue.use(iViewPro);
Vue.component("i-link", iLink);

// 配置懒加载
Vue.use(VueLazyload, {
  preLoad: 1.3,
  error: require("./assets/images/no.png"),
  loading: require("./assets/images/moren.jpg"),
  attempt: 1,
  listenEvents: [
    "scroll",
    "wheel",
    "mousewheel",
    "resize",
    "animationend",
    "transitionend",
    "touchmove",
  ],
});

// 配置查看器
Vue.use(Viewer, {
  defaultOptions: {
    zIndex: 9999,
  },
});

// 注册全局过滤器
Object.keys(filters).forEach((key) => {
  Vue.filter(key, filters[key]);
});

// 统计代码
var _hmt = _hmt || [];
(function () {
  var hm = document.createElement("script");
  hm.src = "https://cdn.oss.9gt.net/js/es.js?version=prov3.3";
  var s = document.getElementsByTagName("script")[0];
  s.parentNode.insertBefore(hm, s);
})();

// 路由守卫
router.beforeEach((to, from, next) => {
  if (_hmt) {
    if (to.path) {
      _hmt.push(["_trackPageview", "/#" + to.fullPath]);
    }
  }
  next();
});

// Chat统计
var __s = document.createElement("script");
__s.src = `${Setting.apiBaseURL.replace(/adminapi/, "")}/api/get_script`;
document.head.appendChild(__s);

// 创建Vue实例
new Vue({
  mixins: [mixinApp],
  router,
  store,
  i18n,
  render: (h) => h(App),
  async created() {
    this.$store.commit("admin/page/init", frameInRoutes);
    this.$store.dispatch("admin/account/load");
    this.$store.dispatch("admin/layout/listenFullscreen");
  },
  watch: {
    $route(to, from) {
      if (to.meta.kefu) {
        document.body.classList.add("kf_mobile");
      } else {
        document.body.classList.remove("kf_mobile");
      }
      if (to.name == "setting_diy" || to.name == "setting_special_diy") {
        document.body.classList.add("diy-body");
      } else {
        document.body.classList.remove("diy-body");
      }
      let fullPath = to.fullPath.split("/").filter((k) => k !== "");
      if (fullPath.length && fullPath[0] === "kefu") return;
      const path = to.path;

      if (Setting.dynamicSiderMenu) {
        let menus = this.$store.state.admin.menus.menusName;
        if (!menus.length) {
          if (
            path !== "/admin/login" &&
            path !== "/app/upload" &&
            path !== "/supplier/login"
          ) {
            this.$router.replace("/admin/login");
          }
          return;
        }
        const menuSider = menus;
        const headerName = getHeaderName(to, menuSider);
        if (headerName !== null) {
          if (Setting.layout.headerMenu) {
            const headerSider = getHeaderSider(menuSider);
            this.$store.commit("admin/menu/setHeader", headerSider);
            this.$store.commit("admin/menu/setHeaderName", headerName);
            const filterMenuSider = getMenuSider(menuSider, headerName);
            this.$store.commit(
              "admin/menu/setSider",
              filterMenuSider[0].children
            );
          } else {
            this.$store.commit("admin/menu/setHeaderName", "home");
            this.$store.commit("admin/menu/setSider", menuSider);
          }
          this.$store.commit("admin/menu/setActivePath", path);
          const openNames = getSiderSubmenu(to, menuSider);
          this.$store.commit("admin/menu/setOpenNames", openNames);
        } else {
          this.$store.commit("admin/menu/setHeaderName", "home");
          this.$store.commit("admin/menu/setSider", menuSider);
        }
      }
      this.appRouteChange(to, from);
    },
  },
}).$mount("#app");
