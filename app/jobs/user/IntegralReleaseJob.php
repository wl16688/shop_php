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
namespace app\jobs\user;

use crmeb\basic\BaseJobs;
use app\services\user\UserServices;
use app\services\order\UserCoinBillServices;
use crmeb\traits\QueueTrait;
use crmeb\services\CacheService;

/**
 * 被锁定积分释放定时任务
 * Class IntegralReleaseJob
 * @package app\jobs\user
 */
class IntegralReleaseJob extends BaseJobs
{
    use QueueTrait;

    /**
     * @return string
     */
    protected static function queueName()
    {
        return 'CRMEB_PRO_TASK';
    }

    public function doJob()
    {
        try {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            
            /** @var UserCoinBillServices $userCoinBillServices */
            $userCoinBillServices = app()->make(UserCoinBillServices::class);

            // 获取积分释放比例配置
            $releaseRate = CacheService::redisHandler()->get('config:irr');
            if ($releaseRate === false || !is_numeric($releaseRate)) {
                $releaseRate = 0.001;// 默认0.1%
            } else {
                $releaseRate = floatval($releaseRate);
            }
            
            // 获取所有有锁定积分的用户
            $users = $userServices->getUsersWithLockedIntegral();
            
            $releaseCount = 0;
            $totalReleaseAmount = 0;

            foreach ($users as $user) {
                $lockedIntegral = $user['integral_lock'] ?? 0;
                if ($lockedIntegral <= 0) {
                    continue;
                }

                // 计算本次释放的积分数量（四舍五入确保为整数）
                $releaseAmount = round($lockedIntegral * $releaseRate);
                if ($releaseAmount <= 0) {
                    continue;
                }

                // 确保不超过锁定积分总数
                if ($releaseAmount > $lockedIntegral) {
                    $releaseAmount = floor($lockedIntegral);
                }

                // 执行积分释放
                $result = $userServices->releaseLockedIntegral($user['uid'], $releaseAmount);
                
                if ($result) {
                    // 获取释放后的用户最新积分余额
                    $updatedUser = $userServices->getUserInfo($user['uid']);
                    $newBalance = $updatedUser['integral'] ?? 0;
                    
                    // 记录积分释放明细
                    $userCoinBillServices->integralBill(
                        $user['uid'],
                        $releaseAmount,
                        $newBalance,
                        '系统自动释放锁定积分',
                        "系统自动释放锁定积分，释放比例：" . ($releaseRate * 100) . "%",
                        'auto_release_' . date('Ymd') . '_' . $user['uid'],
                        1
                    );

                    $releaseCount++;
                    $totalReleaseAmount += $releaseAmount;
                }
            }

            // 记录日志
            response_log_write([
                'message' => "积分释放任务执行完成，释放用户数：{$releaseCount}，总释放积分：{$totalReleaseAmount}，释放比例：" . ($releaseRate * 100) . "%",
                'type' => 'integral_release',
                'data' => [
                    'release_count' => $releaseCount,
                    'total_release_amount' => $totalReleaseAmount,
                    'release_rate' => $releaseRate,
                    'execute_time' => date('Y-m-d H:i:s')
                ]
            ]);

        } catch (\Throwable $e) {
            response_log_write([
                'message' => '积分释放任务失败，失败原因:[' . class_basename($this) . ']' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'type' => 'integral_release_error'
            ]);
        }
    }
}