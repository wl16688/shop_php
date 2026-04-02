<?php

declare (strict_types=1);

namespace app\services\activity\holiday;

use app\dao\activity\holiday\HolidayGiftDao;
use app\model\activity\holiday\HolidayGift;
use app\services\BaseServices;
use app\services\product\product\StoreProductCouponServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\label\UserLabelServices;
use app\services\user\level\UserLevelServices;
use app\services\user\UserServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\activity\coupon\StoreCouponUserServices;
use app\services\user\UserBillServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use think\exception\ValidateException;
use think\facade\Db;

/**
 * 节日有礼服务
 * Class HolidayGiftServices
 * @package app\services\activity
 * @mixin HolidayGiftDao
 */
class HolidayGiftServices extends BaseServices
{
    /**
     * 构造方法
     * HolidayGiftServices constructor.
     * @param HolidayGiftDao $dao
     */
    public function __construct(HolidayGiftDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取节日有礼列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getHolidayGiftList($where, '*', $page, $limit);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item['start_time'] = date('Y-m-d H:i:s', $item['start_time']);
            $item['end_time'] = date('Y-m-d H:i:s', $item['end_time']);
            // 使用辅助方法转换数值为文本描述
            $this->formatHolidayGiftData($item);
        }
        return compact('list', 'count');
    }

    /**
     * 获取节日有礼详情
     * @param int $id
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftInfo(int $id, array $field = ['*'])
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        $info = $this->dao->get($id, $field);
        if (!$info) {
            throw new AdminException('节日有礼不存在');
        }
        $info = $info->toArray();
        if ($info['is_permanent'] == 0) {
            $start_time = $info['start_time'] ? date('Y-m-d H:i:s', (int)$info['start_time']) : '';
            $end_time = $info['start_time'] ? date('Y-m-d H:i:s', (int)$info['end_time']) : '';
            $info['start_time'] = [$start_time, $end_time];
        }
        $info['activity_date'] = [];
        if ($info['activity_date_type'] == 1) {
            $activity_start_date = $info['activity_start_date'] ? date('Y-m-d H:i:s', (int)$info['activity_start_date']) : '';
            $activity_end_date = $info['activity_end_date'] ? date('Y-m-d H:i:s', (int)$info['activity_end_date']) : '';
            if ($activity_start_date && $activity_end_date) {
                $info['activity_date'] = [
                    $activity_start_date,
                    $activity_end_date
                ];
            }
        }

        $coupon_ids = $info['coupon_ids'];
        $user_label = $info['user_label'];
        if ($coupon_ids) {
            $info['coupon'] = app()->make(StoreCouponIssueServices::class)->search([])->whereIn('id', $coupon_ids)->field('id,coupon_title as title')->select()->toArray();
        }
        if ($user_label) {
            $info['label'] = app()->make(UserLabelServices::class)->search([])->whereIn('id', $user_label)->field('id,label_name')->select()->toArray();
        }
        return $info;
    }

    /**
     * 格式化节日有礼数据，添加文本描述
     * @param array &$data 节日有礼数据
     * @return void
     */
    protected function formatHolidayGiftData(array &$data)
    {
        // 基本信息
        if (isset($data['task_type'])) {
            $data['task_type_text'] = HolidayGift::TASK_TYPE[$data['task_type']] ?? '';
        }
        if (isset($data['birthday_type'])) {
            $data['birthday_type_text'] = HolidayGift::BIRTHDAY_TYPE[$data['birthday_type']] ?? '';
        }
        if (isset($data['status'])) {
            $data['status_text'] = HolidayGift::STATUS[$data['status']] ?? '';
        }
        if (isset($data['is_permanent'])) {
            $data['is_permanent_text'] = HolidayGift::IS_PERMANENT[$data['is_permanent']] ?? '';
        }

        // 推送相关
        if (isset($data['push_time_type'])) {
            $data['push_time_type_text'] = HolidayGift::PUSH_TIME_TYPE[$data['push_time_type']] ?? '';
        }
        if (isset($data['push_user_type'])) {
            $data['push_user_type_text'] = HolidayGift::PUSH_USER_TYPE[$data['push_user_type']] ?? '';
        }
        if (isset($data['push_frequency'])) {
            $data['push_frequency_text'] = HolidayGift::PUSH_FREQUENCY[$data['push_frequency']] ?? '';
        }
        if (isset($data['advance_push'])) {
            $data['advance_push_text'] = HolidayGift::ADVANCE_PUSH[$data['advance_push']] ?? '';
        }
        if (isset($data['push_status'])) {
            $data['push_status_text'] = HolidayGift::PUSH_STATUS[$data['push_status']] ?? '';
        }

        // 条件和礼品相关
        if (isset($data['condition_type'])) {
            $data['condition_type_text'] = HolidayGift::CONDITION_TYPE[$data['condition_type']] ?? '';
        }
        if (isset($data['gift_type'])) {
            if (is_array($data['gift_type'])) {
                $data['gift_type_text'] = [];
                foreach ($data['gift_type'] as $type) {
                    $data['gift_type_text'][] = HolidayGift::GIFT_TYPE[$type] ?? '';
                }
            } else {
                $data['gift_type_text'] = HolidayGift::GIFT_TYPE[$data['gift_type']] ?? '';
            }
        }

        // 渠道和页面相关
        if (isset($data['push_channel'])) {
            if (is_array($data['push_channel'])) {
                $data['push_channel_text'] = [];
                foreach ($data['push_channel'] as $channel) {
                    $data['push_channel_text'][] = HolidayGift::PUSH_CHANNEL[$channel] ?? '';
                }
            } else {
                $data['push_channel_text'] = HolidayGift::PUSH_CHANNEL[$data['push_channel']] ?? '';
            }
        }
        if (isset($data['show_page'])) {
            if (is_array($data['show_page'])) {
                $data['show_page_text'] = [];
                foreach ($data['show_page'] as $page) {
                    $data['show_page_text'][] = HolidayGift::SHOW_PAGE[$page] ?? '';
                }
            } else {
                $data['show_page_text'] = HolidayGift::SHOW_PAGE[$data['show_page']] ?? '';
            }
        }
    }

    /**
     * 保存节日有礼数据
     * @param array $data
     * @return mixed
     */
    public function saveHolidayGift(array $data)
    {
        // 数据验证
        $this->checkHolidayGiftData($data);

        $data['add_time'] = time();
        $data['update_time'] = time();
        return $this->dao->save($data);
    }

    /**
     * 更新节日有礼数据
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateHolidayGift(int $id, array $data)
    {
        // 数据验证
        $this->checkHolidayGiftData($data);

        $data['update_time'] = time();

        return $this->dao->update($id, $data);
    }

    /**
     * 删除节日有礼
     * @param int $id
     * @return mixed
     */
    public function deleteHolidayGift(int $id)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        return $this->dao->update($id, ['is_del' => 1]);
    }

    /**
     * 修改节日有礼状态
     * @param int $id
     * @param int $status
     * @return mixed
     */
    public function setStatus(int $id, int $status)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        return $this->dao->update($id, ['status' => $status]);
    }

    /**
     * 验证节日有礼数据
     * @param array $data
     * @return bool
     */
    public function checkHolidayGiftData(array $data)
    {
        // 验证活动名称
        if (!isset($data['name']) || !$data['name']) {
            throw new AdminException('请输入活动名称');
        }

        // 验证活动时间（如果不是永久活动）
        if (!isset($data['is_permanent']) || $data['is_permanent'] != 1) {
            if (!isset($data['start_time']) || !$data['start_time']) {
                throw new AdminException('请选择开始时间');
            }
            if (!isset($data['end_time']) || !$data['end_time']) {
                throw new AdminException('请选择结束时间');
            }
            if ($data['start_time'] >= $data['end_time']) {
                throw new AdminException('结束时间必须大于开始时间');
            }
        }

        // 验证任务类型
        if (!isset($data['task_type']) || !in_array($data['task_type'], array_keys(HolidayGift::TASK_TYPE))) {
            throw new AdminException('请选择正确的任务类型');
        }
        // 根据任务类型验证相关数据
        if ($data['task_type'] == array_search('用户生日', HolidayGift::TASK_TYPE)) { // 用户生日
            if (!isset($data['birthday_type']) || !in_array((int)$data['birthday_type'], array_keys(HolidayGift::BIRTHDAY_TYPE))) {
                throw new AdminException('请选择正确的生日类型');
            }
        } else { // 活动日期
            // 验证活动日期类型
            if (!isset($data['activity_date_type']) || !in_array((int)$data['activity_date_type'], array_keys(HolidayGift::ACTIVITY_DATE_TYPE))) {
                throw new AdminException('请选择正确的活动日期类型');
            }

            // 根据活动日期类型验证相关数据
            switch ($data['activity_date_type']) {
                case 1: // 自定义日期
                    if (!isset($data['activity_start_date']) || !$data['activity_start_date']) {
                        throw new AdminException('请选择活动开始日期');
                    }
                    if (!isset($data['activity_end_date']) || !$data['activity_end_date']) {
                        throw new AdminException('请选择活动结束日期');
                    }
                    break;

                case 2: // 每月
                    if (!isset($data['activity_month_days']) || (is_array($data['activity_month_days']) && empty($data['activity_month_days'])) || !$data['activity_month_days']) {
                        throw new AdminException('请选择每月活动日期');
                    }
                    // 验证月份日期范围 1-31
                    $monthDays = is_array($data['activity_month_days']) ? $data['activity_month_days'] : explode(',', $data['activity_month_days']);
                    foreach ($monthDays as $day) {
                        if (!is_numeric($day) || $day < 1 || $day > 31) {
                            throw new AdminException('每月活动日期必须在1-31之间');
                        }
                    }
                    break;
                case 3: // 每周
                    if (!isset($data['activity_week_days']) || (is_array($data['activity_week_days']) && empty($data['activity_week_days'])) || !$data['activity_week_days']) {
                        throw new AdminException('请选择每周活动日期');
                    }
                    // 验证周几范围 1-7
                    $weekDays = is_array($data['activity_week_days']) ? $data['activity_week_days'] : explode(',', $data['activity_week_days']);
                    foreach ($weekDays as $day) {
                        if (!is_numeric($day) || $day < 1 || $day > 7) {
                            throw new AdminException('每周活动日期必须在1-7之间（1表示周一，7表示周日）');
                        }
                    }
                    break;
            }
        }
        // 验证提前推送
        if (isset($data['advance_push']) && $data['advance_push']) {
            if (!isset($data['advance_days']) || $data['advance_days'] <= 0) {
                throw new AdminException('请输入正确的提前推送天数');
            }
        }

        // 验证推送时段
        if (isset($data['push_time_type']) && $data['push_time_type'] == array_search('指定时段', HolidayGift::PUSH_TIME_TYPE)) {
            if (!isset($data['push_start_time']) || !$data['push_start_time']) {
                throw new AdminException('请选择推送开始时间');
            }
            if (!isset($data['push_end_time']) || !$data['push_end_time']) {
                throw new AdminException('请选择推送结束时间');
            }
            // 验证时间格式 HH:mm:ss 或 HH:mm
            if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $data['push_start_time']) ||
                !preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])(:[0-5][0-9])?$/', $data['push_end_time'])) {
                throw new AdminException('推送时间格式不正确，应为HH:mm:ss或HH:mm格式');
            }
        }

        // 验证推送人群
        if (isset($data['push_user_type']) && $data['push_user_type'] == array_search('指定人群', HolidayGift::PUSH_USER_TYPE)) {
            if ((!isset($data['user_level']) || !$data['user_level']) &&
                (!isset($data['user_label']) || (is_array($data['user_label']) && empty($data['user_label'])) || !$data['user_label']) &&
                (!isset($data['user_tag']) || (is_array($data['user_tag']) && empty($data['user_tag'])) || !$data['user_tag'])) {
                throw new AdminException('请至少选择一种用户筛选条件');
            }
        }
        // 验证赠送内容
//        if (!isset($data['gift_type']) || !$data['gift_type']) {
//            throw new AdminException('请选择赠送内容');
//        }
        $giftTypes = is_array($data['gift_type']) ? $data['gift_type'] : explode(',', $data['gift_type']);
        foreach ($giftTypes as $type) {
            if ($type == array_search('优惠券', HolidayGift::GIFT_TYPE) && (!isset($data['coupon_ids']) || (is_array($data['coupon_ids']) && empty($data['coupon_ids'])) || !$data['coupon_ids'])) {
                throw new AdminException('请选择赠送的优惠券');
            }
            if ($type == array_search('积分', HolidayGift::GIFT_TYPE) && (!isset($data['integral']) || $data['integral'] <= 0)) {
                throw new AdminException('请输入正确的赠送积分数量');
            }
            if ($type == array_search('多倍积分', HolidayGift::GIFT_TYPE) && (!isset($data['integral_multiple']) || $data['integral_multiple'] <= 1)) {
                throw new AdminException('请输入正确的积分倍数，必须大于1');
            }
            if ($type == array_search('余额', HolidayGift::GIFT_TYPE) && (!isset($data['balance']) || $data['balance'] <= 0)) {
                throw new AdminException('请输入正确的赠送余额');
            }
        }

        // 验证推送渠道
        if (!isset($data['push_channel']) || (is_array($data['push_channel']) && empty($data['push_channel'])) || !$data['push_channel']) {
            throw new AdminException('请选择推送渠道');
        }
        $pushChannels = is_array($data['push_channel']) ? $data['push_channel'] : explode(',', $data['push_channel']);
        foreach ($pushChannels as $channel) {
            if ($channel == array_search('公众号', HolidayGift::PUSH_CHANNEL) && (!isset($data['wechat_image']) || (is_array($data['wechat_image']) && empty($data['wechat_image'])) || !$data['wechat_image'])) {
                throw new AdminException('请上传公众号推送图片');
            }
        }

        // 验证推送频次
        if (!isset($data['push_frequency']) || !in_array((int)$data['push_frequency'], array_keys(HolidayGift::PUSH_FREQUENCY))) {
            throw new AdminException('请选择正确的推送频次');
        }
        if ($data['push_frequency'] == array_search('每周', HolidayGift::PUSH_FREQUENCY) && (!isset($data['push_week_days']) || (is_array($data['push_week_days']) && empty($data['push_week_days'])) || !$data['push_week_days'])) {
            throw new AdminException('请选择每周推送的星期几');
        }

        // 验证应用界面
        if (!isset($data['show_page']) || (is_array($data['show_page']) && empty($data['show_page'])) || !$data['show_page']) {
            throw new AdminException('请选择应用界面');
        }
        $showPages = is_array($data['show_page']) ? $data['show_page'] : explode(',', $data['show_page']);
        foreach ($showPages as $page) {
            if ($page == array_search('专题页面', HolidayGift::SHOW_PAGE) && (!isset($data['topic_ids']) || (is_array($data['topic_ids']) && empty($data['topic_ids'])) || !$data['topic_ids'])) {
                throw new AdminException('请选择专题页面');
            }
        }
        return true;
    }

    /**
     * 获取活动中的节日有礼
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getActiveHolidayGift(array $where = [])
    {
        return $this->dao->getActiveHolidayGift($where);
    }

    /**
     * 获取用户生日相关的节日有礼活动
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBirthdayHolidayGift(array $where = [])
    {
        return $this->dao->getBirthdayHolidayGift($where);
    }

    /**
     * 获取活动日期相关的节日有礼活动
     * @param string $date 日期，格式：MM-DD
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCustomDateHolidayGift(string $date, array $where = [])
    {
        return $this->dao->getCustomDateHolidayGift($date, $where);
    }

    /**
     * 获取每月活动日期相关的节日有礼活动
     * @param int $day 日期，1-31
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMonthlyHolidayGift(int $day, array $where = [])
    {
        return $this->dao->getMonthlyHolidayGift($day, $where);
    }

    /**
     * 获取每周活动日期相关的节日有礼活动
     * @param int $weekDay 周几，1-7表示周一到周日
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWeeklyHolidayGift(int $weekDay, array $where = [])
    {
        return $this->dao->getWeeklyHolidayGift($weekDay, $where);
    }

    /**
     * 获取当前日期相关的所有节日有礼活动
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getTodayHolidayGift(array $where = [])
    {
        $activities = [];
        $today = date('m-d'); // MM-DD格式
        $todayDay = (int)date('d'); // 当前日期
        $todayWeek = (int)date('N'); // 当前周几，1-7表示周一到周日

        // 获取自定义日期活动
        $customDateActivities = $this->getCustomDateHolidayGift($today, $where);
        $activities = array_merge($activities, $customDateActivities);

        // 获取每月活动
        $monthlyActivities = $this->getMonthlyHolidayGift($todayDay, $where);
        $activities = array_merge($activities, $monthlyActivities);

        // 获取每周活动
        $weeklyActivities = $this->getWeeklyHolidayGift($todayWeek, $where);
        $activities = array_merge($activities, $weeklyActivities);

        // 去重（根据ID）
        $uniqueActivities = [];
        $ids = [];
        foreach ($activities as $activity) {
            if (!in_array($activity['id'], $ids)) {
                $uniqueActivities[] = $activity;
                $ids[] = $activity['id'];
            }
        }

        return $uniqueActivities;
    }


    /**
     * 检查用户是否符合节日有礼活动条件
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkUserCondition(int $uid, array $giftInfo)
    {

        // 如果是全部人群，直接返回true
        if ($giftInfo['push_user_type'] == 1) {
            return true;
        }

        // 指定人群，需要检查条件
        $isLevelMatch = $isLabelMatch = $isTagMatch = true;
        // 检查用户等级
        if (!empty($giftInfo['user_level'])) {
            $isLevelMatch = false;
            $userLevelService = app()->make(UserLevelServices::class);
            $userLevel = $userLevelService->getUserLevel($uid);
            if ($userLevel) {
                $levelIds = is_string($giftInfo['user_level']) ? explode(',', $giftInfo['user_level']) : $giftInfo['user_level'];
                $isLevelMatch = in_array($userLevel['level_id'], $levelIds);
            }
        }
        // 检查用户标签
        if (!empty($giftInfo['user_label'])) {
            $isLabelMatch = false;
            $userLabelRelationService = app()->make(UserLabelRelationServices::class);
            $userLabels = $userLabelRelationService->getUserLabels($uid);
            if ($userLabels) {
                $labelIds = is_string($giftInfo['user_label']) ? explode(',', $giftInfo['user_label']) : $giftInfo['user_label'];
                $isLabelMatch = (bool)empty(array_diff($labelIds, $userLabels));
            }
        }

        // 检查客户身份
        if (!empty($giftInfo['user_tag'])) {
            $isTagMatch = false;
            $userService = app()->make(UserServices::class);
            $userTag = $userService->checkUserTag($uid);
            if ($userTag) {

                $tagIds = is_string($giftInfo['user_tag']) ? explode(',', $giftInfo['user_tag']) : $giftInfo['user_tag'];
                $isTagMatch = (bool)empty(array_diff($tagIds, $userTag));
            }
        }
        // 根据条件满足类型返回结果
        if ($giftInfo['condition_type'] == 1) { // 满足任一条件
            return $isLevelMatch || $isLabelMatch || $isTagMatch;
        } else { // 满足全部条件
            return $isLevelMatch && $isLabelMatch && $isTagMatch;
        }
    }

    /**
     * 检查弹框广告是否符合节日有礼活动条件
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkPopupAdCondition(int $uid, array $giftInfo)
    {
        // 检查推送渠道是否包含弹框广告
        $pushChannels = is_array($giftInfo['push_channel']) ? $giftInfo['push_channel'] : explode(',', $giftInfo['push_channel']);
        $popupAdChannel = 3;
        if (!in_array($popupAdChannel, $pushChannels)) {
            return false;
        }
        // 根据推送频次检查是否可以推送
        /** @var HolidayGiftPushServices $pushService */
        $pushService = app()->make(HolidayGiftPushServices::class);
        switch ($giftInfo['push_frequency']) {
            case 1: // 永久一次
                // 检查是否已经推送过
                $pushRecord = $pushService->getUserPushRecord($uid, $giftInfo['id']);
                return empty($pushRecord);

            case 2: // 每次进入
                return true; // 每次都可以推送

            case 3: // 每天
                // 检查今天是否已经推送过
                $todayRecord = $pushService->getUserTodayPushRecord($uid, $giftInfo['id']);
                return empty($todayRecord);

            case 4: // 每月
                // 检查本月是否已经推送过
                $monthRecord = $pushService->getUserMonthPushRecord($uid, $giftInfo['id']);
                return empty($monthRecord);

            case 5: // 每周
                // 检查今天是否是设置的星期几
                $weekDay = date('N'); // 1-7 表示周一到周日
                $weekDays = explode(',', $giftInfo['push_week_days']);
                if (!in_array($weekDay, $weekDays)) {
                    return false; // 今天不是设置的星期几，不能推送
                }

                // 检查本周是否已经推送过
                $weekRecord = $pushService->getUserWeekPushRecord($uid, $giftInfo['id']);
                return empty($weekRecord);

            default:
                return false;
        }
    }

    /**
     * 检查用户是否在推送时段内
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkPushTimeRange(array $giftInfo, int $uid = 0)
    {
        // 如果是全时段，直接返回true
        if ($giftInfo['push_time_type'] == 1) {
            return true;
        }
        // 指定时段，需要检查当前时间是否在时段内
        $now = date('H:i');
        $startTime = $giftInfo['push_start_time'];
        $endTime = $giftInfo['push_end_time'];

        return $now >= $startTime && $now <= $endTime;
    }


    /**
     * 获取当前有效的节日有礼活动
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getActiveHolidayGifts()
    {
        $currentTime = time();
        $where = [
            'status' => 1,
            'is_del' => 0,
            'is_valid' => 1,
        ];
        $list = $this->dao->search($where)
            ->select()
            ->toArray();
        return $list;
    }

    /**
     * 推送节日有礼消息
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function pushHolidayGiftMessage(int $uid, array $giftInfo)
    {
        if (!$uid || !$giftInfo) {
            return false;
        }

        // 获取用户信息
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $userInfo = $userService->getUserInfo($uid);
        if (!$userInfo) {
            return false;
        }

        // 准备推送记录数据
        $pushData = [
            'gift_id' => $giftInfo['id'],
            'uid' => $uid,
            'push_time' => time(),
            'status' => 0,
            'add_time' => time()
        ];

        // 处理不同推送渠道
        $pushChannels = explode(',', $giftInfo['push_channel']);
        $pushSuccess = false;

        /** @var HolidayGiftPushServices $pushService */
        $pushService = app()->make(HolidayGiftPushServices::class);
        /** @var NoticeServices $noticeService */
        $noticeService = app()->make(NoticeServices::class);

        foreach ($pushChannels as $channel) {
            switch ($channel) {
                case 1: // 短信
                    if (!empty($userInfo['phone'])) {
                        $pushData['push_type'] = 1;
                        $pushData['push_content'] = '尊敬的用户，您有一份节日礼物待领取，请登录APP查看。';

                        // 发送短信
                        $noticeService->smsNotice($userInfo['phone'], ['gift_name' => $giftInfo['name']], 'HOLIDAY_GIFT');

                        $pushData['status'] = 1;
                        $pushService->save($pushData);
                        $pushSuccess = true;
                    }
                    break;

                case 2: // 公众号
                    if (!empty($userInfo['openid'])) {
                        $pushData['push_type'] = 2;
                        $pushData['push_content'] = '尊敬的用户，您有一份节日礼物待领取，请登录APP查看。';

                        // 发送公众号消息
                        $noticeService->wechatNotice($userInfo['openid'], 'HOLIDAY_GIFT', [
                            'first' => '节日有礼提醒',
                            'keyword1' => $giftInfo['name'],
                            'keyword2' => date('Y-m-d H:i:s'),
                            'remark' => '点击领取您的节日礼物'
                        ], '');

                        $pushData['status'] = 1;
                        $pushService->save($pushData);
                        $pushSuccess = true;
                    }
                    break;

                case 3: // 弹框广告
                    $pushData['push_type'] = 3;
                    $pushData['push_content'] = '弹框广告推送';
                    $pushData['status'] = 1;
                    $pushService->save($pushData);
                    $pushSuccess = true;
                    break;
            }
        }

        return $pushSuccess;
    }
}
