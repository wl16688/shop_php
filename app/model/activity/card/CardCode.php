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
use think\Model;

/**
 * 礼品卡模型
 * Class CardGift
 * @package appmodelactivitycard
 */
class CardCode extends BaseModel
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
    protected $name = 'card_code';

    public function setAddTimeAttr()
    {
        return time();
    }

    //查询器时间戳转时间
    public function getAddTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public function getActiveTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
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

    //uid搜索器
    public function searchUidAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('uid', $value);
        }
    }

    //type搜索器
    public function searchTypeAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('type', $value);
        }
    }

    //status搜索器
    public function searchStatusAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('status', $value);
        }
    }

    //card_id搜索器
    public function searchCardIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('card_id', $value);
        }
    }

    //batch_id
    public function searchBatchIdAttr($query, $value)
    {
        if ($value !== '') {
            $query->where('batch_id', $value);
        }
    }

    //active_time搜索器
    public function searchActiveTimeAttr($query, $value)
    {
        if ($value !== '') {
            $active_time = explode('-', $value);
            $active_time[0] = isset($active_time[0]) ? strtotime($active_time[0]) : 0;
            $active_time[1] = isset($active_time[1]) ? strtotime($active_time[1]) : 0;
            $query->whereBetween('active_time', [$active_time[0], $active_time[1]]);
        }
    }
}
