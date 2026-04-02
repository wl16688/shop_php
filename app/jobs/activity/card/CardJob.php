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

namespace app\jobs\activity\card;


use app\services\activity\card\CardBatchServices;
use app\services\activity\card\CardCodeServices;
use app\services\activity\card\CardGiftServices;
use app\services\activity\coupon\StoreCouponIssueServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;

/**
 * 营销：礼品卡
 * Class CardJob
 * @package app\jobs\user
 */
class CardJob extends BaseJobs
{

    use QueueTrait;

    /**
     * 卡密分配数量矫正
     * @param $id
     * @return bool
     */
    public function allocationCode($id)
    {
        try {
            app()->make(CardBatchServices::class)->allocationCode($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '卡密分配数量统计失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }

    /**
     * 校验使用数量
     * @param $id
     * @return true
     * User: liusl
     * DateTime: 2025/5/24 10:57
     */
    public function allocationCardGift($id)
    {
        try {
            app()->make(CardGiftServices::class)->allocationCardGift($id);
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '卡密分配数量统计失败:' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
        return true;
    }
}
