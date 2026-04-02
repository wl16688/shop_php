<?php

declare (strict_types=1);

namespace app\model\activity\holiday;

use app\model\user\User;
use crmeb\basic\BaseModel;
use think\Model;

/**
 * 节日有礼推送记录模型
 * Class HolidayGiftPush
 * @package app\model\activity
 */
class HolidayGiftPush extends BaseModel
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
    protected $name = 'holiday_gift_push';

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
     * 推送时间获取器
     * @param $value
     * @return string
     */
    public function getPushTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', (int)$value) : '';
    }

    /**
     * 推送类型获取器
     * @param $value
     * @return string
     */
    public function getPushTypeNameAttr($value, $data)
    {
        return HolidayGift::$pushChannelMap[$data['push_type']] ?? '';
    }

    /**
     * 状态获取器
     * @param $value
     * @return string
     */
    public function getStatusNameAttr($value, $data)
    {
        return $data['status'] ? '推送成功' : '推送失败';
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
}
