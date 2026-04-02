<?php

declare (strict_types=1);

namespace app\model\activity\holiday;

use app\model\user\User;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * 节日有礼领取记录模型
 * Class HolidayGiftRecord
 * @package app\model\activity
 */
class HolidayGiftRecord extends BaseModel
{
    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'holiday_gift_record';

    /**
     * 自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * 添加时间获取器
     * @param $value
     * @return string
     */
    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', (int)$value) : '';
    }

    /**
     * 领取时间获取器
     * @param $value
     * @return string
     */
    public function getReceiveTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', (int)$value) : '';
    }

    /**
     * 使用时间获取器
     * @param $value
     * @return string
     */
    public function getUseTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', (int)$value) : '';
    }

    /**
     * 状态获取器
     * @param $value
     * @return string
     */
    public function getStatusNameAttr($value, $data)
    {
        return $data['status'] ? '已使用' : '未使用';
    }

    /**
     * 关联用户表
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->bind([
            'nickname',
            'avatar',
            'phone'
        ]);
    }

    /**
     * 关联节日有礼表
     * @return \think\model\relation\HasOne
     */
    public function holidayGift()
    {
        return $this->hasOne(HolidayGift::class, 'id', 'gift_id')->bind([
            'gift_name' => 'name'
        ]);
    }

    /**
     * 赠送内容类型获取器
     * @param $value
     * @return array
     */
    public function getGiftTypeNameAttr($value, $data)
    {
        $giftTypes = explode(',', $data['gift_type']);
        $names = [];
        foreach ($giftTypes as $type) {
            if (isset(HolidayGift::$giftTypeMap[$type])) {
                $names[] = HolidayGift::$giftTypeMap[$type];
            }
        }
        return $names;
    }

    /**
     * 节日有礼活动ID搜索器
     * @param $query
     * @param $value
     * @return void
     */
    public function searchGiftIdAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('gift_id', $value);
        }
    }

    /**
     * 用户ID搜索器
     * @param $query
     * @param $value
     * @return void
     */
    public function searchUidAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('uid', $value);
        }
    }

    /**
     * 领取时间搜索器
     * @param $query
     * @param $value
     * @return void
     */
    public function searchReceiveTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetween('receive_time', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('receive_time', $value);
        }
    }

    /**
     * 添加时间搜索器
     * @param $query
     * @param $value
     * @return void
     */
    public function searchAddTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetween('add_time', $value);
        } elseif ($value !== '' && $value !== null) {
            $query->where('add_time', $value);
        }
    }

    /**
     * 礼物类型搜索器
     * @param $query
     * @param $value
     * @return void
     */
    public function searchGiftTypeAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            if (is_array($value)) {
                $query->where(function ($q) use ($value) {
                    foreach ($value as $type) {
                        $q->whereOr('gift_type', 'like', '%' . $type . '%');
                    }
                });
            } else {
                $query->where('gift_type', 'like', '%' . $value . '%');
            }
        }
    }

    /**
     * 时间范围搜索器（通用）
     * @param $query
     * @param $value
     * @param $field
     * @return void
     */
    public function searchTimeRangeAttr($query, $value, $field = 'add_time')
    {
        if (is_array($value) && count($value) == 2) {
            $startTime = is_numeric($value[0]) ? $value[0] : strtotime($value[0]);
            $endTime = is_numeric($value[1]) ? $value[1] : strtotime($value[1]);
            $query->whereBetween($field, [$startTime, $endTime]);
        }
    }

    /**
     * 关键词搜索器（搜索礼品内容）
     * @param $query
     * @param $value
     * @return void
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value !== '' && $value !== null) {
            $query->where('gift_content', 'like', '%' . $value . '%');
        }
    }
}
