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

namespace app\model\activity\card;

use crmeb\traits\ModelTrait;
use crmeb\basic\BaseModel;

/**
 * 卡次批次模型
 * Class CardBatch
 * @package app\model\activity\card
 */
class CardBatch extends BaseModel
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
    protected $name = 'card_batch';


    //查询器时间戳转时间
    public function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    /**
     * 搜索器 - 根据卡次名称搜索
     * @param $query
     * @param $value
     */
    public function searchNameAttr($query, $value)
    {
        if ($value) {
            $query->where('name', 'like', '%' . $value . '%');
        }
    }

    //id搜索器
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
     * 搜索器 - 根据卡号前缀搜索
     * @param $query
     * @param $value
     */
    public function searchCardPrefixAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('card_prefix', $value);
        }
    }

    public function searchPrefixAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('prefix', $value);
        }
    }

    /**
     * 搜索器 - 根据卡号后缀搜索
     * @param $query
     * @param $value
     */
    public function searchCardSuffixAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('card_suffix', $value);
        }
    }

    /**
     * 搜索器 - 根据删除状态搜索
     * @param $query
     * @param $value
     */
    public function searchIsDelAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('is_del', $value);
        }
    }
}
