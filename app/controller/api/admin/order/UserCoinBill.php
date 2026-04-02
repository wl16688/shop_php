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

namespace app\controller\api\admin\order;

use app\Request;
use app\services\order\UserCoinBillServices;
use think\annotation\Inject;

/**
 * 管理后台用户金币账单控制器
 * Class UserCoinBill
 * @package app\controller\api\admin\order
 */
class UserCoinBill
{
    /**
     * @Inject
     * @var UserCoinBillServices
     */
    protected UserCoinBillServices $services;

    /**
     * 获取账单列表
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $where = [];
        
        // 用户ID筛选
        if ($request->has('uid') && $request->get('uid')) {
            $where['uid'] = (int)$request->get('uid');
        }
        
        // 类型筛选
        if ($request->has('type') && $request->get('type')) {
            $where['type'] = $request->get('type');
        }
        
        // 收支类型筛选
        if ($request->has('pm') && $request->get('pm') !== '') {
            $where['pm'] = (int)$request->get('pm');
        }
        
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
     * 手动创建账单
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request)
    {
        $data = $request->only(['uid', 'type', 'pm', 'title', 'number', 'mark']);
        $adminId = (int)$request->adminId();

        try {
            $result = $this->services->createManualBill($data, $adminId);
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
     * 获取统计数据
     * @param Request $request
     * @return mixed
     */
    public function statistics(Request $request)
    {
        $startTime = $request->get('start_time');
        $endTime = $request->get('end_time');
        $type = $request->get('type', '');

        try {
            $data = $this->services->getBillStatistics($startTime, $endTime, $type);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取关联账单
     * @param Request $request
     * @param string $linkedId
     * @return mixed
     */
    public function getBillsByLinkedId(Request $request, string $linkedId)
    {
        try {
            $data = $this->services->getBillsByLinkedId($linkedId);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 导出数据
     * @param Request $request
     * @return mixed
     */
    public function export(Request $request)
    {
        $where = [];
        
        // 用户ID筛选
        if ($request->has('uid') && $request->get('uid')) {
            $where['uid'] = (int)$request->get('uid');
        }
        
        // 类型筛选
        if ($request->has('type') && $request->get('type')) {
            $where['type'] = $request->get('type');
        }
        
        // 时间筛选
        if ($request->has('start_time') && $request->get('start_time')) {
            $where['add_time'] = ['>=', strtotime($request->get('start_time'))];
        }
        if ($request->has('end_time') && $request->get('end_time')) {
            $where['add_time'] = ['<=', strtotime($request->get('end_time'))];
        }

        try {
            $result = $this->services->exportBillData($where);
            return app('json')->successful('导出成功', $result);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 解锁积分
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function unlockIntegral(Request $request, int $id)
    {
        $adminId = (int)$request->adminId();

        try {
            $result = $this->services->unlockIntegralById($id, $adminId);
            if ($result) {
                return app('json')->successful('解锁成功');
            } else {
                return app('json')->fail('解锁失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }
}