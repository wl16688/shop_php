<?php

declare (strict_types=1);

namespace app\dao\activity\holiday;

use app\dao\BaseDao;
use app\model\activity\holiday\HolidayGiftRecord;

/**
 * 节日有礼领取记录数据层
 * Class HolidayGiftRecordDao
 * @package app\dao\activity
 */
class HolidayGiftRecordDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return HolidayGiftRecord::class;
    }

    /**
     * 获取节日有礼领取记录列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftRecordList(array $where, string $field = '*', int $page = 0, int $limit = 0)
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
     * 获取节日有礼领取记录数量
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        return $this->getModel()->where($where)->count();
    }

    /**
     * 获取用户领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserGiftRecord(int $uid, int $giftId, string $field = '*')
    {
        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->field($field)
            ->find();
    }

    /**
     * 获取用户今日领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserTodayGiftRecord(int $uid, int $giftId, string $field = '*')
    {
        $today = strtotime(date('Y-m-d'));
        $tomorrow = $today + 86400;

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->where('receive_time', '>=', $today)
            ->where('receive_time', '<', $tomorrow)
            ->field($field)
            ->find();
    }

    /**
     * 获取用户本周领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserWeekGiftRecord(int $uid, int $giftId, string $field = '*')
    {
        $weekStart = strtotime(date('Y-m-d', strtotime('this week Monday')));
        $weekEnd = $weekStart + 7 * 86400;

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->where('receive_time', '>=', $weekStart)
            ->where('receive_time', '<', $weekEnd)
            ->field($field)
            ->find();
    }

    /**
     * 获取用户本月领取的节日有礼记录
     * @param int $uid 用户ID
     * @param int $giftId 节日有礼活动ID
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserMonthGiftRecord(int $uid, int $giftId, string $field = '*')
    {
        $monthStart = strtotime(date('Y-m-01'));
        $monthEnd = strtotime(date('Y-m-01', strtotime('+1 month')));

        return $this->getModel()->where('uid', $uid)
            ->where('gift_id', $giftId)
            ->where('receive_time', '>=', $monthStart)
            ->where('receive_time', '<', $monthEnd)
            ->field($field)
            ->find();
    }

    public function checkUserCanPush(int $uid,int $gifId)
    {
        $where = [
            'uid' => $uid,
            'gift_id' => $gifId,
        ];
        return !!$this->search($where)->find();
    }
}
