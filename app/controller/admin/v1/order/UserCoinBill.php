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

namespace app\controller\admin\v1\order;

use app\controller\admin\AuthController;
use app\services\order\UserCoinBillServices;
use app\services\user\UserServices;
use app\Request;
use think\annotation\Inject;

/**
 * 用户积分通证明细管理
 * Class UserCoinBill
 * @package app\controller\admin\v1\order
 */
class UserCoinBill extends AuthController
{
    /**
     * @Inject
     * @var UserCoinBillServices
     */
    protected UserCoinBillServices $services;

    /**
     * @Inject
     * @var UserServices
     */
    protected UserServices $userServices;

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
     * 积分解锁
     * @param Request $request
     * @return mixed
     */
    public function unlockIntegral(Request $request)
    {
        $uid = (int)$request->post('uid');
        $amount = (float)$request->post('amount');

        if (!$uid || $amount <= 0) {
            return app('json')->fail('参数错误');
        }

        // 检查用户是否存在
        $user = $this->userServices->getUserInfo($uid);
        if (!$user) {
            return app('json')->fail('用户不存在');
        }

        try {
            /** @var \app\model\user\User $userModel */
            $userModel = app()->make(\app\model\user\User::class);
            $result = $userModel::unlockIntegral($uid, $amount);
            
            if ($result) {
                // 记录解锁明细
                $this->services->integralBill(
                    $uid,
                    'unlock',
                    $amount,
                    $user['integral'] ?? 0,
                    '管理员解锁积分',
                    "管理员解锁{$amount}积分",
                    'admin_unlock_' . $this->adminId . '_' . time()
                );
                
                return app('json')->successful('解锁成功');
            } else {
                return app('json')->fail('解锁失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }
}