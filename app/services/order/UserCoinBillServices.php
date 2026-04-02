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

namespace app\services\order;

use app\services\BaseServices;
use app\dao\order\UserCoinBillDao;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\Exception;
use think\exception\ValidateException;
use think\facade\Cache;

/**
 * 用户积分通票明细服务
 * Class UserCoinBillServices
 * @package app\services\order
 * @mixin UserCoinBillDao
 */
class UserCoinBillServices extends BaseServices
{
    /**
     * @var UserServices
     */
    #[Inject]
    protected UserServices $userServices;

    /**
     * 明细类型模板
     * @var array
     */
    protected array $billTypes = [
        'integral' => [
            'gain' => '积分获得',
            'deduction' => '积分抵扣',
            'refund' => '积分退还',
            'transfer' => '积分转账',
            'exchange' => '积分兑换'
        ],
        'coin' => [
            'apply' => '通票申请',
            'reward' => '通票奖励',
            'transfer' => '通票转账',
            'exchange' => '通票兑换',
            'consume' => '通票消费'
        ]
    ];

    /**
     * @var UserCoinBillDao
     */
    #[Inject]
    protected UserCoinBillDao $dao;

    /**
     * 获取明细列表
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where, array $field = ['*']): array
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getList($where, implode(',', $field), $page, $limit);
        $count = $this->dao->getCount($where);
        
        return compact('data', 'count');
    }

    /**
     * 获取用户明细列表
     * @param int $uid
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserBillList(int $uid, array $where = [], array $field = ['*']): array
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getUserBillList($uid, $where, implode(',', $field), $page, $limit);
        $count = $this->dao->getUserBillCount($uid, $where);
        
        return compact('data', 'count');
    }

    /**
     * 创建明细记录
     * @param array $data
     * @return bool
     * @throws ValidateException
     */
    public function createBill(array $data): bool
    {
        // 验证必要字段
        $this->validateBillData($data);

        // 设置默认值
        $billData = array_merge([
            'add_time' => time()
        ], $data);

        return $this->dao->createBill($billData) ? true : false;
    }

    /**
     * 验证明细数据
     * @param array $data
     * @throws ValidateException
     */
    protected function validateBillData(array $data): void
    {
        $required = ['uid', 'type', 'pm', 'title', 'number'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new ValidateException("字段 {$field} 是必需的");
            }
        }

        if (!in_array($data['type'], ['integral', 'coin'])) {
            throw new ValidateException('类型必须是 integral 或 coin');
        }

        if (!in_array($data['pm'], [0, 1])) {
            throw new ValidateException('收支类型必须是 0(支出) 或 1(收入)');
        }

        if ($data['number'] <= 0) {
            throw new ValidateException('数量必须大于0');
        }
    }

    /**
     * 积分变动记录
     * @param int $uid
     * @param string $type
     * @param float $number
     * @param float $balance
     * @param string $title
     * @param string $mark
     * @param string $linkId
     * @param int $pm
     * @return bool
     */
   public function integralBill(int $orderTo, int $uid, float $number, float $balance, string $title, string $mark = '', string $linkId = '', int $pm = 1): bool
    {
        return $this->createBill([
            'order_to' => $orderTo,
            'uid' => $uid,
            'type' => 'integral',
            'pm' => $pm,
            'title' => $title,
            'number' => $number,
            'balance' => $balance,
            'mark' => $mark,
            'link_id' => $linkId
        ]);
    }

    /**
     * 通票变动记录
     * @param int $uid
     * @param string $type
     * @param float $number
     * @param float $balance
     * @param string $title
     * @param string $mark
     * @param string $linkId
     * @param int $pm
     * @return bool
     */
    public function coinBill(int $orderTo, int $uid, float $number, float $balance, string $title, string $mark = '', string $linkId = '', int $pm = 1): bool
    {
        return $this->createBill([
            'order_to' => $orderTo,
            'uid' => $uid,
            'type' => 'coin',
            'pm' => $pm,
            'title' => $title,
            'number' => $number,
            'balance' => $balance,
            'mark' => $mark,
            'link_id' => $linkId
        ]);
    }

    // /**
    //  * 积分转通票
    //  * @param int $uid
    //  * @param float $integralAmount
    //  * @param float $coinAmount
    //  * @param float $rate
    //  * @return bool
    //  * @throws Exception
    //  */
    // public function integralToCoin(int $uid, float $integralAmount, float $coinAmount, float $rate = 100): bool
    // {
    //     $user = $this->userServices->getUserInfo($uid);
    //     if (!$user) {
    //         throw new ValidateException('用户不存在');
    //     }

    //     if ($user['integral'] < $integralAmount) {
    //         throw new ValidateException('积分余额不足');
    //     }

    //     // 开启事务
    //     return $this->transaction(function () use ($uid, $integralAmount, $coinAmount, $user) {
    //         // 扣除积分
    //         $this->userServices->decField($uid, 'integral', $integralAmount);
    //         $newIntegral = $user['integral'] - $integralAmount;
            
    //         // 增加通票
    //         $this->userServices->incField($uid, 'coin_num', $coinAmount);
    //         $newCoin = ($user['coin_num'] ?? 0) + $coinAmount;

    //         // 记录积分明细
    //         $this->integralBill(
    //             $uid,
    //             $integralAmount,
    //             $newIntegral,
    //             '积分兑换通票',
    //             "使用{$integralAmount}积分兑换{$coinAmount}通票",
    //             '',
    //             0
    //         );

    //         // 记录通票明细
    //         $this->coinBill(
    //             $uid,
    //             $coinAmount,
    //             $newCoin,
    //             '积分兑换通票',
    //             "使用{$integralAmount}积分兑换{$coinAmount}通票"
    //         );

    //         return true;
    //     });
    // }

    /**
     * 通票转账
     * @param int $fromUid
     * @param int $toUid
     * @param float $amount
     * @param string $remark
     * @return bool
     * @throws Exception
     */
    public function transferCoin(int $fromUid, int $toUid, float $amount, string $remark = ''): bool
    {
        // 验证转账金额范围
        if ($amount < 1) {
            throw new ValidateException('转账金额不能少于1个通票');
        }
        
        if ($amount > 100000) {
            throw new ValidateException('转账金额不能超过100000个通票');
        }

        $fromUser = $this->userServices->getUserInfo($fromUid);
        $toUser = $this->userServices->getUserInfo($toUid);

        if (!$fromUser || !$toUser) {
            throw new ValidateException('用户不存在');
        }

        if (($fromUser['coin_num'] ?? 0) < $amount) {
            throw new ValidateException('通票余额不足');
        }

        // 开启事务
        return $this->transaction(function () use ($fromUid, $toUid, $amount, $fromUser, $toUser, $remark) {
            // 扣除转出方通票
            $this->userServices->decField($fromUid, 'coin_num', $amount);
            $fromBalance = ($fromUser['coin_num'] ?? 0) - $amount;
            
            // 增加转入方通票
            $this->userServices->incField($toUid, 'coin_num', $amount);
            $toBalance = ($toUser['coin_num'] ?? 0) + $amount;

            $transferId = uniqid('transfer_');

            // 记录转出明细
            $this->coinBill(
                $toUid,
                $fromUid,
                $amount,
                $fromBalance,
                '通票转账',
                "向用户{$toUser['nickname']}转账{$amount}通票" . ($remark ? "，备注：{$remark}" : ''),
                $transferId,
                0
            );

            // 记录转入明细
            $this->coinBill(
                $fromUid,
                $toUid,
                $amount,
                $toBalance,
                '通票转账',
                "收到用户{$fromUser['nickname']}转账{$amount}通票" . ($remark ? "，备注：{$remark}" : ''),
                $transferId,
                1
            );

            return true;
        });
    }

    /**
     * 获取统计数据
     * @param array $where
     * @return array
     */
    public function getStatistics(array $where = []): array
    {
        return $this->dao->getStatistics($where);
    }

     /**
     * 获取用户最近明细
     * @param int $uid
     * @param string $type
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserBalance(int $uid): array
    {
        $user = $this->userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }
        $data = [
            'uid' => $uid,
            'nickname' => $user['nickname'],
            'coin_num' => ($user['coin_num'] ?? 0),
            'integral' => ($user['integral'] ?? 0),
            'integral_lock' => ($user['integral_lock'] ?? 0),
        ];
        return $data;
    }

    /**
     * 获取用户最近明细
     * @param int $uid
     * @param string $type
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserRecentBills(int $uid, string $type = '', int $limit = 10): array
    {
        return $this->dao->getUserRecentBills($uid, $type, $limit);
    }

    /**
     * 批量插入账单记录
     * @param array $billData
     * @return bool
     */
    public function saveBatchBills(array $billData): bool
    {
        if (empty($billData)) {
            return true;
        }
        
        return $this->dao->saveAll($billData) !== false;
    }

    /**
     * 根据关联ID获取明细
     * @param string $linkId
     * @param string $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBillByLinkId(string $linkId, string $type = ''): array
    {
        return $this->dao->getBillByLinkId($linkId, $type);
    }

    /**
     * 手动创建账单
     * @param array $data
     * @param int $adminId
     * @return bool
     */
    public function createManualBill(array $data, int $adminId = 0): bool
    {
        $uid = (int)$data['uid'];
        $type = $data['type'];
        $pm = (int)$data['pm'];
        $title = $data['title'];
        $number = (float)$data['number'];
        $mark = $data['mark'] ?? '';

        if ($number <= 0) {
            throw new ValidateException('金额必须大于0');
        }

        $user = $this->userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        return $this->transaction(function () use ($uid, $type, $pm, $title, $number, $mark, $user, $adminId) {
            // 更新用户余额
            if ($pm == 1) { // 收入
                $this->userServices->incField($uid, 'coin_num', $number);
                $balance = ($user['coin_num'] ?? 0) + $number;
            } else { // 支出
                if (($user['coin_num'] ?? 0) < $number) {
                    throw new ValidateException('用户通票余额不足');
                }
                $this->userServices->decField($uid, 'coin_num', $number);
                $balance = ($user['coin_num'] ?? 0) - $number;
            }

            // 创建账单记录
            return $this->createBill([
                'uid' => $uid,
                'type' => $type,
                'pm' => $pm,
                'title' => $title,
                'number' => $number,
                'balance' => $balance,
                'mark' => $mark . "（管理员手动创建，操作员ID：{$adminId}）",
                'link_id' => 'manual_' . uniqid(),
                'add_time' => time()
            ]);
        });
    }

    /**
     * 导出账单数据
     * @param array $where
     * @return array
     */
    public function exportBillData(array $where = []): array
    {
        $list = $this->dao->getList($where, '*', 0, 0);
        
        // 格式化导出数据
        $exportData = [];
        foreach ($list as $item) {
            $exportData[] = [
                'ID' => $item['id'],
                '用户ID' => $item['uid'],
                '用户昵称' => $item['user']['nickname'] ?? '',
                '用户手机号' => $item['user']['phone'] ?? '',
                '类型' => $this->getTypeText($item['type']),
                '收支' => $item['pm'] ? '收入' : '支出',
                '标题' => $item['title'],
                '金额' => $item['number'],
                '余额' => $item['balance'],
                '备注' => $item['mark'],
                '关联ID' => $item['link_id'],
                '创建时间' => date('Y-m-d H:i:s', $item['add_time']),
            ];
        }

        return $exportData;
    }

    /**
     * 解锁指定账单的积分
     * @param int $id
     * @param int $adminId
     * @return bool
     */
    public function unlockIntegralById(int $id, int $adminId = 0): bool
    {
        $bill = $this->dao->get($id);
        if (!$bill) {
            throw new ValidateException('账单记录不存在');
        }

        if ($bill['type'] !== 'integral' || $bill['pm'] != 1) {
            throw new ValidateException('只能解锁积分收入记录');
        }

        $user = $this->userServices->getUserInfo($bill['uid']);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        return $this->transaction(function () use ($bill, $user, $adminId) {
            // 从锁定积分转移到可用积分
            $unlockAmount = $bill['number'];
            $this->userServices->decField($bill['uid'], 'integral_lock', $unlockAmount);
            $this->userServices->incField($bill['uid'], 'integral', $unlockAmount);

            // 创建解锁记录
            return $this->createBill([
                'uid' => $bill['uid'],
                'type' => 'integral',
                'pm' => 1,
                'title' => '积分解锁',
                'number' => $unlockAmount,
                'balance' => ($user['integral'] ?? 0) + $unlockAmount,
                'mark' => "管理员手动解锁积分，原账单ID：{$bill['id']}，操作员ID：{$adminId}",
                'link_id' => 'unlock_' . $bill['id'],
                'add_time' => time()
            ]);
        });
    }

    /**
     * 积分释放任务
     * @return array
     */
    public function releaseIntegral(): array
    {
        try {
            // 获取积分释放比例配置
            $redis = Cache::store('redis')->handler();
            $releaseRate = $redis->get('config:irr');
            if ($releaseRate === false || !is_numeric($releaseRate)) {
                $releaseRate = 0.001;// 默认0.1%
            } else {
                $releaseRate = floatval($releaseRate/1000);
            }
            
            // 获取所有有锁定积分的用户
            $users = $this->userServices->getUsersWithLockedIntegral();
            
            $releaseCount = 0;
            $totalReleaseAmount = 0;
            $promoterBonusData = []; // 存储推广员加成数据

            foreach ($users as $user) {
                $lockedIntegral = $user['integral_lock'] ?? 0;
                if ($lockedIntegral <= 0) {
                    continue;
                }

               // 计算本次释放的积分数量
                // $releaseAmount = round($lockedIntegral * $releaseRate, 2);
                // 计算本次释放的积分数量（向上取整确保为整数）
                // $releaseAmount = ceil($lockedIntegral * $releaseRate);
                // 计算本次释放的积分数量（四舍五入确保为整数）
                $releaseAmount = round($lockedIntegral * $releaseRate);
                if ($releaseAmount <= 0) {
                    continue;
                }

                // 确保不超过锁定积分总数
                if ($releaseAmount > $lockedIntegral) {
                    // $releaseAmount = $lockedIntegral;
                    $releaseAmount = floor($lockedIntegral);
                }

                // 执行积分释放
                $result = $this->userServices->releaseLockedIntegral($user['uid'], $releaseAmount);
                
                if ($result) {
                    // 获取释放后的用户最新积分余额
                    $updatedUser = $this->userServices->getUserInfo($user['uid']);
                    $newBalance = $updatedUser['integral'] ?? 0;
                    
                    // 记录积分释放明细
                    $this->integralBill(
                        0,
                        $user['uid'],
                        $releaseAmount,
                        $newBalance,
                        '系统自动释放锁定积分',
                        "释放比例：" . ($releaseRate * 100) . "%",
                        'auto_release_' . date('Ymd'),
                        1
                    );

                    $releaseCount++;
                    $totalReleaseAmount += $releaseAmount;
                    
                    // 检查是否有推广员，如果有则计算推广员加成
                    $spreadUid = $updatedUser['spread_uid'] ?? 0;
                    if ($spreadUid > 0) {
                        // 计算推广员加成（被推广人释放积分的50%）
                        $promoterBonus = round($releaseAmount * 0.5);
                        if ($promoterBonus > 0) {
                            if (!isset($promoterBonusData[$spreadUid])) {
                                $promoterBonusData[$spreadUid] = 0;
                            }
                            $promoterBonusData[$spreadUid] += $promoterBonus;
                        }
                    }
                }
            }
            
            // 处理推广员加成
            $promoterBonusCount = 0;
            $totalPromoterBonus = 0;
            foreach ($promoterBonusData as $promoterUid => $bonusAmount) {
                // 检查推广员是否存在且有效
                $promoterInfo = $this->userServices->getUserInfo($promoterUid);
                // if (!$promoterInfo || !$promoterInfo['status']) {
                if (!$promoterInfo) {
                    continue;
                }
                
                // 为推广员增加积分（直接增加可用积分，不是锁定积分）
                // $result = $this->userServices->incField($promoterUid, 'integral', $bonusAmount);
                 $result = $this->userServices->releaseLockedIntegral($promoterUid, $bonusAmount);
                
                if ($result) {
                    // 获取推广员最新积分余额
                    $updatedPromoter = $this->userServices->getUserInfo($promoterUid);
                    $newPromoterBalance = $updatedPromoter['integral'] ?? 0;
                    
                    // 记录推广员加成明细
                    $this->integralBill(
                        0,
                        $promoterInfo['uid'],
                        $bonusAmount,
                        $newPromoterBalance,
                        '推广员积分释放加成',
                        "推广员加成：被推广人释放积分的50%",
                        'promoter_bonus_' . date('Ymd'),
                        1
                    );
                    
                    $promoterBonusCount++;
                    $totalPromoterBonus += $bonusAmount;
                }
            }

            // 记录日志
            $logData = [
                'message' => "积分释放任务执行完成，释放用户数：{$releaseCount}，总释放积分：{$totalReleaseAmount}，推广员加成用户数：{$promoterBonusCount}，推广员加成积分：{$totalPromoterBonus}，释放比例：" . ($releaseRate * 100) . "%",
                'type' => 'integral_release',
                'data' => [
                    'release_count' => $releaseCount,
                    'total_release_amount' => $totalReleaseAmount,
                    'promoter_bonus_count' => $promoterBonusCount,
                    'total_promoter_bonus' => $totalPromoterBonus,
                    'release_rate' => $releaseRate,
                    'execute_time' => date('Y-m-d H:i:s')
                ]
            ];
            
            response_log_write($logData);

            return [
                'success' => true,
                'message' => '积分释放任务执行完成',
                'data' => [
                    'release_count' => $releaseCount,
                    'total_release_amount' => $totalReleaseAmount,
                    'promoter_bonus_count' => $promoterBonusCount,
                    'total_promoter_bonus' => $totalPromoterBonus,
                    'release_rate' => $releaseRate,
                    'execute_time' => date('Y-m-d H:i:s')
                ]
            ];

        } catch (\Throwable $e) {
            $errorData = [
                'message' => '积分释放任务失败，失败原因:[' . class_basename($this) . ']' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'type' => 'integral_release_error'
            ];
            
            response_log_write($errorData);

            return [
                'success' => false,
                'message' => '积分释放任务执行失败：' . $e->getMessage(),
                'error' => $errorData
            ];
        }
    }


    // public function releaseIntegral(): array
    // {
    //     try {
    //         // 获取积分释放比例配置
    //         $redis = Cache::store('redis')->handler();
    //         $releaseRate = $redis->get('config:irr');
    //         if ($releaseRate === false || !is_numeric($releaseRate)) {
    //             $releaseRate = 0.001;// 默认0.1%
    //         } else {
    //             $releaseRate = floatval($releaseRate/1000);
    //         }
            
    //         // 获取所有有锁定积分的用户
    //         $users = $this->userServices->getUsersWithLockedIntegral();
            
    //         $releaseCount = 0;
    //         $totalReleaseAmount = 0;

    //         foreach ($users as $user) {
    //             $lockedIntegral = $user['integral_lock'] ?? 0;
    //             if ($lockedIntegral <= 0) {
    //                 continue;
    //             }

    //            // 计算本次释放的积分数量
    //             // $releaseAmount = round($lockedIntegral * $releaseRate, 2);
    //             // 计算本次释放的积分数量（向上取整确保为整数）
    //             // $releaseAmount = ceil($lockedIntegral * $releaseRate);
    //             // 计算本次释放的积分数量（四舍五入确保为整数）
    //             $releaseAmount = round($lockedIntegral * $releaseRate);
    //             if ($releaseAmount <= 0) {
    //                 continue;
    //             }

    //             // 确保不超过锁定积分总数
    //             if ($releaseAmount > $lockedIntegral) {
    //                 // $releaseAmount = $lockedIntegral;
    //                 $releaseAmount = floor($lockedIntegral);
    //             }

    //             // 执行积分释放
    //             $result = $this->userServices->releaseLockedIntegral($user['uid'], $releaseAmount);
                
    //             if ($result) {
    //                 // 获取释放后的用户最新积分余额
    //                 $updatedUser = $this->userServices->getUserInfo($user['uid']);
    //                 $newBalance = $updatedUser['integral'] ?? 0;
                    
    //                 // 记录积分释放明细
    //                 $this->integralBill(
    //                     $user['uid'],
    //                     $releaseAmount,
    //                     $newBalance,
    //                     '系统自动释放锁定积分',
    //                     "释放比例：" . ($releaseRate * 100) . "%",
    //                     'auto_release_' . date('Ymd'),
    //                     1
    //                 );

    //                 $releaseCount++;
    //                 $totalReleaseAmount += $releaseAmount;
    //             }
    //         }

    //         // 记录日志
    //         $logData = [
    //             'message' => "积分释放任务执行完成，释放用户数：{$releaseCount}，总释放积分：{$totalReleaseAmount}，释放比例：" . ($releaseRate * 100) . "%",
    //             'type' => 'integral_release',
    //             'data' => [
    //                 'release_count' => $releaseCount,
    //                 'total_release_amount' => $totalReleaseAmount,
    //                 'release_rate' => $releaseRate,
    //                 'execute_time' => date('Y-m-d H:i:s')
    //             ]
    //         ];
            
    //         response_log_write($logData);

    //         return [
    //             'success' => true,
    //             'message' => '积分释放任务执行完成',
    //             'data' => [
    //                 'release_count' => $releaseCount,
    //                 'total_release_amount' => $totalReleaseAmount,
    //                 'release_rate' => $releaseRate,
    //                 'execute_time' => date('Y-m-d H:i:s')
    //             ]
    //         ];

    //     } catch (\Throwable $e) {
    //         $errorData = [
    //             'message' => '积分释放任务失败，失败原因:[' . class_basename($this) . ']' . $e->getMessage(),
    //             'file' => $e->getFile(),
    //             'line' => $e->getLine(),
    //             'type' => 'integral_release_error'
    //         ];
            
    //         response_log_write($errorData);

    //         return [
    //             'success' => false,
    //             'message' => '积分释放任务执行失败：' . $e->getMessage(),
    //             'error' => $errorData
    //         ];
    //     }
    // }

    /**
     * 获取类型文本
     * @param string $type
     * @return string
     */
    private function getTypeText(string $type): string
    {
        $typeMap = [
            'integral' => '积分',
            'coin' => '通票'
        ];

        return $typeMap[$type] ?? $type;
    }
}