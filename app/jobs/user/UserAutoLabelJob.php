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

use app\services\order\StoreOrderServices;
use app\services\product\product\StoreProductLogServices;
use app\services\product\product\StoreProductRelationServices;
use app\services\user\label\UserLabelServices;
use app\services\user\label\UserLabelExtendServices;
use app\services\user\label\UserLabelRelationServices;
use app\services\user\UserBillServices;
use app\services\user\UserMoneyServices;
use app\services\user\UserServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 用户自动打标签队列任务
 * Class UserAutoLabelJob
 * @package app\jobs\user
 */
class UserAutoLabelJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 处理自动打标签
     * @param array $data [uid|uids, trigger_type, trigger_data, ids]
     * @return bool
     */
    public function doJob($uids, $trigger_type = '', $trigger_data = [], $ids = [])
    {
        Log::info('开始批量处理自动标签:' . var_export([
                'user_ids' => $uids,
                'trigger_type' => $trigger_type,
                'label_ids' => $ids
            ], true));
        if (!$uids) {
            return true;
        }

        // 兼容单个用户和批量用户处理
        if (!is_array($uids)) {
            $uids = [$uids];
        }
        return $this->batchAutoLabel($uids, $trigger_type, $trigger_data, $ids);
    }

    /**
     * 批量处理自动打标签
     * @param array $uids 用户ID数组
     * @param string $trigger_type 触发类型
     * @param array $trigger_data 触发数据
     * @param array|null $ids 指定的标签ID
     * @return bool
     */
    public function batchAutoLabel(array $uids, string $trigger_type, array $trigger_data = [], $ids = null): bool
    {
        Log::info('开始批量处理自动标签2:' . var_export([
                'user_count' => count($uids),
                'trigger_type' => $trigger_type,
                'label_ids' => $ids
            ], true));
        if (empty($uids)) {
            return true;
        }

        try {
            Log::info('开始批量处理自动标签:' . var_export([
                    'user_count' => count($uids),
                    'trigger_type' => $trigger_type,
                    'label_ids' => $ids
                ], true));

            /** @var UserLabelServices $labelServices */
            $labelServices = app()->make(UserLabelServices::class);

            /** @var UserLabelExtendServices $extendServices */
            $extendServices = app()->make(UserLabelExtendServices::class);

            /** @var UserLabelRelationServices $relationServices */
            $relationServices = app()->make(UserLabelRelationServices::class);

            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);

            $where = [
                'label_type' => 2, // 自动标签
                'status' => 1
            ];
            if ($ids) {
                $where['id'] = $ids;
            }

            // 获取所有自动标签
            $autoLabels = $labelServices->search($where)->select()->toArray();

            if (!$autoLabels) {
                Log::info('没有找到自动标签:' . var_export(['label_ids' => $ids], true));
                return true;
            }

            // 批量获取用户当前标签关系，减少数据库查询
            $userLabelsMap = $relationServices->getUserLabelsMap($uids);

            $processedCount = 0;
            $addedLabels = [];
            $removedLabels = [];

            foreach ($uids as $uid) {
                foreach ($autoLabels as $label) {
                    // 检查标签是否需要处理当前触发类型
//                    if (!$this->shouldProcessLabel($label, $trigger_type)) {
//                        continue;
//                    }

                    // 获取标签的扩展规则
                    $extendRules = $extendServices->getLabelExtendList(['label_id' => $label['id']]);
                    Log::info('获取标签的扩展规则:' . var_export(['extendRules' => $extendRules], true));

                    if (!$extendRules) {
                        continue;
                    }

                    // 检查用户是否满足标签条件
                    $isMatch = $this->checkLabelConditions($uid, $label, $extendRules, $userServices, $trigger_data);
                    Log::info('检查用户是否满足标签条件:' . var_export([
                            'isMatch' => $isMatch,
                            'label_id' => $label['id'],
                            'label_name' => $label['label_name'],
                            'user_id' => $uid
                        ], true));

                    // 获取用户当前是否有此标签
                    $userLabels = $userLabelsMap[$uid] ?? [];
                    $hasLabel = in_array($label['id'], $userLabels);

                    if ($isMatch && !$hasLabel) {
                        // 满足条件且没有标签，记录需要添加的标签
                        $addedLabels[] = [
                            'uid' => $uid,
                            'label_id' => $label['id'],
                            'label_name' => $label['label_name']
                        ];
                    } elseif (!$isMatch && $hasLabel) {
                        // 不满足条件但有标签，记录需要移除的标签
                        $removedLabels[] = [
                            'uid' => $uid,
                            'label_id' => $label['id'],
                            'label_name' => $label['label_name']
                        ];
                    }
                }
                $processedCount++;
            }

            // 批量处理标签添加
            if (!empty($addedLabels)) {
                $this->batchAddLabels($addedLabels, $relationServices);
            }

            // 批量处理标签移除
            if (!empty($removedLabels)) {
                $this->batchRemoveLabels($removedLabels, $relationServices);
            }

            Log::info('批量自动标签处理完成:' . var_export([
                    'processed_users' => $processedCount,
                    'added_labels' => count($addedLabels),
                    'removed_labels' => count($removedLabels),
                    'trigger_type' => $trigger_type
                ], true));

        } catch (\Throwable $e) {
            Log::error('批量自动打标签失败:' . var_export([
                    'user_count' => count($uids),
                    'trigger_type' => $trigger_type,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], true));
            return false;
        }

        return true;
    }

    /**
     * 批量添加标签
     * @param array $addedLabels
     * @param UserLabelRelationServices $relationServices
     * @return void
     */
    protected function batchAddLabels(array $addedLabels, $relationServices): void
    {
        try {
            // 按用户分组标签
            $userLabelGroups = [];
            foreach ($addedLabels as $item) {
                $userLabelGroups[$item['uid']][] = $item['label_id'];
            }

            // 批量添加标签
            foreach ($userLabelGroups as $uid => $labelIds) {
                $relationServices->setUserLable($uid, $labelIds);
            }

            Log::info('批量添加标签完成:' . var_export([
                    'user_count' => count($userLabelGroups),
                    'total_labels' => count($addedLabels)
                ], true));
        } catch (\Throwable $e) {
            Log::error('批量添加标签失败:' . var_export([
                    'error' => $e->getMessage(),
                    'labels_count' => count($addedLabels)
                ], true));
        }
    }

    /**
     * 批量移除标签
     * @param array $removedLabels
     * @param UserLabelRelationServices $relationServices
     * @return void
     */
    protected function batchRemoveLabels(array $removedLabels, $relationServices): void
    {
        try {
            // 按用户分组标签
            $userLabelGroups = [];
            foreach ($removedLabels as $item) {
                $userLabelGroups[$item['uid']][] = $item['label_id'];
            }

            // 批量移除标签
            foreach ($userLabelGroups as $uid => $labelIds) {
                $relationServices->unUserLabel($uid, $labelIds);
            }

            Log::info('批量移除标签完成:' . var_export([
                    'user_count' => count($userLabelGroups),
                    'total_labels' => count($removedLabels)
                ], true));
        } catch (\Throwable $e) {
            Log::error('批量移除标签失败:' . var_export([
                    'error' => $e->getMessage(),
                    'labels_count' => count($removedLabels)
                ], true));
        }
    }

    /**
     * 检查标签是否需要处理当前触发类型
     * @param array $label
     * @param string $trigger_type
     * @return bool
     */
    private function shouldProcessLabel(array $label, string $trigger_type): bool
    {
        switch ($trigger_type) {
            case 'product': // 商品相关触发
                return $label['is_product'] == 1;
            case 'property': // 资产相关触发
                return $label['is_property'] == 1;
            case 'trade': // 交易相关触发
                return $label['is_trade'] == 1;
            case 'customer': // 客户相关触发
                return $label['is_customer'] == 1;
            case 'all': // 处理所有类型
                return true;
            default:
                return true; // 默认处理所有类型
        }
    }

    /**
     * 检查用户是否满足标签条件
     * @param int $uid
     * @param array $label
     * @param array $extendRules
     * @param UserServices $userServices
     * @param array $trigger_data 触发数据
     * @return bool
     */
    private function checkLabelConditions(int $uid, array $label, array $extendRules, UserServices $userServices, array $trigger_data = []): bool
    {
        // 初始化各类条件检查结果，默认只初始化需要检查的类型
        $is_product = $label['is_product'] == 1 ? true : null;
        $is_property = $label['is_property'] == 1 ? true : null;
        $is_trade = $label['is_trade'] == 1 ? true : null;
        $is_customer = $label['is_customer'] == 1 ? true : null;

        foreach ($extendRules as $rule) {
            switch ($rule['rule_type']) {
                case 1: // 商品条件
                    if ($label['is_product'] == 1) {
                        // 只有当标签启用商品条件时才进行检查
                        if (!$this->checkProductCondition($uid, $rule, $trigger_data)) {
                            $is_product = false;
                        }
                    }
                    break;
                case 2: // 资产条件
                    if ($label['is_property'] == 1) {
                        // 只有当标签启用资产条件时才进行检查
                        if (!$this->checkPropertyCondition($uid, $rule, $trigger_data)) {
                            $is_property = false;
                        }
                    }
                    break;
                case 3: // 交易条件
                    if ($label['is_trade'] == 1) {
                        // 只有当标签启用交易条件时才进行检查
                        if (!$this->checkTradeCondition($uid, $rule, $trigger_data)) {
                            $is_trade = false;
                        }
                    }
                    break;
                case 4: // 客户条件
                    if ($label['is_customer'] == 1) {
                        // 只有当标签启用客户条件时才进行检查
                        if (!$this->checkCustomerCondition($uid, $rule, $userServices, $trigger_data)) {
                            $is_customer = false;
                        }
                    }
                    break;
            }
        }

        // 构造用于日志记录的实际检查结果
        $logData = [
            'uid' => $uid,
            'is_product' => $is_product,
            'is_property' => $is_property,
            'is_trade' => $is_trade,
            'is_customer' => $is_customer
        ];
        Log::info('标签条件检查结果:' . var_export($logData, true));

        // 收集实际参与判断的条件结果
        $conditions = [];
        if ($label['is_product'] == 1) {
            $conditions[] = $is_product !== false; // 如果未检查则默认为true
        }
        if ($label['is_property'] == 1) {
            $conditions[] = $is_property !== false;
        }
        if ($label['is_trade'] == 1) {
            $conditions[] = $is_trade !== false;
        }
        if ($label['is_customer'] == 1) {
            $conditions[] = $is_customer !== false;
        }

        // 根据条件类型判断是否满足
        if ($label['is_condition'] == 1) {
            // 满足任一条件
            return in_array(true, $conditions, true);
        } else {
            // 满足全部条件
            return !in_array(false, $conditions, true);
        }
    }

    /**
     * 检查商品条件
     * @param int $uid
     * @param array $rule
     * @param array $trigger_data 触发数据，可能包含商品ID、订单信息等
     * @return bool
     */
    private function checkProductCondition(int $uid, array $rule, array $trigger_data = []): bool
    {
        $storeProductRelationServices = app()->make(StoreProductRelationServices::class);
        // TODO: 实现商品条件检查逻辑
        //用户购买过的商品 id 总和
        $pids = app()->make(StoreProductLogServices::class)->search(['uid' => $uid, 'type' => 'pay'])->column('product_id');
        //去重
        $pids = array_unique($pids);
        $productIds = is_array($rule['product_ids']) ? $rule['product_ids'] : explode(',', $rule['product_ids']);

        //记录日志
        switch ($rule['specify_dimension']) {
            // 1=>商品,2=>分类,3=>标签
            case 1:
                Log::info('商品条件(购买商品)检查日志:' . var_export(['uid' => $uid, 'pids' => $pids, 'productIds' => $productIds, 'rule' => $rule], true));
                //判断$pids全部包含$product_ids
                return $pids && count(array_diff($productIds, $pids)) == 0;
            case 2:
                //分类
                $cateIds = $storeProductRelationServices->search(['product_id' => $pids, 'type' => 1])->column('relation_id');
                $cateIds = array_unique($cateIds);
                Log::info('商品条件(购买商品分类)检查日志:' . var_export([
                        'uid' => $uid,
                        'label_id'=>$rule['label_id'],
                        'productIds'=>$productIds,
                        'pids' => $pids,
                        'cateIds' => $cateIds,
                        'rule' => $rule
                    ], true));
                return $pids && count(array_diff($productIds, $cateIds)) == 0;
            case 3:
                //标签
                $cateIds = $storeProductRelationServices->search(['product_id' => $pids, 'type' => 3])->column('relation_id');
                $cateIds = array_unique($cateIds);
                Log::info('商品条件(购买商品标签)检查日志:' . var_export([
                        'uid' => $uid,
                        'label_id'=>$rule['label_id'],
                        'productIds'=>$productIds,
                        'pids' => $pids,
                        'cateIds' => $cateIds,
                        'rule' => $rule
                    ], true));
                return $pids && count(array_diff($productIds, $cateIds)) == 0;
            default :
                return false;
        }
    }

    /**
     * 检查资产条件
     * @param int $uid
     * @param array $rule
     * @param array $trigger_data 触发数据，可能包含充值金额、余额变动等
     * @return bool
     */
    private function checkPropertyCondition(int $uid, array $rule, array $trigger_data = []): bool
    {
        //数值
        $amount_value_min = $rule['amount_value_min'];
        $amount_value_max = $rule['amount_value_max'];
        //次数
        $operation_times_min = $rule['operation_times_min'];
        $operation_times_max = $rule['operation_times_max'];
        $userBillServices = app()->make(UserBillServices::class);
        $userMoneyServices = app()->make(UserMoneyServices::class);
        // TODO: 实现资产条件检查逻辑

        if ($rule['sub_type'] == 1) {
            //积分
            $integral = $userBillServices->search(['uid' => $uid, 'category' => 'integral', 'type' => 'deduction'])->sum('number');
            Log::info('资产条件(积分)检查日志:' . var_export(['uid' => $uid, 'integral' => $integral, 'rule' => $rule], true));
            return $integral >= $amount_value_min && $integral <= $amount_value_max;
        } else {
            //余额充值消耗次数和金额
            $where = ['uid' => $uid, 'type' => ['pay_product', 'recharge']];
            $list = $userMoneyServices->search($where)->select()->toArray();
            //充值金额,充值次数,消耗金额,消耗次数
            $recharge_amount = $recharge_times = $consume_amount = $consume_times = 0;
            foreach ($list as $item) {
                if ($item['type'] == 'recharge') {
                    $recharge_amount += $item['number'];
                    $recharge_times++;
                } else {
                    $consume_amount += $item['number'];
                    $consume_times++;
                }
            }
            Log::info('资产条件(余额)检查日志:' . var_export([
                    'uid' => $uid,
                    'recharge_amount' => $recharge_amount,
                    'recharge_times' => $recharge_times,
                    'consume_amount' => $consume_amount,
                    'consume_times' => $consume_times,
                    'rule' => $rule
                ], true));
            //余额
            if ($rule['balance_type'] == 1) {
                //充值
                if ($rule['operation_type'] == 1) {
                    //次数
                    return $recharge_times >= $operation_times_min && $recharge_times <= $operation_times_max;
                } else {
                    //金额
                    return $recharge_amount >= $amount_value_min && $recharge_amount <= $amount_value_max;
                }
            } else {
                //消耗
                if ($rule['operation_type'] == 1) {
                    //次数
                    return $consume_times >= $operation_times_min && $consume_times <= $operation_times_max;
                } else {
                    //金额
                    return $consume_amount >= $amount_value_min && $consume_amount <= $amount_value_max;
                }
            }

        }
    }

    /**
     * 检查交易条件
     * @param int $uid
     * @param array $rule
     * @param array $trigger_data 触发数据，可能包含订单金额、支付方式等
     * @return bool
     */
    private function checkTradeCondition(int $uid, array $rule, array $trigger_data = []): bool
    {
        //数值
        $amount_value_min = $rule['amount_value_min'];
        $amount_value_max = $rule['amount_value_max'];
        //次数
        $operation_times_min = $rule['operation_times_min'];
        $operation_times_max = $rule['operation_times_max'];
        $storeOrderServices = app()->make(StoreOrderServices::class);
        $where = ['pid' => 0, 'uid' => $uid, 'paid' => 1, 'refund_status' => [0, 3], 'is_del' => 0, 'is_system_del' => 0];

        if ($rule['operation_type'] == 1) {
            $count = $storeOrderServices->count($where);
            Log::info('交易条件(订单次数)检查日志:' . var_export([
                    'uid' => $uid,
                    'count' => $count,
                    'rule' => $rule
                ], true));
            return $count >= $operation_times_min && $count <= $operation_times_max;
        } else {
            $pay_price = $storeOrderServices->together($where, 'pay_price');
            Log::info('交易条件(订单金额)检查日志:' . var_export([
                    'uid' => $uid,
                    'pay_price' => $pay_price,
                    'rule' => $rule
                ], true));
            return $pay_price >= $amount_value_min && $pay_price <= $amount_value_max;
        }
    }

    /**
     * 检查客户条件
     * @param int $uid
     * @param array $rule
     * @param UserServices $userServices
     * @param array $trigger_data 触发数据，可能包含用户行为信息等
     * @return bool
     */
    private function checkCustomerCondition(int $uid, array $rule, UserServices $userServices, array $trigger_data = []): bool
    {
        try {
            $userInfo = $userServices->getUserInfo($uid);
            if (!$userInfo) {
                return false;
            }
            switch ($rule['customer_identity']) {
                case 1: // 注册时间
                    $registerTime = $userInfo['add_time'];
                    $res =  $this->checkTimeRange($registerTime, $rule['customer_time_start'], $rule['customer_time_end']);
                    Log::info('检查注册时间条件:' . var_export([
                        'registerTime' => $registerTime,
                        'rule' => [
                            'customer_time_start' => $rule['customer_time_start'],
                            'customer_time_end' => $rule['customer_time_end']
                        ],
                        'res' => $res
                        ],true));
                    return $res;
                case 2: // 访问时间
                    $lastTime = $userInfo['last_time'];
                    $res =  $this->checkTimeRange($lastTime, $rule['customer_time_start'], $rule['customer_time_end']);
                    Log::info('检查访问时间条件:' . var_export([
                        'lastTime' => $lastTime,
                        'rule' => [
                            'customer_time_start' => $rule['customer_time_start'],
                            'customer_time_end' => $rule['customer_time_end']
                        ],
                        'res' => $res
                        ],true));
                    return $res;
                case 3: // 用户等级
                    $userLevel = $userInfo['level'] ?? 0;
                    $res =  $userLevel == $rule['customer_num'];
                    Log::info('检查用户等级条件:' . var_export([
                        'userLevel' => $userLevel,
                        'rule' => [
                            'customer_num' => $rule['customer_num']
                        ],
                        'res' => $res
                        ],true));
                    return $res;
                case 4: // 客户身份
                    //$checkUserTag是数组,包含 //1等级会员,2付费会员,3推广员,4采购商
                    $checkUserTag = $userServices->checkUserTag($uid);
                    $res = in_array($rule['customer_num'], $checkUserTag);
                    Log::info('检查客户身份条件:' . var_export([
                        'checkUserTag' => $checkUserTag,
                        'rule' => [
                            'customer_num' => $rule['customer_num']
                        ],
                        'res' => $res
                        ],true));
                    return $res;
                default:
                    return false;
            }
        } catch (\Throwable $e) {
            Log::error('检查客户条件失败:' . var_export([
                    'uid' => $uid,
                    'rule' => $rule,
                    'error' => $e->getMessage()
                ], true));
            return false;
        }
    }

    /**
     * 检查时间范围
     * @param int $time
     * @param int $startTime
     * @param int $endTime
     * @return bool
     */
    private function checkTimeRange(int $time, int $startTime, int $endTime): bool
    {
        if ($startTime && $time < $startTime) {
            return false;
        }
        if ($endTime && $time > $endTime) {
            return false;
        }
        return true;
    }

    /**
     * 检查客户身份
     * @param array $userInfo
     * @param int $identityType
     * @return bool
     */
    private function checkCustomerIdentity(array $userInfo, int $identityType): bool
    {
        switch ($identityType) {
            case 1: // 等级会员
                return isset($userInfo['level']) && $userInfo['level'] > 0;
            case 2: // 付费会员
                return isset($userInfo['is_money_level']) && $userInfo['is_money_level'] > 0;
            case 3: // 推广员
                return isset($userInfo['is_promoter']) && $userInfo['is_promoter'] == 1;
            case 4: // 采购商
                // TODO: 根据实际业务逻辑判断采购商身份
                return false;
            default:
                return false;
        }
    }
}
