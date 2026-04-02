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

namespace app\controller\api\v2\order;

use app\Request;
use app\services\order\UserCoinBillServices;
use app\services\user\UserServices;
use crmeb\services\CacheService;
use think\annotation\Inject;

/**
 * 用户积分通票明细控制器
 * Class UserCoinBill
 * @package app\controller\api\v2\order
 */
class UserCoinBill
{
    /**
     * @var UserCoinBillServices
     */
    #[Inject]
    protected UserCoinBillServices $services;

    /**
     * @var UserServices
     */
    #[Inject]
    protected UserServices $userServices;

    /**
     * 获取用户明细列表
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $uid = (int)$request->uid();
        // $uid = 19;
        $where = [];
        
        // 类型筛选
        if ($request->has('type') && $request->get('type')) {
            $where['type'] = $request->get('type');
        }
        
        // 收支类型筛选
        if ($request->has('pm') && $request->get('pm') !== '') {
            $where['pm'] = (int)$request->get('pm');
        }
        
        // // 详细类型筛选
        // if ($request->has('detail_type') && $request->get('detail_type')) {
        //     $where['detail_type'] = $request->get('detail_type');
        // }
        
        // 时间筛选
        if ($request->has('start_time') && $request->get('start_time')) {
            $where['add_time'] = ['>=', strtotime($request->get('start_time'))];
        }
        if ($request->has('end_time') && $request->get('end_time')) {
            $where['add_time'] = ['<=', strtotime($request->get('end_time'))];
        }

        try {
            $data = $this->services->getUserBillList($uid, $where);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取用户明细列表
     * @param Request $request
     * @return mixed
     */
    public function search(Request $request)
    {
        $where = [];
        
        // 类型筛选
        if ($request->has('type') && $request->get('type')) {
            $where['type'] = $request->get('type');
        }
        
        // 收支类型筛选
        if ($request->has('pm') && $request->get('pm') !== '') {
            $where['pm'] = (int)$request->get('pm');
        }
        
        // // 详细类型筛选
        // if ($request->has('detail_type') && $request->get('detail_type')) {
        //     $where['detail_type'] = $request->get('detail_type');
        // }
        
        // 时间筛选
        if ($request->has('start_time') && $request->get('start_time')) {
            $where['add_time'] = ['>=', strtotime($request->get('start_time'))];
        }
        if ($request->has('end_time') && $request->get('end_time')) {
            $where['add_time'] = ['<=', strtotime($request->get('end_time'))];
        }

        try {
            $data = $this->services->getList($where);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取用户余额
     * @param Request $request
     * @return mixed
     */
    public function getUserBalance(Request $request)
    {
        $uid = (int)$request->uid();
        // $uid = 19;
        try {
            $data = $this->services->getUserBalance($uid);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }
    /**
     * 获取用户最近明细
     * @param Request $request
     * @return mixed
     */
    public function getRecentBills(Request $request)
    {
        $uid = (int)$request->uid();
        // $uid = 19;
        $type = $request->get('type', '');
        $limit = (int)$request->get('limit', 10);

        try {
            $data = $this->services->getUserRecentBills($uid, $type, $limit);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 通票转账
     * @param Request $request
     * @return mixed
     */
    public function transferCoin(Request $request)
    {
        // $fromUid = (int)$request->uid();
        $fromUid = 19;
        // $toPhone = $request->post('to_phone', '');
        $toId = $request->post('to_id', '');
        $amount = (float)$request->post('amount', 0);
        $remark = $request->post('mark', '');

        // if (!$toPhone) {
        //     return app('json')->fail('请输入接收方手机号');
        // }

        if (!$toId) {
            return app('json')->fail('请输入接收方用户ID');
        }

        if ($amount <= 0) {
            return app('json')->fail('转账数量必须大于0');
        }

        if ($amount > 100000) {
            return app('json')->fail('转账数量最大10w');
        }

        try {
            // 根据手机号查找用户
            // $toUser = $this->userServices->getUserInfoByPhone($toPhone);
            $toUser = $this->userServices->getUserInfo($toId);
            if (!$toUser) {
                return app('json')->fail('接收方用户不存在');
            }

            if ($fromUid == $toUser['uid']) {
                return app('json')->fail('不能向自己转账');
            }

            $result = $this->services->transferCoin($fromUid, $toUser['uid'], $amount, $remark);
            if ($result) {
                return app('json')->successful('转账成功', [
                    'to_user' => $toUser['nickname'],
                    'amount' => $amount
                ]);
            } else {
                return app('json')->fail('转账失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取用户余额信息
     * @param Request $request
     * @return mixed
     */
    public function balance(Request $request)
    {
        // $uid = (int)$request->uid();
        $uid = 19;

        try {
            $user = $this->userServices->getUserInfo($uid);
            if (!$user) {
                return app('json')->fail('用户不存在');
            }

            $data = [
                'integral' => $user['integral'] ?? 0,
                'coin_num' => $user['coin_num'] ?? 0,
                'integral_lock' => $user['integral_lock'] ?? 0
            ];

            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取明细类型选项
     * @return mixed
     */
    public function typeOptions()
    {
        $options = [
            [
                'value' => 'integral',
                'label' => '积分',
                'children' => [
                    ['value' => 'gain', 'label' => '积分获得'],
                    ['value' => 'deduction', 'label' => '积分抵扣'],
                    ['value' => 'refund', 'label' => '积分退还'],
                    ['value' => 'transfer', 'label' => '积分转账'],
                    ['value' => 'exchange', 'label' => '积分兑换']
                ]
            ],
            [
                'value' => 'coin',
                'label' => '通票',
                'children' => [
                    ['value' => 'apply', 'label' => '通票申请'],
                    ['value' => 'reward', 'label' => '通票奖励'],
                    ['value' => 'transfer', 'label' => '通票转账'],
                    ['value' => 'exchange', 'label' => '通票兑换'],
                    ['value' => 'consume', 'label' => '通票消费']
                ]
            ]
        ];

        return app('json')->successful('获取成功', $options);
    }

    /**
     * 获取收支类型选项
     * @return mixed
     */
    public function pmOptions()
    {
        $options = [
            ['value' => 1, 'label' => '收入'],
            ['value' => 0, 'label' => '支出']
        ];

        return app('json')->successful('获取成功', $options);
    }

    /**
     * 创建账单记录
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        $uid = (int)$request->uid();
        // $uid = 19;
        $data = $request->only(['type', 'pm', 'title', 'number', 'mark', 'link_id']);
        $data['uid'] = $uid;

        // 获取用户当前余额
        $user = $this->userServices->getUserInfo($uid);
        if (!$user) {
            return app('json')->fail('用户不存在');
        }

        // 根据类型设置余额
        if ($data['type'] === 'integral') {
            $data['balance'] = $user['integral'] ?? 0;
        } else {
            $data['balance'] = $user['coin_num'] ?? 0;
        }

        try {
            $result = $this->services->createBill($data);
            if ($result) {
                return app('json')->successful('创建成功');
            } else {
                return app('json')->fail('创建失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 根据关联ID获取明细
     * @param Request $request
     * @param string $linkId
     * @return mixed
     */
    public function getByLinkId(Request $request, string $linkId)
    {
        $type = $request->get('type', '');

        try {
            $data = $this->services->getBillByLinkId($linkId, $type);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 更新账单记录
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function update(Request $request, int $id)
    {
        $uid = (int)$request->uid();
        // $uid = 19;
        $data = $request->only(['title', 'mark']);

        try {
            $result = $this->services->updateBill($id, $data, $uid);
            if ($result) {
                return app('json')->successful('更新成功');
            } else {
                return app('json')->fail('更新失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 删除账单记录
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function delete(Request $request, int $id)
    {
        $uid = (int)$request->uid();
        // $uid = 19;

        try {
            $result = $this->services->deleteBill($id, $uid);
            if ($result) {
                return app('json')->successful('删除成功');
            } else {
                return app('json')->fail('删除失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    public function releaseIntegral(){
        // // 安全检查：只允许服务器本机执行
        // $clientIp = request()->ip();
        // $allowedIps = ['127.0.0.1', '::1', 'localhost'];
        
        // if (!in_array($clientIp, $allowedIps)) {
        //     response_log_write([
        //         'message' => '积分释放任务被拒绝执行，非法IP访问：' . $clientIp,
        //         'type' => 'integral_release_security',
        //         'ip' => $clientIp,
        //         'time' => date('Y-m-d H:i:s')
        //     ]);
        //     return app('json')->fail('访问被拒绝：此接口仅限服务器本机调用');
        // }
        
        try {
            // 调用服务类的积分释放方法
            $result = $this->services->releaseIntegral();
            
            if ($result['success']) {
                return app('json')->success($result['message'], $result['data']);
            } else {
                return app('json')->fail($result['message']);
            }
            
        } catch (\Throwable $e) {
            response_log_write([
                'message' => '积分释放任务控制器异常：' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'type' => 'integral_release_controller_error'
            ]);
            
            return app('json')->fail('积分释放任务执行异常');
        }
    }

    // /**
    //  * 获取有锁定积分的用户列表（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function getUsersWithLockedIntegral(Request $request)
    // {
    //     try {
    //         $users = $this->userServices->getUsersWithLockedIntegral();
            
    //         $data = [];
    //         foreach ($users as $user) {
    //             $data[] = [
    //                 'uid' => $user['uid'],
    //                 'nickname' => $user['nickname'] ?? '',
    //                 'phone' => $user['phone'] ?? '',
    //                 'integral' => $user['integral'] ?? 0,
    //                 'integral_lock' => $user['integral_lock'] ?? 0
    //             ];
    //         }
            
    //         return app('json')->successful('获取成功', [
    //             'list' => $data,
    //             'total' => count($data)
    //         ]);
    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }

    // /**
    //  * 手动触发积分释放（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function manualReleaseIntegral(Request $request)
    // {
    //     $releaseRate = (float)$request->post('release_rate', 0.001); // 默认0.1%
    //     $targetUid = (int)$request->post('target_uid', 0); // 指定用户ID，0表示所有用户

    //     if ($releaseRate <= 0 || $releaseRate > 1) {
    //         return app('json')->fail('释放比例必须在0-1之间');
    //     }

    //     try {
    //         // 获取所有有锁定积分的用户或指定用户
    //         if ($targetUid > 0) {
    //             $user = $this->userServices->getUserInfo($targetUid);
    //             if (!$user) {
    //                 return app('json')->fail('指定用户不存在');
    //             }
    //             $users = [$user];
    //         } else {
    //             $users = $this->userServices->getUsersWithLockedIntegral();
    //         }
            
    //         $releaseCount = 0;
    //         $totalReleaseAmount = 0;
    //         $releaseDetails = [];

    //         foreach ($users as $user) {
    //             $lockedIntegral = $user['integral_lock'] ?? 0;
    //             if ($lockedIntegral <= 0) {
    //                 continue;
    //             }

    //             // 计算本次释放的积分数量
    //             $releaseAmount = round($lockedIntegral * $releaseRate, 2);
    //             if ($releaseAmount <= 0) {
    //                 continue;
    //             }

    //             // 确保不超过锁定积分总数
    //             if ($releaseAmount > $lockedIntegral) {
    //                 $releaseAmount = $lockedIntegral;
    //             }

    //             // 执行积分释放
    //             $result = $this->userServices->releaseLockedIntegral($user['uid'], $releaseAmount);
                
    //             if ($result) {
    //                 // 记录积分释放明细
    //                 $this->services->income($user['uid'], 'integral', 'gain', [
    //                     'number' => $releaseAmount,
    //                     'mark' => "手动释放锁定积分，释放比例：" . ($releaseRate * 100) . "%",
    //                     'status' => 1,
    //                     'link_id' => 'manual_release_' . date('Ymd') . '_' . $user['uid']
    //                 ]);

    //                 $releaseCount++;
    //                 $totalReleaseAmount += $releaseAmount;
                    
    //                 $releaseDetails[] = [
    //                     'uid' => $user['uid'],
    //                     'nickname' => $user['nickname'] ?? '',
    //                     'locked_integral' => $lockedIntegral,
    //                     'release_amount' => $releaseAmount,
    //                     'remaining_locked' => $lockedIntegral - $releaseAmount
    //                 ];
    //             }
    //         }

    //         return app('json')->successful('积分释放完成', [
    //             'release_count' => $releaseCount,
    //             'total_release_amount' => $totalReleaseAmount,
    //             'release_rate' => $releaseRate,
    //             'release_rate_percent' => ($releaseRate * 100) . '%',
    //             'execute_time' => date('Y-m-d H:i:s'),
    //             'details' => $releaseDetails
    //         ]);

    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }

    // /**
    //  * 单个用户积分释放（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function releaseUserIntegral(Request $request)
    // {
    //     $uid = (int)$request->post('uid', 0);
    //     $amount = (float)$request->post('amount', 0);

    //     if (!$uid) {
    //         return app('json')->fail('请指定用户ID');
    //     }

    //     if ($amount <= 0) {
    //         return app('json')->fail('释放数量必须大于0');
    //     }

    //     try {
    //         $user = $this->userServices->getUserInfo($uid);
    //         if (!$user) {
    //             return app('json')->fail('用户不存在');
    //         }

    //         $lockedIntegral = $user['integral_lock'] ?? 0;
    //         if ($lockedIntegral <= 0) {
    //             return app('json')->fail('该用户没有锁定积分');
    //         }

    //         if ($amount > $lockedIntegral) {
    //             return app('json')->fail('释放数量不能超过锁定积分总数');
    //         }

    //         // 执行积分释放
    //         $result = $this->userServices->releaseLockedIntegral($uid, $amount);
            
    //         if ($result) {
    //             // 记录积分释放明细
    //             $this->services->income($uid, 'integral', 'gain', [
    //                 'number' => $amount,
    //                 'mark' => "手动释放指定数量锁定积分",
    //                 'status' => 1,
    //                 'link_id' => 'manual_release_amount_' . date('Ymd') . '_' . $uid
    //             ]);

    //             return app('json')->successful('积分释放成功', [
    //                 'uid' => $uid,
    //                 'nickname' => $user['nickname'] ?? '',
    //                 'release_amount' => $amount,
    //                 'original_locked' => $lockedIntegral,
    //                 'remaining_locked' => $lockedIntegral - $amount,
    //                 'execute_time' => date('Y-m-d H:i:s')
    //             ]);
    //         } else {
    //             return app('json')->fail('积分释放失败');
    //         }

    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }

    // /**
    //  * 获取积分释放配置（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function getReleaseConfig(Request $request)
    // {
    //     try {
    //         // 获取积分释放比例配置
    //         $releaseRate = \crmeb\services\CacheService::redisHandler()->get('config:irr');
    //         if ($releaseRate === false || !is_numeric($releaseRate)) {
    //             $releaseRate = 0.001; // 默认0.1%
    //         } else {
    //             $releaseRate = floatval($releaseRate);
    //         }

    //         return app('json')->successful('获取成功', [
    //             'release_rate' => $releaseRate,
    //             'release_rate_percent' => ($releaseRate * 100) . '%',
    //             'description' => '积分释放比例配置'
    //         ]);
    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }

    // /**
    //  * 设置积分释放配置（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function setReleaseConfig(Request $request)
    // {
    //     $releaseRate = (float)$request->post('release_rate', 0);

    //     if ($releaseRate <= 0 || $releaseRate > 1) {
    //         return app('json')->fail('释放比例必须在0-1之间');
    //     }

    //     try {
    //         // 设置积分释放比例配置
    //         \crmeb\services\CacheService::redisHandler()->set('config:irr', $releaseRate);

    //         return app('json')->successful('设置成功', [
    //             'release_rate' => $releaseRate,
    //             'release_rate_percent' => ($releaseRate * 100) . '%',
    //             'description' => '积分释放比例配置已更新'
    //         ]);
    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }

    // /**
    //  * 获取用户积分详情（测试接口）
    //  * @param Request $request
    //  * @return mixed
    //  */
    // public function getUserIntegralDetail(Request $request)
    // {
    //     $uid = (int)$request->get('uid', 0);
        
    //     if (!$uid) {
    //         return app('json')->fail('请指定用户ID');
    //     }

    //     try {
    //         $user = $this->userServices->getUserInfo($uid);
    //         if (!$user) {
    //             return app('json')->fail('用户不存在');
    //         }

    //         $data = [
    //             'uid' => $user['uid'],
    //             'nickname' => $user['nickname'] ?? '',
    //             'phone' => $user['phone'] ?? '',
    //             'integral' => $user['integral'] ?? 0, // 可用积分
    //             'integral_lock' => $user['integral_lock'] ?? 0, // 锁定积分
    //             'coin_num' => $user['coin_num'] ?? 0, // 通票数量
    //             'total_integral' => ($user['integral'] ?? 0) + ($user['integral_lock'] ?? 0), // 总积分
    //             'description' => [
    //                 'integral' => '可用积分（可直接使用）',
    //                 'integral_lock' => '锁定积分（需要释放后才能使用）',
    //                 'coin_num' => '通票数量',
    //                 'total_integral' => '总积分（可用积分 + 锁定积分）'
    //             ]
    //         ];

    //         return app('json')->successful('获取成功', $data);
    //     } catch (\Exception $e) {
    //         return app('json')->fail($e->getMessage());
    //     }
    // }
}