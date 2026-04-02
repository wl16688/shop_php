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

namespace app\model\user\label;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;
use think\Model;

/**
 * 标签规则扩展表
 * Class UserLabelExtendRelation
 * @package app\model\user\label
 */
class UserLabelExtendRelation extends BaseModel
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
    protected $name = 'user_label_extend_relation';

    /**
     * type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('type', $value);
    }

    /**
     * left_id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLeftIdAttr($query, $value)
    {
        if ($value !== '') $query->where('left_id', $value);
    }

    /**
     * right_id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchRightIdAttr($query, $value)
    {
        if ($value !== '') $query->where('right_id', $value);
    }

    /**
     * ids搜索器
     * @param Model $query
     * @param $value
     */
    public function searchIdsAttr($query, $value)
    {
        if ($value) $query->whereIn('id', $value);
    }

    /**
     * left_ids搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLeftIdsAttr($query, $value)
    {
        if ($value) $query->whereIn('left_id', $value);
    }

    /**
     * right_ids搜索器
     * @param Model $query
     * @param $value
     */
    public function searchRightIdsAttr($query, $value)
    {
        if ($value) $query->whereIn('right_id', $value);
    }

    /**
     * 关联标签扩展
     * @return \think\model\relation\HasOne
     */
    public function labelExtend()
    {
        return $this->hasOne(UserLabelExtend::class, 'id', 'left_id');
    }
}