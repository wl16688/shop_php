<?php

declare (strict_types=1);

namespace app\model\activity\holiday;

use app\model\user\User;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * 节日有礼模型
 * Class HolidayGift
 * @package app\model\activity
 */
class HolidayGift extends BaseModel
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
    protected $name = 'holiday_gift';

    /**
     * 自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * 任务类型
     * @var array
     */
    public const TASK_TYPE = [
        1 => '用户生日',
        2 => '活动日期'
    ];

    /**
     * 活动日期类型
     * @var array
     */
    public const ACTIVITY_DATE_TYPE = [
        1 => '自定义日期',
        2 => '每月',
        3 => '每周'
    ];

    /**
     * 生日类型
     * @var array
     */
    public const BIRTHDAY_TYPE = [
        1 => '生日当天',
        2 => '生日当周',
        3 => '生日当月',
        0 => '无'
    ];

    /**
     * 推送时段类型
     * @var array
     */
    public const PUSH_TIME_TYPE = [
        1 => '全时段',
        2 => '指定时段'
    ];

    /**
     * 推送人群类型
     * @var array
     */
    public const PUSH_USER_TYPE = [
        1 => '全部人群',
        2 => '指定人群'
    ];

    /**
     * 条件满足类型
     * @var array
     */
    public const CONDITION_TYPE = [
        1 => '满足任一条件',
        2 => '满足全部条件'
    ];

    /**
     * 礼品类型
     * @var array
     */
    public const GIFT_TYPE = [
        1 => '优惠券',
        2 => '积分',
        3 => '多倍积分',
        4 => '余额',
        5 => '全场包邮'
    ];

    /**
     * 推送渠道
     * @var array
     */
    public const PUSH_CHANNEL = [
        1 => '短信',
        2 => '公众号',
        3 => '弹框广告'
    ];

    /**
     * 推送频次
     * @var array
     */
    public const PUSH_FREQUENCY = [
        1 => '永久一次',
        2 => '每次进入',
        3 => '每天',
        4 => '每月',
        5 => '每周'
    ];

    /**
     * 显示页面
     * @var array
     */
    public const SHOW_PAGE = [
        1 => '商城首页',
        2 => '分类页',
        3 => '购物车',
        4 => '个人中心',
        5 => '支付成功',
        6 => '专题页面'
    ];

    /**
     * 状态
     * @var array
     */
    public const STATUS = [
        0 => '关闭',
        1 => '开启'
    ];

    /**
     * 是否提前推送
     * @var array
     */
    public const ADVANCE_PUSH = [
        0 => '否',
        1 => '是'
    ];

    /**
     * 是否永久
     * @var array
     */
    public const IS_PERMANENT = [
        0 => '否',
        1 => '是'
    ];

    /**
     * 推送类型
     * @var array
     */
    public const PUSH_TYPE = [
        1 => '短信',
        2 => '公众号',
        3 => '弹框广告'
    ];

    /**
     * 推送状态
     * @var array
     */
    public const PUSH_STATUS = [
        0 => '推送失败',
        1 => '推送成功'
    ];

    /**
     * 记录状态
     * @var array
     */
    public const RECORD_STATUS = [
        0 => '未使用',
        1 => '已使用'
    ];


    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 获取用户标签属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 用户标签数组
     */
    public function getUserLabelAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }


    /**
     * 设置用户标签属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 用户标签数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setUserLabelAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取用户自定义标签属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 用户自定义标签数组
     */
    public function getUserTagAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    public function getUserLevelAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    public function setUserLevelAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }


    /**
     * 设置用户自定义标签属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 用户自定义标签数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setUserTagAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取礼品类型属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 礼品类型数组
     */
    public function getGiftTypeAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置礼品类型属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 礼品类型数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setGiftTypeAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取优惠券ID属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 优惠券ID数组
     */
    public function getCouponIdsAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置优惠券ID属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 优惠券ID数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setCouponIdsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取推送渠道属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 推送渠道数组
     */
    public function getPushChannelAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置推送渠道属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 推送渠道数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setPushChannelAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取推送周期属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 推送周期数组
     */
    public function getPushWeekDaysAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置推送周期属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 推送周期数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setPushWeekDaysAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取显示页面属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 显示页面数组
     */
    public function getShowPageAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置展示页面属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 展示页面数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setShowPageAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取微信图片属性
     * 将JSON字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 微信图片数组
     */
    public function getWechatImageAttr($value)
    {
        if (!$value) return [];
        $array = json_decode($value, true);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 递归将数组中的数字字符串转换为整型
     * @param array $array 需要处理的数组
     * @return array|int
     */
    private function convertNumericStringsToInt($array)
    {
        if (!is_array($array)) {
            return is_numeric($array) ? (int)$array : $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->convertNumericStringsToInt($value);
            } else if (is_numeric($value)) {
                $array[$key] = (int)$value;
            }
        }

        return $array;
    }

    /**
     * 设置微信图片属性
     * 将数组转换为JSON字符串
     * @param array|string $value 微信图片数组或字符串
     * @return string JSON格式的字符串
     */
    public function setWechatImageAttr($value)
    {
        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
    }

    /**
     * 获取话题ID属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 话题ID数组
     */
    public function getTopicIdsAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置专题ID属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 专题ID数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setTopicIdsAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取活动月份日期属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 活动月份日期数组
     */
    public function getActivityMonthDaysAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置活动月份日期属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 活动月份日期数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setActivityMonthDaysAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 获取活动周日期属性
     * 将逗号分隔的字符串转换为数组，并将字符串数字转为整型
     * @param string|null $value 原始值
     * @return array 活动周日期数组
     */
    public function getActivityWeekDaysAttr($value)
    {
        if (!$value) return [];
        $array = explode(',', $value);
        return $this->convertNumericStringsToInt($array);
    }

    /**
     * 设置活动周日期属性
     * 将数组转换为逗号分隔的字符串
     * @param array|string $value 活动周日期数组或字符串
     * @return string 逗号分隔的字符串
     */
    public function setActivityWeekDaysAttr($value)
    {
        return is_array($value) ? implode(',', $value) : $value;
    }

    /**
     * 根据ID搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchIdAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('id', $value);
            } else {
                $query->where('id', $value);
            }
        }
        return $query;
    }

    /**
     * 根据名称搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchNameAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('name', '%' . $value . '%');
        }
        return $query;
    }

    /**
     * 根据删除状态搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
        return $query;
    }

    /**
     * 根据状态搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
        return $query;
    }

    /**
     * 根据任务类型搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchTaskTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('task_type', $value);
        }
        return $query;
    }

    /**
     * 根据生日类型搜索
     * @param Model $query 查询对象
     * @param mixed $value 搜索值
     * @return Model
     */
    public function searchBirthdayTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('birthday_type', $value);
        }
        return $query;
    }

    /**
     * 搜索有效期内的活动
     * @param Model $query 查询对象
     * @param mixed $value 是否在有效期内，true表示在有效期内，false表示不限制
     * @return Model
     */
    public function searchIsValidAttr($query, $value = true)
    {
        if ($value !== '') {
            $query->where(function ($query) {
                $query->where('is_permanent', 1)->whereOr(function ($query) {
                    $now = time();
                    $query->where('start_time', '<=', $now)
                        ->where('end_time', '>=', $now);
                });
            });
        }
        return $query;
    }

    public function searchGiftTimeAttr($query, $value = true)
    {
        if ($value !== '') {
            $time = explode('-', $value);
            $start_time = strtotime($time[0]);
            $end_time = strtotime($time[1]);
            $query->where('start_time', '<=', $start_time)
                ->where('end_time', '>=', $end_time);
        }
        return $query;
    }

    /**
     * 推送渠道，多个用逗号分隔：1-短信，2-公众号，3-弹框广告',
     *
     * @param $query
     * @param $value
     * @return mixed
     * User: liusl
     * DateTime: 2025/8/12 15:34
     */
    public function searchPushChannelsAttr($query, $value = true)
    {
        if ($value !== '') {
            $query->whereLike('push_channel', '%' . $value . '%');
        }
        return $query;
    }
}
