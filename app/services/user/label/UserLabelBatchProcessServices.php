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

namespace app\services\user\label;

use app\services\BaseServices;
use app\services\user\UserServices;
use app\jobs\user\UserAutoLabelJob;
use think\facade\Log;
use crmeb\traits\ServicesTrait;

/**
 * 用户标签批量处理服务
 * Class UserLabelBatchProcessServices
 * @package app\services\user\label
 */
class UserLabelBatchProcessServices extends BaseServices
{
    use ServicesTrait;

    /**
     * 批量大小
     */
    const BATCH_SIZE = 1000;

    /**
     * 处理标签变更后的用户批量检查
     * @param array $labelIds 标签ID数组
     * @param string $action 操作类型：create(新建) 或 update(修改)
     * @return bool
     */
    public function processLabelChange(array $labelIds, string $action = 'update')
    {
        try {
            Log::info('开始处理标签变更批量用户检查:' . var_export([
                    'label_ids' => $labelIds,
                    'action' => $action
                ], true));

            // 获取所有用户ID
            $userIds = $this->getAllUserIds();

            if (empty($userIds)) {
                Log::info('没有找到用户，跳过处理');
                return true;
            }

            // 初始化处理状态缓存
            $cacheKey = "label_batch_process_" . implode('_', $labelIds);
            $statusData = [
                'label_ids' => $labelIds,
                'status' => 'processing',
                'processed_count' => 0,
                'total_count' => count($userIds),
                'start_time' => date('Y-m-d H:i:s'),
                'end_time' => null,
                'message' => '正在处理中...'
            ];
            cache($cacheKey, $statusData, 3600); // 缓存1小时

            // 分批处理用户
            $batches = array_chunk($userIds, self::BATCH_SIZE);
            $totalBatches = count($batches);

            Log::info('开始分批处理用户:' . var_export([
                    'total_users' => count($userIds),
                    'batch_size' => self::BATCH_SIZE,
                    'total_batches' => $totalBatches
                ], true));

            $processedCount = 0;
            foreach ($batches as $index => $batchUserIds) {
                $this->processBatchUsers($batchUserIds, $labelIds, $action, $index + 1, $totalBatches);

                $processedCount += count($batchUserIds);

                // 更新处理状态
                $statusData['processed_count'] = $processedCount;
                $statusData['message'] = "已处理 {$processedCount}/{$statusData['total_count']} 个用户";
                cache($cacheKey, $statusData, 3600);
            }

            // 更新最终状态
            $statusData['status'] = 'completed';
            $statusData['end_time'] = date('Y-m-d H:i:s');
            $statusData['message'] = '处理完成';
            cache($cacheKey, $statusData, 3600);

            Log::info('标签变更批量用户检查处理完成:' . var_export([
                    'label_ids' => $labelIds,
                    'total_users' => count($userIds)
                ], true));
            return true;
        } catch (\Throwable $e) {
            // 更新错误状态
            $cacheKey = "label_batch_process_" . implode('_', $labelIds);
            $errorStatus = [
                'label_ids' => $labelIds,
                'status' => 'error',
                'processed_count' => 0,
                'total_count' => 0,
                'start_time' => date('Y-m-d H:i:s'),
                'end_time' => date('Y-m-d H:i:s'),
                'message' => '处理失败：' . $e->getMessage()
            ];
            cache($cacheKey, $errorStatus, 3600);

            Log::error('标签变更批量用户检查处理失败:' . var_export([
                    'label_ids' => $labelIds,
                    'action' => $action,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], true));
            return false;
        }
    }

    /**
     * 获取所有用户ID
     * @return array
     */
    protected function getAllUserIds(): array
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);

        // 获取所有启用的用户ID
        $where = [
            'status' => 1, // 启用状态
            'is_del' => 0  // 未删除
        ];

        // 分批获取用户ID，避免内存溢出
        $userIds = [];
        $page = 1;
        $limit = 5000; // 每次获取5000个用户ID

        do {
            $users = $userServices->getList($where, 'uid', $page, $limit);
            $batchIds = array_column($users, 'uid');
            $userIds = array_merge($userIds, $batchIds);
            $page++;
        } while (count($batchIds) === $limit);

        return array_unique($userIds);
    }

    /**
     * 处理批次用户
     * @param array $userIds 用户ID数组
     * @param array $labelIds 标签ID数组
     * @param string $action 操作类型
     * @param int $batchIndex 当前批次索引
     * @param int $totalBatches 总批次数
     */
    protected function processBatchUsers(array $userIds, array $labelIds, string $action, int $batchIndex, int $totalBatches)
    {
        try {
            Log::info('处理用户批次:' . var_export([
                    'batch_index' => $batchIndex,
                    'total_batches' => $totalBatches,
                    'user_ids' => $userIds,
                    'user_count' => count($userIds),
                    'label_ids' => $labelIds
                ], true));

            // 批量分发自动标签检查任务，每100个用户一个任务
            $batchSize = 100;
            $userBatches = array_chunk($userIds, $batchSize);

            foreach ($userBatches as $userBatch) {
                // 分发批量处理任务，传入用户数组而不是单个用户

                UserAutoLabelJob::dispatch([
                    $userBatch,
                    'label_change', // 触发类型：标签变更
                    [
                        'action' => $action,
                        'changed_labels' => $labelIds,
                        'timestamp' => time()
                    ],
                    $labelIds // 只检查变更的标签
                ]);
            }

            Log::info('批次用户处理完成:' . var_export([
                    'batch_index' => $batchIndex,
                    'user_batches' => count($userBatches),
                    'dispatched_jobs' => count($userBatches),
                    'total_users' => count($userIds)
                ], true));
        } catch (\Throwable $e) {
            Log::error('处理用户批次失败:' . var_export([
                    'batch_index' => $batchIndex,
                    'user_count' => count($userIds),
                    'error' => $e->getMessage()
                ], true));
        }
    }

    /**
     * 处理单个标签的用户检查
     * @param int $labelId 标签ID
     * @param string $action 操作类型
     * @return bool
     */
    public function processSingleLabel(int $labelId, string $action = 'update')
    {
        return $this->processLabelChange([$labelId], $action);
    }

    /**
     * 获取批量处理状态
     * @param int $labelId
     * @return array
     */
    public function getBatchProcessStatus(int $labelId): array
    {
        try {
            // 从缓存中获取处理状态
            $cacheKey = "label_batch_process_{$labelId}";
            $status = cache($cacheKey);

            if (!$status) {
                return [
                    'label_id' => $labelId,
                    'status' => 'not_started',
                    'processed_count' => 0,
                    'total_count' => 0,
                    'start_time' => null,
                    'end_time' => null,
                    'message' => '未找到处理记录'
                ];
            }

            return $status;
        } catch (\Exception $e) {
            Log::error('获取批量处理状态失败:' . var_export([
                    'label_id' => $labelId,
                    'error' => $e->getMessage()
                ], true));

            return [
                'label_id' => $labelId,
                'status' => 'error',
                'processed_count' => 0,
                'total_count' => 0,
                'start_time' => null,
                'end_time' => null,
                'message' => '获取状态失败：' . $e->getMessage()
            ];
        }
    }
}
