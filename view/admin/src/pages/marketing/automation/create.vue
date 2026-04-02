<template>
  <div>
    <div class="i-layout-page-header">
      <PageHeader class="product_tabs" hidden-breadcrumb>
        <div class="flex-y-center" slot="title">
          <div class="flex-y-center pointer" @click="$router.go(-1)">
            <div class="font-sm after-line">
              <span class="iconfont iconfanhui"></span>
              <span class="pl10">返回</span>
            </div>
          </div>
          <span
            v-text="$route.params.id ? `编辑${pageTitle}` : `添加${pageTitle}`"
            class="mr20 ml16"
          ></span>
        </div>
      </PageHeader>
      <Card :bordered="false" dis-hover class="ivu-mt mb79">
        <Form
          class="formValidate"
          ref="formValidate"
          :rules="ruleValidate"
          :model="formValidate"
          :label-width="95"
          label-position="right"
          @submit.native.prevent
        >
          <FormItem label="任务名称:" prop="name">
            <Input
              v-model="formValidate.name"
              placeholder="请输入任务名称"
              maxlength="30"
              show-word-limit
              class="w-300"
            ></Input>
          </FormItem>
          <FormItem label="任务有效期" required>
            <RadioGroup
              v-model="formValidate.is_permanent"
              @on-change="permanentChange"
            >
              <Radio :label="1">永久有效</Radio>
              <Radio :label="0">自定义时间</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem v-if="formValidate.is_permanent == 0">
            <DatePicker
              :editable="false"
              @on-change="createTime"
              v-model="formValidate.start_time"
              format="yyyy/MM/dd"
              type="datetimerange"
              placement="bottom-start"
              placeholder="时间选择"
              clearable
              class="w-300"
            ></DatePicker>
          </FormItem>
          <!-- <FormItem label="任务类型:" required>
            <RadioGroup v-model="formValidate.task_type">
              <Radio :label="2">自定义</Radio>
              <Radio :label="1">用户生日</Radio>
            </RadioGroup>
          </FormItem> -->
          <FormItem label="生日范围:" v-if="formValidate.task_type == 1">
            <Select
              v-model="formValidate.birthday_type"
              placeholder="请选择生日范围"
              class="w-300"
            >
              <Option :value="1">生日当天</Option>
              <Option :value="2">生日当周</Option>
              <Option :value="3">生日当月</Option>
            </Select>
          </FormItem>
          <FormItem label="活动日期:" v-if="formValidate.task_type == 2">
            <RadioGroup v-model="formValidate.activity_date_type">
              <Radio :label="1">自定义</Radio>
              <Radio :label="2">每月</Radio>
              <Radio :label="3">每周</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem
            v-if="
              formValidate.activity_date_type == 1 &&
              formValidate.task_type == 2
            "
          >
            <DatePicker
              :editable="false"
              @on-change="customDateChange"
              v-model="formValidate.activity_date"
              format="yyyy/MM/dd"
              type="datetimerange"
              placement="bottom-start"
              placeholder="时间选择"
              :options="datePickerOptions"
              clearable
              class="w-300"
            ></DatePicker>
          </FormItem>
          <FormItem
            v-if="
              formValidate.activity_date_type == 2 &&
              formValidate.task_type == 2
            "
          >
            <Select
              v-model="formValidate.activity_month_days"
              multiple
              clearable
              class="w-400"
            >
              <Option v-for="item in 31" :key="item" :value="item"
                >{{ item }}号</Option
              >
            </Select>
          </FormItem>
          <FormItem
            v-if="
              formValidate.activity_date_type == 3 &&
              formValidate.task_type == 2
            "
          >
            <Select
              v-model="formValidate.activity_week_days"
              multiple
              clearable
              class="w-400"
            >
              <Option :value="1">周一</Option>
              <Option :value="2">周二</Option>
              <Option :value="3">周三</Option>
              <Option :value="4">周四</Option>
              <Option :value="5">周五</Option>
              <Option :value="6">周六</Option>
              <Option :value="7">周日</Option>
            </Select>
          </FormItem>
          <FormItem label="提前推送:">
            <div class="flex-y-center">
              <Select
                v-model="formValidate.advance_push"
                placeholder="请选择提前推送"
                class="w-120"
              >
                <Option :value="1">前N天</Option>
                <Option :value="0">当天</Option>
              </Select>
              <InputNumber
                v-model="formValidate.advance_days"
                :min="1"
                :max="30"
                :step="1"
                :precision="0"
                placeholder="请输入提前推送天数"
                class="w-120 ml-10"
                v-if="formValidate.advance_push == 1"
              ></InputNumber>
            </div>
          </FormItem>
          <FormItem label="推送时段:">
            <RadioGroup v-model="formValidate.push_time_type">
              <Radio :label="1">全时段</Radio>
              <Radio :label="2">指定时段</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem v-if="formValidate.push_time_type == 2">
            <div class="hui-box">
              <div class="flex-y-center">
                <TimePicker
                  v-model="formValidate.push_start_time"
                  type="time"
                  placeholder="选择时间"
                  style="width: 185px"
                />
                <span class="px-10">~</span>
                <TimePicker
                  v-model="formValidate.push_end_time"
                  type="time"
                  placeholder="选择时间"
                  style="width: 185px"
                />
              </div>
            </div>
          </FormItem>
          <FormItem label="推送人群:">
            <RadioGroup v-model="formValidate.push_user_type">
              <Radio :label="1">全部人群</Radio>
              <Radio :label="2">指定人群</Radio>
            </RadioGroup>
          </FormItem>
          <FormItem v-if="formValidate.push_user_type == 2">
            <div class="hui-box">
              <div class="flex-y-center rule-cell">
                <Select
                  v-model="formValidate.user_level"
                  clearable
                  multiple
                  placeholder="请选择会员等级"
                  class="w-300"
                >
                  <Option
                    :value="item.id"
                    v-for="(item, index) in levelList"
                    :key="index"
                    >{{ item.name }}</Option
                  >
                </Select>
                <span class="pl-10 text--w111-333">条件满足任一</span>
              </div>
              <div class="flex-y-center rule-cell">
                <Select
                  v-model="formValidate.user_tag"
                  multiple
                  clearable
                  placeholder="请选择用户群体"
                  class="w-300"
                >
                  <Option :value="2">付费会员</Option>
                  <Option :value="3">推广员</Option>
                  <Option :value="4">采购商</Option>
                </Select>
                <span class="pl-10 text--w111-333">条件全部满足</span>
              </div>
              <div class="flex-y-center rule-cell">
                <div>
                  <div class="acea-row row-middle">
                    <div
                      class="labelInput acea-row row-between-wrapper"
                      @click="openStoreLabel"
                    >
                      <div style="width: 90%">
                        <div v-if="userLabelActiveData.length">
                          <Tag
                            v-for="(item, index) in userLabelActiveData"
                            :key="index"
                            closable
                            @on-close="closeStoreLabel(index)"
                            >{{ item.label_name }}</Tag
                          >
                        </div>
                        <span class="span" v-else>请选择用户标签</span>
                      </div>
                      <div class="iconfont iconxiayi"></div>
                    </div>
                  </div>
                </div>
                <span class="pl-10 text--w111-333">条件全部满足</span>
              </div>
              <div class="rule-condition mt-10">
                <RadioGroup v-model="formValidate.condition_type">
                  <Radio :label="1">条件满足任一</Radio>
                  <Radio :label="2">全部条件满足</Radio>
                </RadioGroup>
              </div>
            </div>
          </FormItem>
          <FormItem label="赠送内容:">
            <div class="flex-y-center mb-20">
              <Checkbox v-model="gift_type[0]">
                <span class="pl-8 fs-12">赠优惠券</span>
              </Checkbox>
              <div>
                <div class="acea-row row-middle">
                  <div
                    class="labelInput flex-between-center"
                    @click="addCoupon"
                  >
                    <div style="width: 90%">
                      <div v-if="formValidate.couponName.length">
                        <Tag
                          v-for="(item, index) in formValidate.couponName"
                          :key="index"
                          closable
                          @on-close="closeCouponLabel(index)"
                          >{{ item.title }}</Tag
                        >
                      </div>
                      <span class="span" v-else>选择优惠券</span>
                    </div>
                    <div class="iconfont iconxiayi"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="flex-y-center mb-20">
              <Checkbox v-model="gift_type[1]">
                <span class="pl-8 pr-8 fs-12">赠送积分</span>
                <InputNumber
                  v-model="formValidate.integral"
                  :min="0"
                  :max="999"
                  :step="1"
                  :precision="0"
                  class="w-300"
                  placeholder="请填写"
                />
              </Checkbox>
            </div>
            <div class="flex-y-center mb-20">
              <Checkbox v-model="gift_type[2]">
                <span class="pl-8 pr-8 fs-12">多倍积分</span>
                <InputNumber
                  v-model="formValidate.integral_multiple"
                  :min="0"
                  :max="999"
                  :step="1"
                  :precision="0"
                  class="w-300"
                  placeholder="请填写"
                />
                <span class="fs-12 pl-10">倍</span>
              </Checkbox>
            </div>
            <div class="w-300 mb-20" style="margin-left: 90px">
              <Alert closable>活动期间下单多倍积分</Alert>
            </div>
            <div class="flex-y-center mb-20">
              <Checkbox v-model="gift_type[3]">
                <span class="pl-8 pr-8 fs-12">赠送余额</span>
                <Input
                  v-model="formValidate.balance"
                  type="number"
                  class="w-300"
                  placeholder="请填写"
                  @input="balanceChange"
                />
              </Checkbox>
            </div>
            <div class="flex-y-center">
              <Checkbox v-model="gift_type[4]">
                <span class="pl-8 pr-8 fs-12">商品全场包邮</span>
              </Checkbox>
            </div>
          </FormItem>
          <FormItem label="推送渠道：">
            <CheckboxGroup v-model="formValidate.push_channel">
              <Checkbox :label="1">
                <span class="fs-12">消息提醒</span>
              </Checkbox>
              <Checkbox :label="3">
                <span class="fs-12">弹框广告</span>
              </Checkbox>
            </CheckboxGroup>
            <div
              class="hui-box mt-10"
              v-if="formValidate.push_channel.includes(1)"
            >
              <div class="fs-12">
                赠送到账通知：您有一个新的活动福利已送达，请注意查收哦，登陆<br />{$site_name}即可查看。
              </div>
            </div>
          </FormItem>

          <div v-if="formValidate.push_channel.includes(3)">
            <FormItem>
              <div>
                <div class="list-box">
                  <draggable
                    class="dragArea list-group"
                    :list="formValidate.wechat_image"
                    group="peoples"
                    handle=".move-icon"
                  >
                    <div
                      class="item"
                      v-for="(item, index) in formValidate.wechat_image"
                      :key="index"
                    >
                      <div class="delect-btn" @click="bindDelete(item, index)">
                        <span class="iconfont-diy icondel_1"></span>
                      </div>
                      <div class="move-icon flex-center">
                        <span class="iconfont-diy icondrag"></span>
                      </div>
                      <div
                        class="img-box flex-center"
                        @click="modalPicTap(index)"
                      >
                        <img :src="item.pic" alt="" v-if="item.pic" />
                        <div class="upload-box" v-else>
                          <Icon
                            type="ios-camera-outline"
                            size="36"
                            show-word-limit
                            :maxLength="6"
                          />
                        </div>
                      </div>
                      <div class="info">
                        <span class="span">
                          <Input v-model="item.name" placeholder="请输入标题" />
                        </span>
                        <div class="input-box mt10">
                          <Input
                            icon="ios-link"
                            v-model="item.url"
                            placeholder="请选择链接"
                            @on-click="getLink(index)"
                            search
                          />
                        </div>
                      </div>
                    </div>
                  </draggable>
                  <div class="flex-y-center">
                    <div
                      class="jia-btn"
                      @click="addHotTxt"
                      v-if="formValidate.wechat_image.length < 10"
                    >
                      <span class="iconfont iconjiahao"></span>添加图片
                    </div>
                    <div class="text--w111-999 fs-12 pl-20">
                      推荐尺寸(590px*800px)
                    </div>
                  </div>
                </div>
              </div>
            </FormItem>
            <FormItem label="推送频次：">
              <RadioGroup v-model="formValidate.push_frequency">
                <Radio :label="1">永久一次</Radio>
                <Radio :label="2">每次进入</Radio>
                <Radio :label="3">每天</Radio>
                <!-- <Radio :label="4">每月</Radio>
                <Radio :label="5">每周</Radio> -->
              </RadioGroup>
            </FormItem>
            <FormItem label="应用页面：">
              <CheckboxGroup v-model="formValidate.show_page">
                <Checkbox
                  v-for="(check, i) in pageModalList"
                  :key="i"
                  :label="check.value"
                >
                  <span class="fs-12">{{ check.name }}</span>
                </Checkbox>
              </CheckboxGroup>
            </FormItem>
            <FormItem v-if="formValidate.show_page.includes(6)">
              <Select
                v-model="formValidate.topic_ids"
                multiple
                clearable
                class="w-400"
              >
                <Option
                  v-for="(item, index) in specialLink"
                  :key="index"
                  :value="item.id"
                  >{{ item.name }}</Option
                >
              </Select>
            </FormItem>
          </div>
          <FormItem label="状态：">
            <i-switch
              v-model="formValidate.status"
              :true-value="1"
              :false-value="0"
              size="large"
            >
              <span slot="open">开启</span>
              <span slot="close">关闭</span>
            </i-switch>
          </FormItem>
        </Form>
      </Card>
      <Card :bordered="false" dis-hover class="fixed-card">
        <div class="flex-center">
          <Button @click="cancel()">取消</Button>
          <Button type="primary" class="ml-14" @click="handleSubmit()"
            >确定</Button
          >
        </div>
      </Card>
    </div>
    <!-- 商品标签 -->
    <Modal
      v-model="storeLabelShow"
      scrollable
      title="选择用户标签"
      :closable="true"
      width="540"
      :footer-hide="true"
      :mask-closable="false"
    >
      <labelList
        ref="labelList"
        @activeData="activeStoreData"
        @close="labelListClose"
      ></labelList>
    </Modal>
    <coupon-list
      ref="couponTemplates"
      @nameId="nameId"
      :couponids="formValidate.coupon_ids"
      :updateIds="formValidate.coupon_ids"
      :updateName="formValidate.couponName"
    ></coupon-list>
    <linkaddress ref="linkaddres" @linkUrl="linkUrl"></linkaddress>
  </div>
</template>
<script>
import { mapState } from "vuex";
import timeOptions from "@/utils/timeOptions";
import { levelListApi } from "@/api/user";
import { pageLink } from "@/api/diy";
import {
  saveHolidayApi,
  holidayDetailApi,
  holidayUpdateApi,
} from "@/api/marketing";
import labelList from "@/components/labelList";
import couponList from "@/components/couponList";
import draggable from "vuedraggable";
import linkaddress from "@/components/linkaddress";
export default {
  name: "createAutomation",
  data() {
    return {
      options: timeOptions,
      // 其他数据...
      formValidate: {
        name: "", // 任务名称
        start_time: [], // 时间
        is_permanent: 1, // 是否永久有效，1为永久有效，2为自定义时间
        task_type: 2, // 任务类型，1为用户生日，2为自定义节日
        birthday_type: 1, // 生日范围，1为当天，2为当周，3为当月
        advance_push: 0, // 提前推送，1为前一天，0为当天
        advance_days: 1, // 提前天数
        activity_date_type: 1,
        activity_date: [], // 自定义节日日期
        activity_month_days: [], //每月活动日期
        activity_week_days: [], //每周活动星期几，多个用逗号分隔：1-7表示周一到周日
        push_time_type: 1, //推送时段类型：1-全时段，2-指定时段
        push_start_time: "", // 推送开始时间
        push_end_time: "", // 推送结束时间
        push_user_type: 1, // 推送人群，1为全部人群，2为指定人群
        user_level: [], // 用户等级
        user_label: [], // 用户标签
        user_tag: [], // 客户标签，多个用逗号分隔
        condition_type: 1, // 条件满足方式，1为任一条件满足，2为全部条件满足
        coupon_ids: [], // 优惠券ID
        couponName: [], // 优惠券名称
        gift_type: [], // 赠送内容类型，多个用逗号分隔：1-优惠券，2-积分，3-多倍积分，4-余额，5-全场包邮
        integral: 0,
        integral_multiple: 1, // 多倍积分
        balance: "",
        push_channel: [],
        wechat_image: [
          {
            pic: "",
            name: "",
            url: "",
          },
        ],
        push_frequency: 1, //推送频次：1-永久一次，2-每次进入，3-每天，4-每月，5-每周
        show_page: [1], //1-商城首页，2-分类页，3-购物车，4-个人中心，5-支付成功，6-专题页面
        topic_ids: [], //专题ID
        status: 1, // 状态，1为启用，0为禁用
      },
      ruleValidate: {
        name: [{ required: true, message: "请输入名称", trigger: "blur" }],
      },
      levelList: [], // 用户等级列表
      userLabelActiveData: [],
      storeLabelShow: false,
      couponActiveData: [], // 优惠券数据
      activeIndex: 0, // 当前激活的索引
      specialLink: [],
      gift_type: [false, false, false, false, false],
      pageModalList: [
        { name: "首页", value: 1 },
        { name: "分类页", value: 2 },
        { name: "购物车", value: 3 },
        { name: "个人中心", value: 4 },
        { name: "支付成功", value: 5 },
        { name: "专题页", value: 6 },
      ],
      pageTitle: "智能推送",
    };
  },
  components: {
    labelList,
    couponList,
    draggable,
    linkaddress,
  },
  computed: {
    ...mapState("admin/layout", ["isMobile"]),
    processedGiftType() {
      return this.gift_type.reduce((acc, isTrue, index) => {
        if (isTrue) {
          acc.push(index + 1); // 如果为true，将索引加一后的值加入数组
        }
        return acc;
      }, []);
    },
    datePickerOptions() {
      const [startDateStr, endDateStr] = this.formValidate.start_time;
      const startDate = new Date(startDateStr);
      const endDate = new Date(endDateStr);
      return {
        disabledDate(date) {
          // 限制只能选择 start_time 时间段内的日期
          return date < startDate || date > endDate;
        },
      };
    },
  },
  watch: {
    "formValidate.task_type": {
      handler(newVal, oldVal) {
        console.log(newVal);
        this.pageTitle = newVal == 1 ? "生日有礼" : "智能推送";
        window.document.title = this.$route.params.id
          ? "编辑" + this.pageTitle
          : "添加" + this.pageTitle;
      },
      immediate: true,
    },
  },
  mounted() {
    // 页面加载时的逻辑...
    this.getLevel();
    this.getSpecialLink();
    if (this.$route.params.id) {
      this.getInfo(this.$route.params.id);
    }
    if (this.$route.query.task_type) {
      this.formValidate.task_type = Number(this.$route.query.task_type);
    }
  },
  methods: {
    createTime(val) {
      this.formValidate.start_time = val;
      this.$refs.formValidate.validateField("start_time");
    },
    permanentChange(val) {
      if (val == 1) {
        this.formValidate.start_time = [];
      }
    },
    customDateChange(val) {
      this.formValidate.activity_date = val;
    },
    getLevel() {
      levelListApi().then((res) => {
        this.levelList = res.data.list;
      });
    },
    activeStoreData(storeDataLabel) {
      this.storeLabelShow = false;
      this.userLabelActiveData = storeDataLabel;
    },
    openStoreLabel(row) {
      this.storeLabelShow = true;
      let data = JSON.parse(JSON.stringify(this.userLabelActiveData));
      this.$refs.labelList.userLabel(data);
    },
    // 标签弹窗关闭
    labelListClose() {
      this.storeLabelShow = false;
    },
    // 添加优惠券
    addCoupon() {
      this.$refs.couponTemplates.isTemplate = true;
      this.$refs.couponTemplates.tableList();
    },
    closeCouponLabel(index) {
      this.formValidate.couponName.splice(index, 1);
      this.formValidate.coupon_ids.splice(index, 1);
    },
    nameId(id, names) {
      this.formValidate.coupon_ids = id;
      this.formValidate.couponName = this.unique(names);
    },
    //对象数组去重；
    unique(arr) {
      const res = new Map();
      return arr.filter((arr) => !res.has(arr.id) && res.set(arr.id, 1));
    },
    bindDelete(item, index) {
      this.formValidate.wechat_image.splice(index, 1);
    },
    closeStoreLabel(index) {
      this.userLabelActiveData.splice(index, 1);
    },
    modalPicTap(index) {
      this.activeIndex = index;
      this.$imgModal((e) => {
        let imgUrl = e[0].att_dir;
        if (imgUrl.includes("mp4")) {
          this.$Message.error("请选择正确的图片文件");
        } else {
          this.formValidate.wechat_image[this.activeIndex].pic = imgUrl;
        }
      });
    },
    getLink(index) {
      this.activeIndex = index;
      this.$refs.linkaddres.modals = true;
    },
    linkUrl(e) {
      this.formValidate.wechat_image[this.activeIndex].url = e;
    },
    balanceChange(val) {
      this.formValidate.balance = val.replace(/[^\d.]/g, "");
    },
    addHotTxt() {
      this.formValidate.wechat_image.push({
        pic: "",
        name: "",
        url: "",
      });
    },
    getSpecialLink() {
      pageLink(7).then((res) => {
        this.specialLink = res.data.list;
      });
    },
    handleSubmit() {
      this.$refs.formValidate.validate((valid) => {
        if (valid) {
          if (this.userLabelActiveData.length) {
            this.formValidate.user_label = this.userLabelActiveData.map(
              (item) => item.id
            );
          }
          this.formValidate.gift_type = this.processedGiftType;
          let funApi = this.$route.params.id
            ? holidayUpdateApi(this.$route.params.id, this.formValidate)
            : saveHolidayApi(this.formValidate);
          funApi
            .then((res) => {
              this.$Message.success(res.msg);
              this.$refs.formValidate.resetFields();
              this.navigateToList();
            })
            .catch((err) => {
              this.$Message.error(err.msg);
            });
        } else {
          return false;
        }
      });
    },
    getInfo(id) {
      holidayDetailApi(id)
        .then((res) => {
          Object.assign(this.formValidate, res.data);
          this.pageTitle = res.data.task_type == 1 ? "生日有礼" : "智能推送";
          this.userLabelActiveData = res.data.label || [];
          this.formValidate.couponName = res.data.coupon || [];
          this.initializeGiftType(res.data.gift_type);
        })
        .catch((err) => {
          this.$message.error(err.msg);
        });
    },
    cancel() {
      this.navigateToList();
    },
    navigateToList() {
      const targetPath =
        this.formValidate.task_type === 1
          ? "/admin/marketing/birthday"
          : "/admin/marketing/automation";
      this.$router.push({ path: targetPath });
    },
    initializeGiftType(giftTypeData) {
      const giftTypeArray = Array(5).fill(false); // 创建一个长度为5的数组并填充false
      giftTypeData.forEach((index) => {
        if (index >= 1 && index <= 5) {
          giftTypeArray[index - 1] = true; // 将对应索引位置设置为true
        }
      });
      this.gift_type = giftTypeArray; // 更新formValidate.gift_type
    },
  },
};
</script>
<style lang="less">
.w-300 {
  width: 300px !important;
}
.w-400 {
  width: 400px;
}
.w-460 {
  width: 460px;
}
.hui-box {
  width: 465px;
  background-color: #f9f9f9;
  padding: 20px;
}
.jia-btn {
  font-size: 13px;
  color: #2d8cf0;
  cursor: pointer;
  .iconjiahao {
    font-size: 14px;
  }
  .pl-2 {
    padding-left: 2px;
  }
}
.rule-cell ~ .rule-cell {
  margin-top: 15px;
}
.rule-condition {
  // margin: 10px 0 0 35px;
}
.px-6 {
  padding: 0 6px 0;
}
.fixed-card {
  position: fixed;
  right: 0;
  bottom: 0;
  left: 250px;
  z-index: 20;
  box-shadow: 0 -1px 2px rgb(240, 240, 240);

  /deep/ .ivu-card-body {
    padding: 15px 16px 14px;
  }

  .ivu-form-item {
    margin-bottom: 12px !important;
  }

  /deep/ .ivu-form-item-content {
    margin-right: 124px;
    text-align: center;
  }
}
.labelInput {
  border: 1px solid #dcdee2;
  width: 300px;
  background-color: #ffffff;
  padding: 0 10px 0 5px;
  border-radius: 5px;
  min-height: 30px;
  cursor: pointer;
  .span {
    color: #c5c8ce;
  }
  .iconxiayi {
    font-size: 10px;
  }
}
.labelClass {
  /deep/.ivu-form-item-content {
    line-height: unset;
  }
}
.list-box {
  width: 465px;

  .item {
    position: relative;
    display: flex;
    background: #f9f9f9;
    align-items: center;
    padding: 16px 20px 16px 0;
    margin-bottom: 16px;
    border-radius: 3px;

    .delect-btn {
      position: absolute;
      right: -13px;
      top: -16px;
      z-index: 10;

      .iconfont-diy {
        font-size: 25px;
        color: #ccc;
      }
    }

    .move-icon {
      width: 30px;
      cursor: move;
    }

    .img-box {
      position: relative;
      width: 64px;
      height: 64px;
      border: 1px solid #e5e5e5;
      border-radius: 8px;

      img {
        width: 100%;
        height: 100%;
        border-radius: 3px;
      }
    }

    .info {
      flex: 1;
      margin-left: 22px;

      .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 10px;

        &:nth-last-child(1) {
          margin-bottom: 0;
        }

        .span {
          width: 40px;
          font-size: 12px;
          color: #999;
        }

        .input-box {
          flex: 1;
        }
      }
    }
  }
}
</style>
