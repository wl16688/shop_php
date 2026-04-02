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
 * 标签规则关联表
 * Class UserLabelExtend
 * @package app\model\user\label
 */
class UserLabelExtend extends BaseModel
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
    protected $name = 'user_label_extend';

    /**
     * label_id搜索器
     * @param Model $query
     * @param $value
     */
    public function searchLabelIdAttr($query, $value)
    {
        if ($value !== '') $query->where('label_id', $value);
    }

    /**
     * rule_type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchRuleTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('rule_type', $value);
    }

    /**
     * sub_type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchSubTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('sub_type', $value);
    }

    /**
     * balance_type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchBalanceTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('balance_type', $value);
    }

    /**
     * operation_type搜索器
     * @param Model $query
     * @param $value
     */
    public function searchOperationTypeAttr($query, $value)
    {
        if ($value !== '') $query->where('operation_type', $value);
    }

    /**
     * time_dimension搜索器
     * @param Model $query
     * @param $value
     */
    public function searchTimeDimensionAttr($query, $value)
    {
        if ($value !== '') $query->where('time_dimension', $value);
    }

    /**
     * specify_dimension搜索器
     * @param Model $query
     * @param $value
     */
    public function searchSpecifyDimensionAttr($query, $value)
    {
        if ($value !== '') $query->where('specify_dimension', $value);
    }

    /**
     * customer_identity搜索器
     * @param Model $query
     * @param $value
     */
    public function searchCustomerIdentityAttr($query, $value)
    {
        if ($value !== '') $query->where('customer_identity', $value);
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
     * 关联标签
     * @return \think\model\relation\HasOne
     */
    public function label()
    {
        return $this->hasOne(UserLabel::class, 'id', 'label_id')->bind([
            'label_name' => 'label_name'
        ]);
    }
}