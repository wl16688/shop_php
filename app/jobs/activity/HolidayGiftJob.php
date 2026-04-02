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

namespace app\jobs\activity;

use app\services\activity\holiday\HolidayGiftPushServices;
use app\services\activity\holiday\HolidayGiftRecordServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 节日有礼用户权益处理队列
 * Class HolidayGiftJob
 * @package app\jobs\activity
 */
class HolidayGiftJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 处理单个用户的节日有礼权益
     * @param int $uid 用户ID
     * @param array $giftInfo 活动信息
     * @return bool
     */
    public function doJob(int $uid, array $giftInfo)
    {
        try {
            /** @var HolidayGiftPushServices $holidayGiftPushServices */
            $holidayGiftPushServices = app()->make(HolidayGiftPushServices::class);
            $holidayGiftRecordServices = app()->make(HolidayGiftRecordServices::class);

            //检查是否需要提前推送或当天推送
            if (!$holidayGiftPushServices->checkAdvancePush($giftInfo, true, $uid)) {
                return true;
            }
            // 检查用户是否符合活动条件
            if (!$holidayGiftPushServices->checkUserCondition($uid, $giftInfo)) {
                return true;
            }

            // 检查是否可以推送（频次限制）
            if ($holidayGiftRecordServices->checkUserCanPush($uid, $giftInfo['id'])) {
                return true;
            }

            // 执行赠送权益
            $grantResults = $holidayGiftPushServices->grantUserBenefits($uid, $giftInfo);

            // 记录推送记录（无论成功失败都要记录）
            $holidayGiftPushServices->recordPushLog($uid, $giftInfo);

            if ($grantResults) {
                Log::info("用户{$uid}节日有礼处理成功，活动ID：{$giftInfo['id']}");
            } else {
                Log::warning("用户{$uid}节日有礼权益赠送失败，活动ID：{$giftInfo['id']}，详情：" . json_encode($grantResults['details']));
            }

            return true;
        } catch (\Exception $e) {
            Log::error("用户{$uid}节日有礼处理失败: " . $e->getMessage());
            return false;
        }
    }
}
