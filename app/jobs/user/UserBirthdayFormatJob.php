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

use app\services\user\UserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 用户生日格式转换队列任务
 * Class UserBirthdayFormatJob
 * @package app\jobs\user
 */
class UserBirthdayFormatJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 处理用户生日格式转换
     * @param array $userIds 用户ID数组，如果为空则处理所有用户
     * @param int $batchSize 批处理大小，默认100
     * @return bool
     */
    public function doJob($userIds = [], $batchSize = 100)
    {
        try {
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);

            Log::info('开始处理用户生日格式转换任务', [
                'user_ids' => $userIds,
                'batch_size' => $batchSize
            ]);

            // 如果没有指定用户ID，则查询所有需要处理的用户
            if (empty($userIds)) {
                $userIds = $this->getUsersWithTimestampBirthday($userServices);
            }

            if (empty($userIds)) {
                Log::info('没有找到需要处理的用户生日数据');
                return true;
            }

            // 分批处理用户数据
            $chunks = array_chunk($userIds, $batchSize);
            $totalChunks = count($chunks);

            Log::info('开始分批处理用户生日数据', [
                'total_users' => count($userIds),
                'total_chunks' => $totalChunks,
                'batch_size' => $batchSize
            ]);

            foreach ($chunks as $index => $chunk) {
                $this->processBirthdayBatch($userServices, $chunk, $index + 1, $totalChunks);
            }

            Log::info('用户生日格式转换任务完成', [
                'processed_users' => count($userIds)
            ]);

            return true;

        } catch (\Throwable $e) {
            Log::error('用户生日格式转换任务执行失败', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * 获取生日字段为时间戳格式的用户ID
     * @param UserServices $userServices
     * @return array
     */
    private function getUsersWithTimestampBirthday($userServices)
    {
        try {
            // 查询birthday字段不为空且不为0的用户
            // 时间戳通常是10位数字，大于946684800（2000-01-01的时间戳）
            $users = $userServices->search([])
                ->where('birthday', '<>', 0)
                ->where('is_del', 0)
                ->column('uid');

            Log::info('查询到需要处理的用户', [
                'count' => count($users)
            ]);

            return $users;

        } catch (\Throwable $e) {
            Log::error('查询用户生日数据失败', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return [];
        }
    }

    /**
     * 批量处理用户生日数据
     * @param UserServices $userServices
     * @param array $userIds
     * @param int $currentBatch
     * @param int $totalBatches
     * @return void
     */
    private function processBirthdayBatch($userServices, $userIds, $currentBatch, $totalBatches)
    {
        try {
            Log::info("处理第 {$currentBatch}/{$totalBatches} 批用户数据", [
                'user_ids' => $userIds,
                'count' => count($userIds)
            ]);

            // 获取用户数据
            $users = $userServices->search([])
                ->whereIn('uid', $userIds)
                ->where('is_del', 0)
                ->field('uid,birthday')
                ->select()
                ->toArray();

            $updateCount = 0;
            $errorCount = 0;

            foreach ($users as $user) {
                try {
                    $uid = $user['uid'];
                    $birthday = $user['birthday'];

                    // 检查是否为时间戳格式（10位数字）
                    if (!$this->isTimestamp($birthday)) {
                        continue;
                    }

                    // 将时间戳转换为日期格式 Y-m-d
                    $birthdayDate = date('Y-m-d', $birthday);

                    // 更新用户生日数据
                    $result = $userServices->search([])
                        ->where('uid', $uid)
                        ->update(['birthday' => $birthdayDate]);

                    if ($result) {
                        $updateCount++;
                        Log::debug("用户 {$uid} 生日格式转换成功", [
                            'old_birthday' => $birthday,
                            'new_birthday' => $birthdayDate,
                            'timestamp' => strtotime($birthdayDate)
                        ]);
                    }

                } catch (\Throwable $e) {
                    $errorCount++;
                    Log::error("处理用户 {$uid} 生日数据失败", [
                        'uid' => $uid,
                        'birthday' => $birthday,
                        'message' => $e->getMessage()
                    ]);
                }
            }

            Log::info("第 {$currentBatch} 批处理完成", [
                'total_users' => count($users),
                'updated_count' => $updateCount,
                'error_count' => $errorCount
            ]);

        } catch (\Throwable $e) {
            Log::error("批量处理用户生日数据失败", [
                'batch' => $currentBatch,
                'user_ids' => $userIds,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * 检查是否为时间戳格式
     * @param mixed $value
     * @return bool
     */
    private function isTimestamp($value)
    {
        // 检查是否为数字且在合理的时间戳范围内
        if (!is_numeric($value)) {
            return false;
        }

        $timestamp = (int)$value;

        // 时间戳应该在1970年到2100年之间
        return $timestamp > 0 && $timestamp > 946684800 && $timestamp < 4102444800;
    }

    /**
     * 手动触发处理指定用户的生日格式转换
     * @param array $userIds 指定的用户ID数组
     * @return bool
     */
    public function processSpecificUsers($userIds)
    {
        if (empty($userIds)) {
            return false;
        }

        return $this->doJob($userIds);
    }
}
