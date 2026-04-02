<template>
  <div>
    <Modal
      v-model="isTemplate"
      scrollable
      footer-hide
      closable
      :title="formItem.id ? '编辑提货点' : '添加提货点'"
      :z-index="1"
      width="700"
      @on-cancel="cancel"
    >
      <div class="article-manager">
        <Card :bordered="false" dis-hover :padding="0">
          <Form
            ref="formItem"
            :model="formItem"
            :label-width="labelWidth"
            :label-position="labelPosition"
            :rules="ruleValidate"
            @submit.native.prevent
          >
            <Row type="flex" :gutter="24">
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem label="提货点名称：" prop="name" label-for="name">
                    <Input
                      v-model="formItem.name"
                      placeholder="请输入提货点名称"
                    />
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem label="提货点简介：" label-for="introduction">
                    <Input
                        type="textarea"
                        :autosize="{ minRows: 2, maxRows: 4 }"
                        v-model="formItem.introduction"
                        placeholder="请输入提货点简介"
                    />
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem
                    label="提货点手机号："
                    label-for="phone"
                    prop="phone"
                  >
                    <Input
                      v-model="formItem.phone"
                      placeholder="请输入提货点手机号"
                    />
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem
                    label="提货点地址："
                    label-for="address"
                    prop="address2"
                  >
                    <Cascader
                        :data="addressData"
                        :load-data="loadData"
                        v-model="formItem.address2"
                        @on-change="handleChange"
                        class="w-full"
                        ></Cascader>
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem
                    label="详细地址："
                    label-for="detailed_address"
                    prop="detailed_address"
                  >
                    <Input
                        type="textarea"
                        :autosize="{ minRows: 2, maxRows: 4 }"
                      v-model="formItem.detailed_address"
                      placeholder="请输入详细地址"
                    />
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem label="提货点营业：" label-for="day_time">
                    <TimePicker
                      type="timerange"
                      @on-change="onchangeTime"
                      v-model="formItem.day_time"
                      format="HH:mm:ss"
                      :value="formItem.day_time"
                      placement="bottom-end"
                      placeholder="请选择营业时间"
                      class="w-full"
                    ></TimePicker>
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem label="提货点logo：" prop="image">
                    <div class="acea-row">
                      <div v-if="formItem.image" class="pictrue">
                        <img v-lazy="formItem.image" @click="modalPicTap()" />
                      </div>
                      <div
                        v-else
                        class="upLoad acea-row row-center-wrapper"
                        @click="modalPicTap()"
                      >
                        <Icon type="ios-camera-outline" size="26" />
                      </div>
                    </div>
                  </FormItem>
                </Col>
              </Col>
              <Col span="24">
                <Col v-bind="grid">
                  <FormItem label="经纬度：" label-for="status2" prop="latlng">
                    <Tooltip>
                      <Input
                        search
                        enter-button="查找位置"
                        v-model="formItem.latlng"
                        placeholder="请查找位置"
                        @on-search="onSearch"
                        style="width: 436px;"
                      />
                      <div slot="content">请点击查找位置选择位置</div>
                    </Tooltip>
                  </FormItem>
                </Col>
              </Col>
            </Row>
            <div class="acea-row row-right">
                <Button @click="cancel">取消</Button>
                <Button
                  type="primary"
                  class="ml-14"
                  @click="handleSubmit('formItem')"
                  >{{ formItem.id ? "修改" : "提交" }}</Button
                >
            </div>
            <Spin size="large" fix v-if="spinShow"></Spin>
          </Form>
        </Card>

        <Modal
          v-model="modalMap"
          scrollable
          footer-hide
          closable
          title="查找位置"
          :mask-closable="false"
          :z-index="1"
          class="mapBox"
        >
          <iframe
            id="mapPage"
            width="100%"
            height="100%"
            frameborder="0"
            v-bind:src="keyUrl"
          ></iframe>
        </Modal>
      </div>
    </Modal>
  </div>
</template>

<script>
import { keyApi } from "@/api/setting";
import { cityApi, storeUpdateApi, storeGetInfoApi } from "@/api/store";
import { mapState } from "vuex";
export default {
  name: "systemStore",
  props: {},
  data() {
    const validatePhone = (rule, value, callback) => {
      if (!value) {
        return callback(new Error("请填写手机号"));
      } else if (!/^1[3456789]\d{9}$/.test(value)) {
        callback(new Error("手机号格式不正确!"));
      } else {
        callback();
      }
    };
    const validateUpload = (rule, value, callback) => {
      if (!this.formItem.image) {
        callback(new Error("请上传提货点logo"));
      } else {
        callback();
      }
    };
    return {
      isTemplate: false,
      spinShow: false,
      modalMap: false,
      addressData: [],
      formItem: {
        name: "",
        image: "",
        introduction: "",
        phone: "",
        address: "",
        address2: [],
        detailed_address: "",
        valid_time: [],
        day_time: [],
        latlng: "",
        id: 0,
      },
      ruleValidate: {
        name: [
          { required: true, message: "请输入提货点名称", trigger: "blur" },
        ],
        mail: [
          {
            required: true,
            message: "Mailbox cannot be empty",
            trigger: "blur",
          },
          { type: "email", message: "Incorrect email format", trigger: "blur" },
        ],
        address2: [
          {
            required: true,
            message: "请选择提货点地址",
            type: "array",
            trigger: "change",
          },
          
        ],
        valid_time: [
          {
            required: true,
            type: "array",
            message: "请选择核销时效",
            trigger: "change",
            fields: {
              0: { type: "date", required: true, message: "请选择年度范围" },
              1: { type: "date", required: true, message: "请选择年度范围" },
            },
          },
        ],
        day_time: [
          {
            required: true,
            type: "array",
            message: "请选择提货点营业时间",
            trigger: "change",
          },
        ],
        phone: [{ required: true, validator: validatePhone, trigger: "blur" }],
        detailed_address: [
          { required: true, message: "请输入详细地址", trigger: "blur" },
        ],
        image: [
          { required: true, validator: validateUpload, trigger: "change" },
        ],
        latlng: [{ required: true, message: "请选择经纬度", trigger: "blur" }],
      },
      grid: {
        xl: 20,
        lg: 20,
        md: 20,
        sm: 24,
        xs: 24,
      },
      keyUrl: "",
    };
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    labelWidth() {
      return this.isMobile ? undefined : 120;
    },
    labelPosition() {
      return this.isMobile ? "top" : "right";
    },
  },
  mounted: function () {
    window.addEventListener(
      "message",
      function (event) {
        // 接收位置信息，用户选择确认位置点后选点组件会触发该事件，回传用户的位置信息
        var loc = event.data;
        if (loc && loc.module === "locationPicker") {
          // 防止其他应用也会向该页面post信息，需判断module是否为'locationPicker'
          window.parent.selectAdderss(loc);
        }
      },
      false
    );
    window.selectAdderss = this.selectAdderss;
    this.getCityInfo({ pid: 0 });
  },
  methods: {
    cancel() {
      this.isTemplate = false;
      this.clearFrom();
    },
    clearFrom() {
      this.formItem = {
        name: "",
        image: "",
        introduction: "",
        phone: "",
        address: "",
        address2: [],
        detailed_address: "",
        valid_time: [],
        day_time: [],
        latlng: "",
        id: 0,
      };
    },
    // 选择经纬度
    selectAdderss(data) {
      this.formItem.latlng = data.latlng.lat + "," + data.latlng.lng;
      this.modalMap = false;
    },
    // key值
    getKey() {
      keyApi()
        .then(async (res) => {
          this.modalMap = true;
          let keys = res.data.key;
          this.keyUrl = `https://apis.map.qq.com/tools/locpicker?type=1&key=${keys}&referer=myapp`;
        })
        .catch((res) => {
          this.$Modal.confirm({
            title: "提示",
            content: "<p>" + res.msg + "</p>",
            onOk: () => {
              this.$router.push({ path: "/admin/setting/system_config" });
            },
            onCancel: () => {},
          });
          // this.$Message.error(res.msg);
        });
    },
    // 详情
    getInfo(id) {
      this.formItem.id = id;
      this.spinShow = true;
      storeGetInfoApi(id).then((res) => {
          let info = res.data.info || null;
          this.formItem = info || this.formItem;
          this.formItem.address2 = info.address2.map((item) =>
            Number(item)
          );
          this.spinShow = false;
        })
        .catch(function (res) {
            this.spinShow = false;
            this.$Message.error(res.msg);
        });
    },
    // 选择图片
    modalPicTap() {
      this.$imgModal((e) => {
        e.forEach((item) => {
          this.formItem.image = item.att_dir;
        });
      });
    },
    getCityInfo(data) {
      cityApi(data).then((res) => {
        this.addressData = res.data;
      });
    },
    loadData(item, callback) {
      item.loading = true;
      cityApi({ pid: item.value }).then((res) => {
        item.children = res.data;
        item.loading = false;
        callback();
      });
    },
    handleChange(e, selectedData) {
      this.formItem.address = selectedData.map((o) => o.label).join("");
      this.formItem.address2 = selectedData.map((o) => o.id);
    },
    // 核销时效
    onchangeDate(e) {
      this.formItem.valid_time = e;
    },
    // 营业时间
    onchangeTime(e) {
      this.formItem.day_time = e;
    },
    onSearch() {
      this.getKey();
    },
    // 提交
    handleSubmit(name) {
      this.$refs[name].validate((valid) => {
        if (valid) {
            storeUpdateApi(this.formItem.id, this.formItem).then(async (res) => {
              this.$Message.success(res.msg);
              this.isTemplate = false;
              this.$parent.getList();
              this.$refs[name].resetFields();
              this.clearFrom();
            })
            .catch((res) => {
              this.$Message.error(res.msg);
            });
        } else {
          return false;
        }
      });
    },
  },
};
</script>

<style scoped lang="stylus">

.mapBox >>> .ivu-modal-body
    height: 640px !important;
</style>
