<?php

declare (strict_types=1);

namespace app\dao\activity\holiday;

use app\dao\BaseDao;
use app\model\activity\holiday\HolidayGift;

/**
 * 节日有礼数据层
 * Class HolidayGiftDao
 * @package app\dao\activity
 */
class HolidayGiftDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return HolidayGift::class;
    }

    /**
     * 获取节日有礼列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getHolidayGiftList(array $where, string $field = '*', int $page = 0, int $limit = 0)
    {
        return $this->search($where)->field($field)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->order('id desc')
            ->select()
            ->toArray();
    }


    /**
     * 获取正在进行中的节日有礼活动
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getActiveHolidayGift(array $where = [], string $field = '*')
    {
        $time = time();
        return $this->getModel()->where($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where(function ($query) use ($time) {
                $query->whereOr([
                    ['is_permanent', '=', 1], // 永久活动
                    [['start_time', '<=', $time], ['end_time', '>=', $time]] // 时间范围内的活动
                ]);
            })
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取用户生日相关的节日有礼活动
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBirthdayHolidayGift(array $where = [], string $field = '*')
    {
        $time = time();
        return $this->getModel()->where($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('task_type', 1) // 用户生日类型
            ->where(function ($query) use ($time) {
                $query->whereOr([
                    ['is_permanent', '=', 1], // 永久活动
                    [['start_time', '<=', $time], ['end_time', '>=', $time]] // 时间范围内的活动
                ]);
            })
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取活动日期相关的节日有礼活动
     * @param string $date 日期，格式：MM-DD
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCustomDateHolidayGift(string $date, array $where = [], string $field = '*')
    {
        $time = time();
        return $this->getModel()->where($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('task_type', 2) // 活动日期类型
            ->where('activity_date_type', 1) // 自定义日期
            ->where(function ($query) use ($date) {
                $query->where('activity_start_date', '<=', $date)
                      ->where('activity_end_date', '>=', $date);
            })
            ->where(function ($query) use ($time) {
                $query->whereOr([
                    ['is_permanent', '=', 1], // 永久活动
                    [['start_time', '<=', $time], ['end_time', '>=', $time]] // 时间范围内的活动
                ]);
            })
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取每月活动日期相关的节日有礼活动
     * @param int $day 日期，1-31
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getMonthlyHolidayGift(int $day, array $where = [], string $field = '*')
    {
        $time = time();
        return $this->getModel()->where($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('task_type', 2) // 活动日期类型
            ->where('activity_date_type', 2) // 每月
            ->whereRaw("FIND_IN_SET(?, activity_month_days)", [$day])
            ->where(function ($query) use ($time) {
                $query->whereOr([
                    ['is_permanent', '=', 1], // 永久活动
                    [['start_time', '<=', $time], ['end_time', '>=', $time]] // 时间范围内的活动
                ]);
            })
            ->field($field)
            ->select()
            ->toArray();
    }

    /**
     * 获取每周活动日期相关的节日有礼活动
     * @param int $weekDay 周几，1-7表示周一到周日
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getWeeklyHolidayGift(int $weekDay, array $where = [], string $field = '*')
    {
        $time = time();
        return $this->getModel()->where($where)
            ->where('status', 1)
            ->where('is_del', 0)
            ->where('task_type', 2) // 活动日期类型
            ->where('activity_date_type', 3) // 每周
            ->whereRaw("FIND_IN_SET(?, activity_week_days)", [$weekDay])
            ->where(function ($query) use ($time) {
                $query->whereOr([
                    ['is_permanent', '=', 1], // 永久活动
                    [['start_time', '<=', $time], ['end_time', '>=', $time]] // 时间范围内的活动
                ]);
            })
            ->field($field)
            ->select()
            ->toArray();
    }
}
