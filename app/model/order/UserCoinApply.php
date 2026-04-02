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
 * 用户通证申请模型
 * Class UserCoinApply
 * @package app\model\order
 */
class UserCoinApply extends BaseModel
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
    protected $name = 'user_coin_apply';

    protected $autoWriteTimestamp = 'int';

    protected $createTime = 'add_time';

    protected $updateTime = false;

    /**
     * 状态常量
     */
    const STATUS_PENDING = 0; // 未处理
    const STATUS_APPROVED = 1; // 通过
    const STATUS_REJECTED = 2; // 未通过

    /**
     * 金额修改器
     * @param $value
     * @return string
     */
    public function setAmountAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
    }

    /**
     * 添加时间修改器
     * @return int
     */
    public function setAddTimeAttr()
    {
        return time();
    }

    /**
     * 金额获取器
     * @param $value
     * @return string
     */
    public function getAmountAttr($value)
    {
        return number_format((float)$value, 2, '.', '');
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
     * 审核时间获取器
     * @param $value
     * @return false|string
     */
    public function getStatusTimeAttr($value)
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
     * 状态获取器
     * @param $value
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        switch ($status) {
            case self::STATUS_PENDING:
                return '待审核';
            case self::STATUS_APPROVED:
                return '已通过';
            case self::STATUS_REJECTED:
                return '已拒绝';
            default:
                return '未知状态';
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
     * 状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    /**
     * 手机号搜索器
     * @param Model $query
     * @param $value
     */
    public function searchPhoneAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('phone', 'like', '%' . $value . '%');
        }
    }

    /**
     * 删除状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        $query->where('is_del', $value);
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
     * 金额搜索器
     * @param Model $query
     * @param $value
     */
    public function searchAmountAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                // 支持金额范围搜索 [min, max]
                if (count($value) == 2) {
                    if (!empty($value[0])) {
                        $query->where('amount', '>=', $value[0]);
                    }
                    if (!empty($value[1])) {
                        $query->where('amount', '<=', $value[1]);
                    }
                }
            } else {
                // 精确匹配金额
                $query->where('amount', $value);
            }
        }
    }
}