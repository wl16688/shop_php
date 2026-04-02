<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2020 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\model\order;

use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 用户积分通证明细模型
 * Class UserCoinBill
 * @package app\model\order
 */
class UserCoinBill extends BaseModel
{
    use ModelTrait;

    /**
     * 数据表主键
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'user_coin_bill';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'add_time';

    protected $updateTime = false;

    /**
     * 收支类型常量
     */
    const PM_OUT = 0; // 支出
    const PM_IN = 1; // 收入

    /**
     * 明细类型常量
     */
    const TYPE_INTEGRAL = 'integral'; // 积分
    const TYPE_COIN = 'coin'; // 通证

    /**
     * 添加时间修改器
     * @return int
     */
    public function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    public function getAddTimeAttr($value)
    {
        if (!empty($value)) {
            if (is_string($value)) {
                return $value;
            } elseif (is_int($value)) {
                return date('Y-m-d H:i:s', (int)$value);
            }
        }
        return '';
    }

    /**
     * 收支类型获取器
     * @param $value
     * @return string
     */
    public function getPmTextAttr($value, $data)
    {
        $pm = $data['pm'] ?? 0;
        return $pm == self::PM_IN ? '收入' : '支出';
    }

    /**
     * 明细类型获取器
     * @param $value
     * @return string
     */
    public function getTypeTextAttr($value, $data)
    {
        $type = $data['type'] ?? '';
        switch ($type) {
            case self::TYPE_INTEGRAL:
                return '积分';
            case self::TYPE_COIN:
                return '通证';
            default:
                return $type;
        }
    }

    /**
     * 关联用户
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid')->field(['uid', 'nickname', 'avatar', 'phone']);
    }

    /**
     * 关联交易对手用户
     * @return \think\model\relation\HasOne
     */
    public function orderToUser()
    {
        return $this->hasOne(User::class, 'uid', 'order_to')->field(['uid', 'nickname', 'avatar']);
    }

    /**
     * 用户uid搜索器
     * @param Model $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('uid', $value);
            } else {
                $query->where('uid', $value);
            }
        }
    }

    /**
     * 收支类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchPmAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('pm', $value);
        }
    }

    /**
     * 明细类型搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }

    /**
     * 关联id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLinkIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('link_id', $value);
        }
    }

    /**
     * 交易对手搜索器
     * @param Model $query
     * @param $value
     */
    public function searchOrderToAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('order_to', $value);
        }
    }

    /**
     * 时间范围搜索器
     * @param Model $query
     * @param $value
     */
    public function searchAddTimeAttr($query, $value)
    {
        if (is_array($value) && count($value) == 2) {
            $query->whereBetweenTime('add_time', $value[0], $value[1]);
        }
    }

    /**
     * 标题搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTitleAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('title', 'like', '%' . $value . '%');
        }
    }
}