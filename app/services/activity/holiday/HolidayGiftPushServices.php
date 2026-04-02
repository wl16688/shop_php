<?php

declare (strict_types=1);

namespace app\services\activity\holiday;

use app\dao\activity\holiday\HolidayGiftPushDao;
use app\jobs\activity\HolidayGiftJob;
use app\services\activity\coupon\StoreCouponIssueServices;
use app\services\BaseServices;
use app\services\user\UserServices;
use app\services\user\UserBillServices;
use app\services\user\UserMoneyServices;
use app\services\activity\coupon\CouponIssueServices;
use app\services\message\NoticeService;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use think\exception\ValidateException;
use think\facade\Log;

/**
 * 节日有礼推送记录服务
 * Class HolidayGiftPushServices
 * @package app\services\activity
 * @mixin HolidayGiftPushDao
 */
class HolidayGiftPushServices extends BaseServices
{
    /**
     * 构造方法
     * HolidayGiftPushServices constructor.
     * @param HolidayGiftPushDao $dao
     */
    public function __construct(HolidayGiftPushDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取节日有礼推送记录列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftPushList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getHolidayGiftPushList($where, '*', $page, $limit);
        $count = $this->dao->count($where);

        if ($list) {
            // 获取用户信息
            $uids = array_column($list, 'uid');
            /** @var UserServices $userService */
            $userService = app()->make(UserServices::class);
            $userInfos = $userService->getUserList(['uid' => $uids], 'uid,nickname,avatar,phone');
            $userInfos = array_combine(array_column($userInfos, 'uid'), $userInfos);

            // 获取节日有礼活动信息
            $giftIds = array_column($list, 'gift_id');
            /** @var HolidayGiftServices $giftService */
            $giftService = app()->make(HolidayGiftServices::class);
            $giftInfos = $giftService->getHolidayGiftList(['id' => $giftIds])['list'];
            $giftInfos = array_combine(array_column($giftInfos, 'id'), $giftInfos);

            foreach ($list as &$item) {
                $item['user_info'] = $userInfos[$item['uid']] ?? [];
                $item['gift_info'] = $giftInfos[$item['gift_id']] ?? [];
            }
        }

        return compact('list', 'count');
    }

    /**
     * 获取节日有礼推送记录详情
     * @param int $id
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftPushInfo(int $id, string $field = '*')
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        $info = $this->dao->get($id, $field);
        if (!$info) {
            throw new AdminException('推送记录不存在');
        }
        return $info;
    }

    /**
     * 获取用户的节日有礼推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserPushRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $where = ['uid' => $uid];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getHolidayGiftPushList($where);
    }

    /**
     * 获取用户今日的节日有礼推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserTodayPushRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $where = [
            ['uid', '=', $uid],
            ['push_time', '>', strtotime(date('Y-m-d'))]
        ];
        if ($giftId) {
            $where[] = ['gift_id', '=', $giftId];
        }
        return $this->dao->getHolidayGiftPushList($where);
    }

    /**
     * 获取用户本周的节日有礼推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserWeekPushRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $weekStart = strtotime(date('Y-m-d', strtotime('this week Monday')));
        $where = [
            'uid' => $uid,
            'push_time' => ['>=', $weekStart]
        ];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getHolidayGiftPushList($where);
    }

    /**
     * 获取用户本月的节日有礼推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserMonthPushRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $monthStart = strtotime(date('Y-m-01'));
        $where = [
            'uid' => $uid,
            'push_time' => ['>=', $monthStart]
        ];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getHolidayGiftPushList($where);
    }

    /**
     * 更新节日有礼推送记录状态
     * @param int $id 记录ID
     * @param int $status 状态：0未读，1已读
     * @return mixed
     */
    public function updatePushStatus(int $id, int $status)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        return $this->dao->update($id, ['status' => $status]);
    }

    /**
     * 删除节日有礼推送记录
     * @param int $id 记录ID
     * @return mixed
     */
    public function deletePush(int $id)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        return $this->dao->update($id, []);
    }

    /**
     * 批量删除节日有礼推送记录
     * @param array $ids 记录ID数组
     * @return mixed
     */
    public function batchDeletePush(array $ids)
    {
        if (!$ids) {
            throw new AdminException('参数错误');
        }
        return $this->dao->batchUpdate($ids, []);
    }

    /**
     * 统计节日有礼推送数据
     * @param array $where
     * @return array
     */
    public function getPushStatistics(array $where = [])
    {
        // 总推送人数
        $totalUsers = $this->dao->distinct(true)->field('uid')->where($where)->count();

        // 总推送次数
        $totalPushes = $this->dao->count($where);

        // 今日推送人数
        $todayWhere = $where;
        $todayWhere['push_time'] = ['>=', strtotime(date('Y-m-d'))];
        $todayUsers = $this->dao->distinct(true)->field('uid')->where($todayWhere)->count();

        // 今日推送次数
        $todayPushes = $this->dao->count($todayWhere);

        // 昨日推送人数
        $yesterdayStart = strtotime(date('Y-m-d', strtotime('-1 day')));
        $yesterdayEnd = strtotime(date('Y-m-d')) - 1;
        $yesterdayWhere = $where;
        $yesterdayWhere['push_time'] = ['between', [$yesterdayStart, $yesterdayEnd]];
        $yesterdayUsers = $this->dao->distinct(true)->field('uid')->where($yesterdayWhere)->count();

        // 昨日推送次数
        $yesterdayPushes = $this->dao->count($yesterdayWhere);

        // 本月推送人数
        $monthWhere = $where;
        $monthWhere['push_time'] = ['>=', strtotime(date('Y-m-01'))];
        $monthUsers = $this->dao->distinct(true)->field('uid')->where($monthWhere)->count();

        // 本月推送次数
        $monthPushes = $this->dao->count($monthWhere);

        // 按推送渠道统计
        $channelStats = [];
        $pushTypes = [1 => '短信', 2 => '公众号', 3 => '弹框广告'];
        foreach ($pushTypes as $type => $name) {
            $typeWhere = $where;
            $typeWhere['push_type'] = $type;
            $channelStats[$type] = [
                'name' => $name,
                'count' => $this->dao->count($typeWhere),
                'users' => $this->dao->distinct(true)->field('uid')->where($typeWhere)->count()
            ];
        }

        return [
            'total_users' => $totalUsers,
            'total_pushes' => $totalPushes,
            'today_users' => $todayUsers,
            'today_pushes' => $todayPushes,
            'yesterday_users' => $yesterdayUsers,
            'yesterday_pushes' => $yesterdayPushes,
            'month_users' => $monthUsers,
            'month_pushes' => $monthPushes,
            'channel_stats' => $channelStats
        ];
    }

    /**
     * 检查用户是否可以推送节日有礼消息
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function checkUserCanPush(int $uid, array $giftInfo)
    {
        if (!$uid || !$giftInfo) {
            return false;
        }

        // 获取用户推送记录
        $pushRecords = $this->getUserPushRecord($uid, $giftInfo['id']);
        if (!$pushRecords) {
            return true; // 没有推送记录，可以推送
        }

        // 根据推送频次检查是否可以推送
        switch ($giftInfo['push_frequency']) {
            case 1: // 永久一次
                return false; // 已经推送过，不能再推送

            case 2: // 每次进入
                return true; // 每次都可以推送

            case 3: // 每天
                // 检查今天是否已经推送过
                $todayRecords = $this->getUserTodayPushRecord($uid, $giftInfo['id']);
                return empty($todayRecords);

            case 4: // 每月
                // 检查本月是否已经推送过
                $monthRecords = $this->getUserMonthPushRecord($uid, $giftInfo['id']);
                return empty($monthRecords);

            case 5: // 每周
                // 检查今天是否是设置的星期几，以及本周是否已经推送过
                $weekDay = date('N'); // 1-7 表示周一到周日
                $weekDays = explode(',', $giftInfo['push_week_days']);
                if (!in_array($weekDay, $weekDays)) {
                    return false; // 今天不是设置的星期几，不能推送
                }

                // 检查本周是否已经推送过
                $weekRecords = $this->getUserWeekPushRecord($uid, $giftInfo['id']);
                return empty($weekRecords);

            default:
                return false;
        }
    }

    /**
     * 检查用户是否在推送时段内
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkPushTimeRange(array $giftInfo)
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
     * 用户是否包邮,双倍积分
     * @return
     * User: liusl
     * DateTime: 2025/8/5 16:18
     */
    public function receiveHolidayGift(int $uid, int $type)
    {
        //$type 1: 是否包邮 2: 双倍积分
        $holidayGiftRecordServices = app()->make(HolidayGiftRecordServices::class);
        //已经领取的活动
        $recordList = $holidayGiftRecordServices->selectList(['uid' => $uid]);
        if (!$recordList) {
            return false;
        }
        $recordList = $recordList->toArray();
        $integral_multiple = 0;
        foreach ($recordList as $record) {
            $giftInfo = json_decode($record['gift_content'], true);
            // 检查json_decode是否失败
            if ($giftInfo === null) {
                continue;
            }

            if ($this->checkAdvancePush($giftInfo, true, $uid)) {
                if ($type == 1) {
                    // 检查是否包含包邮权益(类型5)
                    if (isset($giftInfo['gift_type']) && in_array(5, $giftInfo['gift_type'])) {
                        return true;
                    }
                } elseif ($type == 2) {
                    // 检查是否包含双倍积分权益(类型3)
                    if (isset($giftInfo['gift_type']) && in_array(3, $giftInfo['gift_type']) && $giftInfo['integral_multiple']) {
                        $integral_multiple = max($integral_multiple, (int)$giftInfo['integral_multiple']);
                    }
                }
            }
        }

        // 默认返回false
        return $type == 2 && $integral_multiple ? $integral_multiple : false;
    }

    /**
     * 检查是否需要提前推送或当天推送
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkAdvancePush(array $giftInfo, bool $is_advance = false, int $uid = 0)
    {
        if ($giftInfo['task_type'] == 2) {
            // 根据活动日期类型检查
            switch ($giftInfo['activity_date_type']) {
                case 1: // 自定义日期
                    return $this->checkCustomDateAdvancePush($giftInfo, $is_advance);
                case 2: // 每月
                    return $this->checkMonthlyAdvancePush($giftInfo, $is_advance);
                case 3: // 每周
                    return $this->checkWeeklyAdvancePush($giftInfo, $is_advance);
                default:
                    return false;
            }
        } else {
            //生日
            if ($giftInfo['task_type'] == 1) { // 生日任务
                $user = app()->make(UserServices::class)->getUserCacheInfo($uid);
                if(!$user['birthday']){
                    return false;
                }
                $today = strtotime(date('Y-m-d'));
                $userBirthday = strtotime($user['birthday']);
                // 如果 is_advance 为 false，需要判断提前推送
                if (!$is_advance && $giftInfo['advance_push'] == 1 && $giftInfo['advance_days'] > 0) {
                    $advanceDays = (int)$giftInfo['advance_days'];

                    //生日当天 - 提前推送
                    if ($giftInfo['birthday_type'] == 1) {
                        $thisYearBirthday = strtotime(date('Y') . '-' . date('m-d', $userBirthday));
                        // 如果今年生日已过，计算明年生日
                        if ($thisYearBirthday < $today) {
                            $thisYearBirthday = strtotime((date('Y') + 1) . '-' . date('m-d', $userBirthday));
                        }
                        $advancePushDate = $thisYearBirthday - ($advanceDays * 24 * 60 * 60);
                        if ($today < $advancePushDate || $today > $thisYearBirthday) {
                            return false;
                        }
                    }
                    //生日当周 - 提前推送
                    elseif ($giftInfo['birthday_type'] == 2) {
                        $thisYearBirthday = strtotime(date('Y') . '-' . date('m-d', $userBirthday));
                        if ($thisYearBirthday < $today) {
                            $thisYearBirthday = strtotime((date('Y') + 1) . '-' . date('m-d', $userBirthday));
                        }
                        $birthdayWeekStart = strtotime('last monday', $thisYearBirthday);
                        $birthdayWeekEnd = strtotime('next sunday', $birthdayWeekStart);
                        $advancePushDate = $birthdayWeekStart - ($advanceDays * 24 * 60 * 60);
                        if ($today < $advancePushDate || $today > $birthdayWeekEnd) {
                            return false;
                        }
                    }
                    //生日当月 - 提前推送
                    elseif ($giftInfo['birthday_type'] == 3) {
                        $thisYearBirthday = strtotime(date('Y') . '-' . date('m-d', $userBirthday));
                        if ($thisYearBirthday < $today) {
                            $thisYearBirthday = strtotime((date('Y') + 1) . '-' . date('m-d', $userBirthday));
                        }
                        $birthdayMonth = date('Y-m', $thisYearBirthday);
                        $monthStart = strtotime($birthdayMonth . '-01');
                        $monthEnd = strtotime($birthdayMonth . '-' . date('t', $thisYearBirthday));
                        $advancePushDate = $monthStart - ($advanceDays * 24 * 60 * 60);
                        if ($today < $advancePushDate || $today > $monthEnd) {
                            return false;
                        }
                    }
                } else {
                    // 原有逻辑：不提前推送
                    $todayMd = date('m-d');
                    $birthday = date('m-d', $userBirthday);
                    //生日当天
                    if ($giftInfo['birthday_type'] == 1) {
                        if ($todayMd != $birthday) {
                            return false;
                        }
                    }
                    //生日当周
                    elseif ($giftInfo['birthday_type'] == 2) {
                        $birthday = strtotime(date('Y').'-'.date('m-d', $userBirthday));
                        $birthdayWeekStart = strtotime('last monday', $birthday);
                        $birthdayWeekEnd = strtotime('next sunday', $birthdayWeekStart);
                        $todayTimestamp = strtotime('today');
                        if ($todayTimestamp <= $birthdayWeekStart || $todayTimestamp >= $birthdayWeekEnd) {
                            return false;
                        }
                    }
                    //生日当月
                    elseif ($giftInfo['birthday_type'] == 3) {
                        if (date('m') != date('m', $userBirthday)) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 检查自定义日期的提前推送
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    private function checkCustomDateAdvancePush(array $giftInfo, $is_advance = false)
    {
        $currentDate = strtotime(date('Y-m-d'));
        $startDate = $giftInfo['activity_start_date'];
        $endDate = $giftInfo['activity_end_date'];
        // 如果没有设置提前推送，检查是否在活动日期范围内
        if (empty($giftInfo['advance_push']) || empty($giftInfo['advance_days']) || $is_advance) {
            return $currentDate >= $startDate && $currentDate <= $endDate;
        }

        // 计算提前推送日期
        $currentYear = date('Y');
        $startTimestamp = strtotime($currentYear . '-' . $startDate);
        $currentTimestamp = strtotime($currentYear . '-' . $currentDate);

        // 如果开始日期已经过了今年，则计算明年的日期
        if ($startTimestamp < $currentTimestamp) {
            $startTimestamp = strtotime(($currentYear + 1) . '-' . $startDate);
        }

        // 计算天数差
        $daysDiff = round(($startTimestamp - $currentTimestamp) / 86400);

        // 检查是否符合提前推送天数或在活动期间
        return $daysDiff == $giftInfo['advance_days'] || ($currentDate >= $startDate && $currentDate <= $endDate);
    }

    /**
     * 检查每月活动的推送
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    private function checkMonthlyAdvancePush(array $giftInfo, bool $is_advance = false)
    {
        $currentDay = (int)date('d');
        $monthDays = is_array($giftInfo['activity_month_days']) ? $giftInfo['activity_month_days'] : explode(',', $giftInfo['activity_month_days']);
        $monthDays = array_map('intval', $monthDays);

        // 如果没有设置提前推送，检查今天是否是活动日期
        if (empty($giftInfo['advance_push']) || empty($giftInfo['advance_days']) || $is_advance) {
            return in_array($currentDay, $monthDays);
        }

        // 检查是否有活动日期需要提前推送
        foreach ($monthDays as $day) {
            $daysDiff = $day - $currentDay;
            if ($daysDiff < 0) {
                // 如果活动日期已过，计算下个月的天数差
                $daysInMonth = (int)date('t');
                $daysDiff = $daysInMonth - $currentDay + $day;
            }

            if ($daysDiff == $giftInfo['advance_days']) {
                return true;
            }
        }

        // 检查今天是否是活动日期
        return in_array($currentDay, $monthDays);
    }

    /**
     * 检查每周活动的推送
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    private function checkWeeklyAdvancePush(array $giftInfo, bool $is_advance = false)
    {
        $currentWeekDay = (int)date('N'); // 1-7表示周一到周日
        $weekDays = is_array($giftInfo['activity_week_days']) ? $giftInfo['activity_week_days'] : explode(',', $giftInfo['activity_week_days']);
        $weekDays = array_map('intval', $weekDays);

        // 如果没有设置提前推送，检查今天是否是活动日期
        if (empty($giftInfo['advance_push']) || empty($giftInfo['advance_days']) || $is_advance) {
            return in_array($currentWeekDay, $weekDays);
        }

        // 检查是否有活动日期需要提前推送
        foreach ($weekDays as $weekDay) {
            $daysDiff = $weekDay - $currentWeekDay;
            if ($daysDiff < 0) {
                // 如果活动日期已过，计算下周的天数差
                $daysDiff = 7 + $daysDiff;
            }

            if ($daysDiff == $giftInfo['advance_days']) {
                return true;
            }
        }

        // 检查今天是否是活动日期
        return in_array($currentWeekDay, $weekDays);
    }

    /**
     * 检查用户是否符合节日有礼活动条件
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return bool
     */
    public function checkUserCondition(int $uid, array $giftInfo)
    {
        /** @var HolidayGiftServices $giftService */
        $giftService = app()->make(HolidayGiftServices::class);
        return $giftService->checkUserCondition($uid, $giftInfo);
    }


    public function saveGiftPush(array $ids, int $uid, array $data = [])
    {
        if (!$ids) return true;
        $saveData = [];
        foreach ($ids as $id) {
            $data['gift_id'] = $id;
            $data['uid'] = $uid;
            $data['push_time'] = $data['add_time'] = time();
            $saveData[] = $data;
        }
        $this->dao->saveAll($saveData);
        return true;
    }

    /**
     * 定时任务处理节日有礼活动
     * 获取当前活动日期的活动，判断给用户赠送积分、余额等权益
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handleHolidayGiftTask()
    {
        try {
            // 获取当前有效的节日有礼活动
            /** @var HolidayGiftServices $giftService */
            $giftService = app()->make(HolidayGiftServices::class);
            $activeGifts = $giftService->getActiveHolidayGifts();
            if (empty($activeGifts)) {
                return true;
            }

            foreach ($activeGifts as $giftInfo) {
                // 获取符合条件的用户并执行推送
                $this->processGiftForUsers($giftInfo);
            }

            return true;
        } catch (\Exception $e) {
            // 记录错误日志
            Log::error('节日有礼定时任务执行失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 为符合条件的用户处理节日有礼
     * @param array $giftInfo 节日有礼活动信息
     * @return
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    private function processGiftForUsers(array $giftInfo)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        /** @var HolidayGiftServices $giftService */
        $giftService = app()->make(HolidayGiftServices::class);

        // 获取符合条件的用户
        $userIds = $this->getEligibleUsers($giftInfo);
        if (empty($userIds)) {
            return true;
        }
        $successCount = 0;
        $failCount = 0;

        // 将用户处理任务分发到队列中，避免大量用户时的性能问题
        foreach ($userIds as $uid) {
//            try {
//                $this->grantUserBenefits($uid, $giftInfo);
//                $this->recordPushLog($uid, $giftInfo);
            // 分发到队列处理
            HolidayGiftJob::dispatch('doJob', [$uid, $giftInfo]);
            $successCount++;
//            } catch (\Exception $e) {
//                Log::error("用户{$uid}节日有礼队列分发失败: " . $e->getMessage());
//                $failCount++;
//            }
        }

        // 记录处理结果日志
        Log::info("节日有礼活动[{$giftInfo['id']}]已分发到队列处理，总用户数：" . count($userIds) . "，成功分发:{$successCount}人，分发失败:{$failCount}人");

        return [
            'dispatched' => $successCount,
            'dispatch_failed' => $failCount,
            'total' => count($userIds)
        ];
    }

    /**
     * 获取符合条件的用户ID列表
     * @param array $giftInfo 节日有礼活动信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getEligibleUsers(array $giftInfo)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);

        $where = ['status' => 1, 'is_del' => 0];

        // 如果是生日任务，需要特殊处理
        if ($giftInfo['task_type'] == 1) { // 生日任务
            $today = date('m-d');
            if ($giftInfo['birthday_type'] == 1) { // 阳历生日
                $where['birthday'] = ['like', '%' . $today];
            }
        }
        $query = $userService->search($where)
            ->when($giftInfo['task_type'] == 2 && $giftInfo['push_user_type'] == 2, function ($query) use ($giftInfo) {
                //自定义活动和指定人群
                if ($giftInfo['condition_type'] == 1) {
                    //满足任一条件
                } else {
                    //满足全部条件
                    $query->when($giftInfo['user_level'], function ($query) use ($giftInfo) {
                        $query->whereIn('level', $giftInfo['user_level']);
                    })->when($giftInfo['user_tag'], function ($query) use ($giftInfo) {
                        //1等级会员,2付费会员,3推广员,4采购商
                        if (in_array(1, $giftInfo['user_tag'])) {
                            $query->where('level', '>', 0);
                        }
                        if (in_array(2, $giftInfo['user_tag'])) {
                            $query->where(function ($query) {
                                $query->where(function ($query) {
                                    $query->where('is_money_level', '>', 0)->where('overdue_time', '>', time());
                                })->whereOr(function ($query) {
                                    $query->where('is_ever_level', '>', 0);
                                });
                            });
                        }
                        if (in_array(3, $giftInfo['user_tag'])) {
                            $query->where('is_promoter', 1);
                        }
                        if (in_array(4, $giftInfo['user_tag'])) {
                            $query->where('is_channel', 1);
                        }
                    })->when($giftInfo['user_label'], function ($query) use ($giftInfo) {
                        $user_label = $giftInfo['user_label'];
                        $query->whereIn('uid', function ($q) use ($user_label) {
                            $q->name('user_label_relation')->whereIn('label_id', $user_label)->field('uid')->select();
                        });
                    });

                }
            });
        $users = $query->field('uid')->select()->toArray();
        return array_column($users, 'uid');
    }

    /**
     * 给用户赠送权益
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @return array|bool
     */
    public function grantUserBenefits(int $uid, array $giftInfo)
    {
        /** @var UserServices $userService */
        $userService = app()->make(UserServices::class);
        $grantResults = true;

        Log::info("开始为用户{$uid}赠送节日有礼权益，活动ID：{$giftInfo['id']}");

        // 赠送积分
        if (in_array(2, $giftInfo['gift_type'])) { // 积分
            if (!empty($giftInfo['integral'])) {
                $userInfo = $userService->getUserInfo($uid);
                if ($userInfo) {
                    $newIntegral = bcadd((string)$userInfo['integral'], (string)$giftInfo['integral'], 0);
                    /** @var UserBillServices $userBillService */
                    $userBillService = app()->make(UserBillServices::class);
                    $billResult = $userBillService->income('holiday_gift_integral', $uid, (int)$giftInfo['integral'], (int)$newIntegral, 0, 0, '节日有礼赠送积分');
                    $updateResult = $userService->update($uid, ['integral' => $newIntegral]);
                    $integralSuccess = $billResult && $updateResult;
                    if ($integralSuccess) {
                        Log::info("用户{$uid}积分赠送成功，赠送积分：{$giftInfo['integral']}");
                    } else {
                        Log::error("用户{$uid}积分赠送失败，赠送积分：{$giftInfo['integral']}");
                        $grantResults = false;
                    }
                } else {
                    Log::error("用户{$uid}不存在，积分赠送失败");
                    $grantResults = false;
                }
            }
        }

        // 赠送余额
        if (in_array(4, $giftInfo['gift_type'])) { // 余额
            if (!empty($giftInfo['balance'])) {
                $userInfo = $userService->getUserInfo($uid);
                if ($userInfo) {
                    $newMoney = bcadd((string)$userInfo['now_money'], (string)$giftInfo['balance'], 2);
                    /** @var UserMoneyServices $userMoneyService */
                    $userMoneyService = app()->make(UserMoneyServices::class);
                    $billResult = $userMoneyService->income('holiday_gift_money', $uid, $giftInfo['balance'], $newMoney, 0, '', '节日有礼赠送余额');
                    $updateResult = $userService->update($uid, ['now_money' => $newMoney]);
                    $moneySuccess = $billResult && $updateResult;
                    if ($moneySuccess) {
                        Log::info("用户{$uid}余额赠送成功，赠送余额：{$giftInfo['balance']}");
                    } else {
                        Log::error("用户{$uid}余额赠送失败，赠送余额：{$giftInfo['balance']}");
                        $grantResults = false;
                    }
                } else {
                    Log::error("用户{$uid}不存在，余额赠送失败");
                    $grantResults = false;
                }
            }
        }

        // 赠送优惠券
        if (in_array(1, $giftInfo['gift_type'])) {
            if (!empty($giftInfo['coupon_ids'])) {
                try {
                    /** @var StoreCouponIssueServices $issueService */
                    $issueService = app()->make(StoreCouponIssueServices::class);
                    $couponList = $issueService->search([])->whereIn('id', $giftInfo['coupon_ids'])->select()->toArray();
                    if ($couponList) {
                        /** @var StoreCouponIssueServices $storeCouponIssueServices */
                        $storeCouponIssueServices = app()->make(StoreCouponIssueServices::class);
                        $couponSuccess = true;
                        foreach ($couponList as $coupon) {
                            $res = $storeCouponIssueServices->setCoupon($coupon, [$uid], '', '', true);
                            if (!$res) {
                                $couponSuccess = false;
                                Log::error("用户{$uid}优惠券赠送失败，优惠券ID：{$coupon['id']}");
                            }
                        }
                        if ($couponSuccess) {
                            Log::info("用户{$uid}优惠券赠送成功，优惠券IDs：" . implode(',', $giftInfo['coupon_ids']));
                        } else {
                            $grantResults = false;
                        }
                    } else {
                        Log::error("用户{$uid}优惠券赠送失败，未找到有效优惠券");
                        $grantResults = false;
                    }
                } catch (\Exception $e) {
                    Log::error("用户{$uid}优惠券赠送异常：" . $e->getMessage());
                    $grantResults = false;
                }
            }
        }

        if ($grantResults) {
            Log::info("用户{$uid}节日有礼权益赠送完成，活动ID：{$giftInfo['id']}");
        } else {
            Log::error("用户{$uid}节日有礼权益赠送失败，活动ID：{$giftInfo['id']}");
        }

        return $grantResults;
    }

    /**
     * 记录推送日志
     * @param int $uid 用户ID
     * @param array $giftInfo 节日有礼活动信息
     * @param array $grantResults 赠送结果详情
     * @return bool|\crmeb\basic\BaseModel|\think\Model
     */
    public function recordPushLog(int $uid, array $giftInfo)
    {
        try {
            // 记录详细的礼品记录到 holiday_gift_record 表
            /** @var HolidayGiftRecordServices $recordService */
            $recordService = app()->make(HolidayGiftRecordServices::class);

            $recordData = [
                'uid' => $uid,
                'gift_id' => $giftInfo['id'],
                'gift_type' => implode(',', $giftInfo['gift_type']),
                'gift_content' => json_encode($giftInfo),
                'receive_time' => time(),
                'add_time' => time()
            ];
            $recordResult = $recordService->dao->save($recordData);

            if (!$recordResult) {
                Log::error("节日有礼记录保存失败: 用户ID {$uid}, 活动ID {$giftInfo['id']}");
                return false;
            }

            return $recordResult;
        } catch (\Exception $e) {
            Log::error("节日有礼记录保存异常: " . $e->getMessage());
            return false;
        }
    }
}
