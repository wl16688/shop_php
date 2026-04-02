<template>
  <div>
    <div class="new-side" :style="{ height: SideHeight + 'px' }" v-show="!menuCollapse">
      <div class="flex-between-center aside-title">
        <span>{{ headerTitle }}</span>
        <i-header-collapse @on-toggle-drawer="handleToggleDrawer"/>
      </div>
      <div class="new-menu-list">
        <div class="new-side-submenu">
          <div v-for="(item, index) in filterSider" :key="index">
            <router-link :to="item.path" class="new-menu-title flex-between-center" v-if="!item.children">
              <div class="new-menu-side-title flex-y-center">
                <Icon :type="item.icon" :color="item.path == activePath ? '#2d8cf0' : '#C0C4CD'" />
                <span class="fs-14 pl-4" :class="{'menu-active':item.path == activePath}">{{ item.title }}</span>
              </div>
            </router-link>
            <div v-else>
              <a class="new-menu-title flex-between-center"  @click="toggleMenu(index, item)">
                <div class="new-menu-side-title flex-y-center">
                  <Icon :type="item.icon" color="#C0C4CD" />
                  <span class="fs-14 pl-4">{{ item.title }}</span>
                </div>
                <Icon :type="activeMenuIndex.includes(index) ? 'ios-arrow-down' : 'ios-arrow-up'"
                  color="#c0c4cc" size="14"
                  v-show="item.children && item.children.length" />
              </a>
              <transition name="slide">
                <div class="new-side-menu pl-20 flex flex-wrap" v-show="!activeMenuIndex.includes(index)">
                  <div class="w-50-p111" v-for="(menu, k) in item.children" :key="k">
                    <div class="h-38 flex-y-center pointer pl-13" @click="handleClick(menu.path)">
                      <span class="menu-name" :class="{'menu-active':menu.path == activePath}">{{ menu.title }}</span>
                    </div>
                  </div>
                </div>
              </transition>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="new-side" :style="{ height: SideHeight + 'px' }" v-show="menuCollapse">
      <div class="flex-center aside-title">
        <i-header-collapse @on-toggle-drawer="handleToggleDrawer"/>
      </div>
      <div>
        <div class="drop-menu-item" v-for="(item, index) in filterSider" :key="index">
          <Poptip trigger="hover" transfer-class-name="drop-menu-pop" placement="right-start">
            <span class="drop-menu-item-content flex-center" :class="{'drop-menu-active': index == firstIndex}">
              <Icon :type="item.icon" color="#303133" size="20" />
            </span>
            <template #content>
              <div v-show="item.children && item.children.length">
                <div class="new-side-menu">
                  <div class="w-full">
                    <div class="h-38 flex-y-center pl-13">
                      <span class="menu-name">{{ item.title }}</span>
                    </div>
                  </div>
                  <div class="w-full" v-for="(menu, k) in item.children" :key="k">
                    <div class="h-38 flex-y-center pointer pl-13" @click="handleClick(menu.path)">
                      <span class="menu-name" :class="{'menu-active':menu.path == activePath}">{{ menu.title }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-show="!item.children || !item.children.length">
                <div class="new-side-menu">
                  <div class="w-full">
                    <div class="h-24 flex-y-center pointer pl-13" @click="handleClick(item.path)">
                      <span class="menu-name">{{ item.title }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </template>
        </Poptip>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import { mapState, mapGetters } from "vuex";
import iHeaderCollapse from "../header-collapse";
export default {
  name: "newSide",
  data() {
    return {
      SideHeight: 0,
      activeMenuIndex: [],
      showDrawer: false,
      firstIndex: 0
    };
  },
  components: {
    iHeaderCollapse,
  },
  computed: {
    ...mapState("admin/layout", [
      "siderTheme",
      "menuAccordion",
      "menuCollapse",
    ]),
    ...mapState("admin/menu", ["activePath", "openNames", "headerName"]),
    ...mapGetters("admin/menu", ["filterSider", "filterHeader"]),
    headerTitle(){
      let title = ''
      this.filterHeader.map(item=>{
        if(item.header == this.headerName){
          title =  item.title
        }
      })
      return title
    }
  },
  watch:{
    $route: {
      handler() {
        this.activeMenuIndex = [];
        this.filterSider.map((item, index) => {
          if (item.children) {
            item.children.map((menu, k) => {
              if (menu.path == this.activePath) {
                this.firstIndex = index;
              }
            });
          }else{
            if(this.activePath == item.path){
              this.firstIndex = index;
            }
          }
        });
      },
      immediate: true,
    },
  },
  mounted() {
    // 获取窗口高度
    const windowHeight = window.innerHeight;
    this.SideHeight = windowHeight - 64;
  },
  methods: {
    handleClick(path){
      this.$router.push({ path });
    },
    toggleMenu(index, item) {
      if(!item.children.length){
        this.$router.push({ path: item.path });
        return
      }
      if(this.activeMenuIndex.includes(index)){
        this.activeMenuIndex.splice(this.activeMenuIndex.indexOf(index), 1)
      }else{
        this.activeMenuIndex.push(index)
      }
    },
    handleToggleDrawer(state) {
      if (typeof state === "boolean") {
        this.showDrawer = state;
      } else {
        this.showDrawer = !this.showDrawer;
      }
    },
  }
};

</script>
<style scoped lang="less">
.new-side {
  width: 100%;
  background: #fff;
  margin-top: 64px;
  overflow: auto;
  padding-bottom: 20px;
  // 隐藏滚动条
  &::-webkit-scrollbar {
    display: none;
  }
}
.aside-title {
  height: 52px;
  font-size: 16px;
  color: #303133;
  font-weight: 600;
  border-bottom: 1px solid #F0F1F5;
  margin-left: 16px;
  margin-right: 10px;
  overflow: hidden;
}
.mr-10{
  margin-right: 10px;
}
.new-menu-list {
  color: #303133;
}
.new-menu-title{
  padding: 9px 16px;
  height: 40px;
  margin-top: 8px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  position: relative;
  cursor: pointer;
  z-index: 1;
  transition: all .2s ease-in-out;
  // color: inherit;
  color: #606266;
}
.new-menu-title:hover{
  background-color: #f1f3f4;
  color: #2d8cf0;
}
.new-menu-title :hover{
  color: #2d8cf0;
}
.new-side-menu{
  color: #606266;
  .menu-name:hover{
    color: #2d8cf0;
  }
}
.menu-active{
  color: #2d8cf0;
}
.slide-enter-active, .slide-leave-active {
  transition: all .3s ease;
  max-height: 300px;
  overflow: hidden;
}

.slide-enter, .slide-leave-to {
  max-height: 0;
  opacity: 0;
}
.grid-col-2{
  display: grid;
  grid-template-columns: repeat(2, 1fr);
}
.pl-13{
  padding-left: 13px;
}
.drop-menu-item{
  height: 40px;
  padding: 0 10px;
  margin-top: 8px;
  transition: all .2s ease-in-out;
  .drop-menu-item-content{
    width: 40px;
    height: 40px;
    border-radius: 4px;
    cursor: pointer;
    &:hover{
      background-color: #f1f9ff;
    }
  }
}
.drop-menu-active{
  background-color: #f1f9ff;
}
/deep/ .i-layout-header-trigger{
  height: unset;
}
.drop-menu-pop{
  top: 129px;
}
.w-50-p111{
  width: 50%;
}
</style>
