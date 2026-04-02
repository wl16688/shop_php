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
declare (strict_types=1);

namespace app\model\user;

use crmeb\basic\BaseModel;
use crmeb\traits\ModelTrait;

/**
 * 用户扩展数据表模型
 * Class UserExtend
 * @package app\model\user
 */
class UserExtend extends BaseModel
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
    protected $name = 'user_extend';

    /**
     * 关联用户表
     * @return \think\model\relation\BelongsTo|\think\model\relation\HasOne
     */
    public function user()
    {
        return $this->hasOne(User::class, 'uid', 'uid');
    }

    /**
     * uid搜索器
     * @param $query
     * @param $value
     */
    public function searchUidAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('uid', $value);
        } else {
            $query->where('uid', $value);
        }
    }

    /**
     * 字段名搜索器
     * @param $query
     * @param $value
     */
    public function searchFieldNameAttr($query, $value)
    {
        if (is_array($value)) {
            $query->whereIn('field_name', $value);
        } else {
            $query->where('field_name', $value);
        }
    }
}
