<?php

declare (strict_types=1);

namespace app\services\activity\holiday;

use app\dao\activity\holiday\HolidayGiftRecordDao;
use app\services\BaseServices;
use app\services\user\UserServices;
use crmeb\exceptions\AdminException;
use crmeb\exceptions\ApiException;
use think\exception\ValidateException;

/**
 * 节日有礼领取记录服务
 * Class HolidayGiftRecordServices
 * @package app\services\activity
 * @mixin HolidayGiftRecordDao
 */
class HolidayGiftRecordServices extends BaseServices
{
    /**
     * 构造方法
     * HolidayGiftRecordServices constructor.
     * @param HolidayGiftRecordDao $dao
     */
    public function __construct(HolidayGiftRecordDao $dao)
    {
        $this->dao = $dao;
    }

    /**
     * 获取节日有礼领取记录列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftRecordList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getHolidayGiftRecordList($where, '*', $page, $limit);
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
     * 获取节日有礼领取记录详情
     * @param int $id
     * @param string $field
     * @return array|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftRecordInfo(int $id, string $field = '*')
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        $info = $this->dao->get($id, $field);
        if (!$info) {
            throw new AdminException('领取记录不存在');
        }
        return $info;
    }

    /**
     * 获取用户领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserGiftRecord(int $uid, int $giftId)
    {
        if (!$uid || !$giftId) {
            return null;
        }
        return $this->dao->getOne(['uid' => $uid, 'gift_id' => $giftId, 'is_del' => 0]);
    }

    /**
     * 获取用户今日领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserTodayGiftRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $where = [
            'uid' => $uid,
            'is_del' => 0,
            'receive_time' => ['>=', strtotime(date('Y-m-d'))]
        ];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getList($where);
    }

    /**
     * 获取用户本周领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserWeekGiftRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $weekStart = strtotime(date('Y-m-d', strtotime('this week Monday')));
        $where = [
            'uid' => $uid,
            'is_del' => 0,
            'receive_time' => ['>=', $weekStart]
        ];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getList($where);
    }

    /**
     * 获取用户本月领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID，可选
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserMonthGiftRecord(int $uid, int $giftId = 0)
    {
        if (!$uid) {
            return [];
        }
        $monthStart = strtotime(date('Y-m-01'));
        $where = [
            'uid' => $uid,
            'is_del' => 0,
            'receive_time' => ['>=', $monthStart]
        ];
        if ($giftId) {
            $where['gift_id'] = $giftId;
        }
        return $this->dao->getList($where);
    }

    /**
     * 更新节日有礼领取记录状态
     * @param int $id 记录ID
     * @param int $status 状态：0未使用，1已使用
     * @return mixed
     */
    public function updateRecordStatus(int $id, int $status)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        $data = ['status' => $status];
        if ($status == 1) {
            $data['use_time'] = time();
        }
        return $this->dao->update($id, $data);
    }

    /**
     * 删除节日有礼领取记录
     * @param int $id 记录ID
     * @return mixed
     */
    public function deleteRecord(int $id)
    {
        if (!$id) {
            throw new AdminException('参数错误');
        }
        return $this->dao->update($id, ['is_del' => 1]);
    }

    /**
     * 批量删除节日有礼领取记录
     * @param array $ids 记录ID数组
     * @return mixed
     */
    public function batchDeleteRecord(array $ids)
    {
        if (!$ids) {
            throw new AdminException('参数错误');
        }
        return $this->dao->batchUpdate($ids, ['is_del' => 1]);
    }

    /**
     * 统计节日有礼领取数据
     * @param array $where
     * @return array
     */
    public function getRecordStatistics(array $where = [])
    {
        // 总领取人数
        $totalUsers = $this->dao->distinct(true)->field('uid')->where($where)->count();

        // 总领取次数
        $totalRecords = $this->dao->count($where);

        // 今日领取人数
        $todayWhere = $where;
        $todayWhere['receive_time'] = ['>=', strtotime(date('Y-m-d'))];
        $todayUsers = $this->dao->distinct(true)->field('uid')->where($todayWhere)->count();

        // 今日领取次数
        $todayRecords = $this->dao->count($todayWhere);

        // 昨日领取人数
        $yesterdayStart = strtotime(date('Y-m-d', strtotime('-1 day')));
        $yesterdayEnd = strtotime(date('Y-m-d')) - 1;
        $yesterdayWhere = $where;
        $yesterdayWhere['receive_time'] = ['between', [$yesterdayStart, $yesterdayEnd]];
        $yesterdayUsers = $this->dao->distinct(true)->field('uid')->where($yesterdayWhere)->count();

        // 昨日领取次数
        $yesterdayRecords = $this->dao->count($yesterdayWhere);

        // 本月领取人数
        $monthWhere = $where;
        $monthWhere['receive_time'] = ['>=', strtotime(date('Y-m-01'))];
        $monthUsers = $this->dao->distinct(true)->field('uid')->where($monthWhere)->count();

        // 本月领取次数
        $monthRecords = $this->dao->count($monthWhere);

        return [
            'total_users' => $totalUsers,
            'total_records' => $totalRecords,
            'today_users' => $todayUsers,
            'today_records' => $todayRecords,
            'yesterday_users' => $yesterdayUsers,
            'yesterday_records' => $yesterdayRecords,
            'month_users' => $monthUsers,
            'month_records' => $monthRecords
        ];
    }

    /**
     * 获取用户可用的多倍积分记录
     * @param int $uid 用户ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAvailableMultipleIntegral(int $uid)
    {
        if (!$uid) {
            return [];
        }

        // 查询用户领取的多倍积分记录，且未使用的
        $where = [
            'uid' => $uid,
            'is_del' => 0,
            'status' => 0,
            'gift_type' => ['like', '%3%'] // 包含多倍积分类型
        ];

        $list = $this->dao->getList($where);
        if (!$list) {
            return [];
        }

        $now = time();
        $result = [];

        // 获取节日有礼活动信息
        $giftIds = array_column($list, 'gift_id');
        /** @var HolidayGiftServices $giftService */
        $giftService = app()->make(HolidayGiftServices::class);
        $giftInfos = $giftService->getHolidayGiftList(['id' => $giftIds])['list'];
        $giftInfos = array_combine(array_column($giftInfos, 'id'), $giftInfos);

        foreach ($list as $item) {
            $giftInfo = $giftInfos[$item['gift_id']] ?? null;
            if (!$giftInfo) {
                continue;
            }

            // 检查活动是否有效
            if ($giftInfo['status'] != 1 || $giftInfo['is_del'] == 1) {
                continue;
            }

            // 检查活动时间（如果不是永久活动）
            if ((!isset($giftInfo['is_permanent']) || $giftInfo['is_permanent'] != 1) && 
                ($now < $giftInfo['start_time'] || $now > $giftInfo['end_time'])) {
                continue;
            }

            // 添加到结果中
            $result[] = [
                'record_id' => $item['id'],
                'gift_id' => $item['gift_id'],
                'gift_name' => $giftInfo['name'],
                'integral_multiple' => $giftInfo['integral_multiple'],
                'receive_time' => $item['receive_time'],
                'end_time' => $giftInfo['end_time']
            ];
        }

        return $result;
    }

    /**
     * 获取用户可用的全场包邮记录
     * @param int $uid 用户ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAvailableFreeShipping(int $uid)
    {
        if (!$uid) {
            return [];
        }

        // 查询用户领取的全场包邮记录，且未使用的
        $where = [
            'uid' => $uid,
            'is_del' => 0,
            'status' => 0,
            'gift_type' => ['like', '%5%'] // 包含全场包邮类型
        ];

        $list = $this->dao->getList($where);
        if (!$list) {
            return [];
        }

        $now = time();
        $result = [];

        // 获取节日有礼活动信息
        $giftIds = array_column($list, 'gift_id');
        /** @var HolidayGiftServices $giftService */
        $giftService = app()->make(HolidayGiftServices::class);
        $giftInfos = $giftService->getHolidayGiftList(['id' => $giftIds])['list'];
        $giftInfos = array_combine(array_column($giftInfos, 'id'), $giftInfos);

        foreach ($list as $item) {
            $giftInfo = $giftInfos[$item['gift_id']] ?? null;
            if (!$giftInfo) {
                continue;
            }

            // 检查活动是否有效
            if ($giftInfo['status'] != 1 || $giftInfo['is_del'] == 1) {
                continue;
            }

            // 检查活动时间（如果不是永久活动）
            if ((!isset($giftInfo['is_permanent']) || $giftInfo['is_permanent'] != 1) && 
                ($now < $giftInfo['start_time'] || $now > $giftInfo['end_time'])) {
                continue;
            }

            // 添加到结果中
            $result[] = [
                'record_id' => $item['id'],
                'gift_id' => $item['gift_id'],
                'gift_name' => $giftInfo['name'],
                'receive_time' => $item['receive_time'],
                'end_time' => isset($giftInfo['is_permanent']) && $giftInfo['is_permanent'] == 1 ? 0 : $giftInfo['end_time']
            ];
        }

        return $result;
    }
}
