<?php

declare (strict_types=1);

namespace app\dao\activity\holiday;

use app\dao\BaseDao;
use app\model\activity\holiday\HolidayGiftPush;

/**
 * 节日有礼推送记录数据层
 * Class HolidayGiftPushDao
 * @package app\dao\activity
 */
class HolidayGiftPushDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return HolidayGiftPush::class;
    }

    /**
     * 获取节日有礼推送记录列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftPushList(array $where, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->getModel()->where($where)->field($field)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取节日有礼推送记录数量
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 获取用户今日推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param int $pushType 推送类型
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserTodayPushRecord(int $uid, int $giftId, int $pushType = 0, string $field = '*')
    {
        $today = strtotime(date('Y-m-d'));
        $tomorrow = $today + 86400;

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->when($pushType, function ($query) use ($pushType) {
                $query->where('push_type', $pushType);
            })
            ->where('push_time', '>=', $today)
            ->where('push_time', '<', $tomorrow)
            ->field($field)
            ->find();
    }

    /**
     * 获取用户本周推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param int $pushType 推送类型
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserWeekPushRecord(int $uid, int $giftId, int $pushType = 0, string $field = '*')
    {
        $weekStart = strtotime(date('Y-m-d', strtotime('this week Monday')));
        $weekEnd = $weekStart + 7 * 86400;

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->when($pushType, function ($query) use ($pushType) {
                $query->where('push_type', $pushType);
            })
            ->where('push_time', '>=', $weekStart)
            ->where('push_time', '<', $weekEnd)
            ->field($field)
            ->find();
    }

    /**
     * 获取用户本月推送记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param int $pushType 推送类型
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserMonthPushRecord(int $uid, int $giftId, int $pushType = 0, string $field = '*')
    {
        $monthStart = strtotime(date('Y-m-01'));
        $monthEnd = strtotime(date('Y-m-01', strtotime('+1 month')));

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->when($pushType, function ($query) use ($pushType) {
                $query->where('push_type', $pushType);
            })
            ->where('push_time', '>=', $monthStart)
            ->where('push_time', '<', $monthEnd)
            ->field($field)
            ->find();
    }
}
