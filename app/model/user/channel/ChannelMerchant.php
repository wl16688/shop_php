<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\model\user\channel;

use app\model\user\User;
use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 采购商模型
 * Class ChannelMerchant
 * @package app\model\user\channel
 */
class ChannelMerchant extends BaseModel
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
    protected $name = 'channel_merchant';

    /**
     * 审核状态
     * @var array
     */
    public static $verifyStatusConfig = [
        0 => '待审核',
        1 => '审核通过',
        2 => '审核未通过'
    ];

    /**
     * 关联用户
     * @return \think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    /**
     * 审核状态获取器
     * @param $value
     * @return string
     */
    public function getVerifyStatusNameAttr($value, $data)
    {
        return self::$verifyStatusConfig[$data['verify_status']] ?? '';
    }

    /**
     * 地区获取器
     * @param $value
     * @return array
     */
    public function getProvinceAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    public function getProvinceIdsAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    /**
     * 地区修改器
     * @param $value
     * @return string
     */
    public function setProvinceAttr($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }

    public function setProvinceIdsAttr($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }

    /**
     * 资质照片获取器
     * @param $value
     * @return array
     */
    public function getCertificateAttr($value)
    {
        return $value ? explode(',', $value) : [];
    }

    /**
     * 资质照片修改器
     * @param $value
     * @return string
     */
    public function setCertificateAttr($value)
    {
        if (is_array($value)) {
            return implode(',', $value);
        }
        return $value;
    }

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 审核时间获取器
     * @param $value
     * @return false|string
     */
    public function getVerifyTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    /**
     * 采购商名称搜索器
     * @param Model $query
     * @param $value
     */
    public function searchChannelNameAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('channel_name', '%' . $value . '%');
        }
    }

    /**
     * 联系人搜索器
     * @param Model $query
     * @param $value
     */
    public function searchRealNameAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('real_name', '%' . $value . '%');
        }
    }

    public function searchUidAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('uid', $value);
        }
    }

    /**
     * 联系电话搜索器
     * @param Model $query
     * @param $value
     */
    public function searchPhoneAttr($query, $value)
    {
        if ($value !== '') {
            $query->whereLike('phone', '%' . $value . '%');
        }
    }

    /**
     * 审核状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchVerifyStatusAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('verify_status', $value);
            } else {
                $query->where('verify_status', $value);
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
     * 采购商身份搜索器
     * @param Model $query
     * @param $value
     */
    public function searchChannelTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('channel_type', $value);
        }
    }

    /**
     * 添加时间范围搜索器
     * @param Model $query
     * @param $value
     */
    public function searchAddTimeAttr($query, $value)
    {
        if ($value && is_array($value) && count($value) == 2) {
            $query->whereBetween('add_time', [strtotime($value[0]), strtotime($value[1])]);
        }
    }

    /**
     * 软删除搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }

    public function searchIsAdminAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_admin', $value);
        }
    }
}
