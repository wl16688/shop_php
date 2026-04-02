<?php
declare (strict_types=1);

namespace app\model\user\channel;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 采购商身份模型
 * Class ChannelMerchantIdentity
 * @package app\model\user\channel
 */
class ChannelMerchantIdentity extends BaseModel
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
    protected $name = 'channel_merchant_identity';

    /**
     * 添加时间获取器
     * @param $value
     * @return false|string
     */
    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function searchIdAttr($query, $value)
    {
        if ($value !== '') {
            if (is_array($value)) {
                $query->whereIn('id', $value);
            } else {
                $query->where('id', $value);
            }
        }
    }

    /**
     * 关键词搜索器
     * @param Model $query
     * @param $value
     */
    public function searchKeywordAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    /**
     * 显示状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsShowAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_show', $value);
        }
    }

    /**
     * 删除状态搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }

    /**
     * 等级搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLevelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('level', $value);
        }
    }

    /**
     * 折扣搜索器
     * @param Model $query
     * @param $value
     */
    public function searchDiscountAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('discount', $value);
        }
    }

}
