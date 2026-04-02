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
use app\services\order\UserCoinApplyServices;
use think\annotation\Inject;

/**
 * 管理后台用户金币申请控制器
 * Class UserCoinApply
 * @package app\controller\api\admin\order
 */
class UserCoinApply
{
    /**
     * @Inject
     * @var UserCoinApplyServices
     */
    protected UserCoinApplyServices $services;

    /**
     * 获取申请列表
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $where = [];
        
        // 申请状态筛选
        if ($request->has('status') && $request->get('status') !== '') {
            $where['status'] = (int)$request->get('status');
        }
        
        // 申请人ID筛选
        if ($request->has('uid') && $request->get('uid')) {
            $where['uid'] = (int)$request->get('uid');
        }
        
        // 申请人手机号筛选
        if ($request->has('phone') && $request->get('phone')) {
            $where['phone'] = $request->get('phone');
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
     * 审核申请
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function review(Request $request, int $id)
    {
        $status = (int)$request->post('status'); // 1:通过 2:驳回
        $failMsg = $request->post('fail_msg', '');
        $adminId = (int)$request->adminId();

        try {
            $result = $this->services->reviewApply($id, $status, $failMsg, $adminId);
            if ($result) {
                $statusText = $status == 1 ? '通过' : '驳回';
                return app('json')->successful("审核{$statusText}成功");
            } else {
                return app('json')->fail('审核失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取申请详情
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function detail(Request $request, int $id)
    {
        try {
            $data = $this->services->getApplyDetail($id);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 删除申请
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function delete(Request $request, int $id)
    {
        try {
            $result = $this->services->deleteApply($id);
            if ($result) {
                return app('json')->successful('删除成功');
            } else {
                return app('json')->fail('删除失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 批量删除申请
     * @param Request $request
     * @return mixed
     */
    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return app('json')->fail('请选择要删除的申请');
        }

        try {
            $result = $this->services->batchDeleteApply($ids);
            if ($result) {
                return app('json')->successful('批量删除成功');
            } else {
                return app('json')->fail('批量删除失败');
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

        try {
            $data = $this->services->getStatistics($startTime, $endTime);
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
        
        // 申请状态筛选
        if ($request->has('status') && $request->get('status') !== '') {
            $where['status'] = (int)$request->get('status');
        }
        
        // 时间筛选
        if ($request->has('start_time') && $request->get('start_time')) {
            $where['add_time'] = ['>=', strtotime($request->get('start_time'))];
        }
        if ($request->has('end_time') && $request->get('end_time')) {
            $where['add_time'] = ['<=', strtotime($request->get('end_time'))];
        }

        try {
            $result = $this->services->exportApplyData($where);
            return app('json')->successful('导出成功', $result);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }
}