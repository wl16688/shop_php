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
use app\services\order\UserCoinApplyServices;
use app\Request;
use think\annotation\Inject;

/**
 * 用户通证申请管理
 * Class UserCoinApply
 * @package app\controller\admin\v1\order
 */
class UserCoinApply extends AuthController
{
    /**
     * @Inject
     * @var UserCoinApplyServices
     */
    protected UserCoinApplyServices $services;

    /**
     * 搜索申请记录
     * @param Request $request
     * @return mixed
     */
    public function search(Request $request)
    {
        $where = [];
        
        // 用户ID筛选
        if ($request->has('uid') && $request->get('uid') !== '') {
            $where['uid'] = (int)$request->get('uid');
        }
        
        // 状态筛选
        if ($request->has('status') && $request->get('status') !== '') {
            $where['status'] = (int)$request->get('status');
        }
        
        // 手机号筛选
        if ($request->has('phone') && $request->get('phone') !== '') {
            $where['phone'] = $request->get('phone');
        }
        
        // 金额筛选
        if ($request->has('min_amount') || $request->has('max_amount')) {
            $amountRange = [];
            if ($request->has('min_amount') && $request->get('min_amount') !== '') {
                $amountRange[0] = (float)$request->get('min_amount');
            }
            if ($request->has('max_amount') && $request->get('max_amount') !== '') {
                $amountRange[1] = (float)$request->get('max_amount');
            }
            if (!empty($amountRange)) {
                $where['amount'] = $amountRange;
            }
        }
        
        // 时间范围筛选
        if ($request->has('start_time') || $request->has('end_time')) {
            $timeRange = [];
            if ($request->has('start_time') && $request->get('start_time') !== '') {
                $timeRange[0] = $request->get('start_time');
            }
            if ($request->has('end_time') && $request->get('end_time') !== '') {
                $timeRange[1] = $request->get('end_time');
            }
            if (count($timeRange) == 2) {
                $where['add_time'] = $timeRange;
            }
        }
        
        // 删除状态筛选（默认查询未删除的记录）
        $where['is_del'] = $request->get('is_del', 0);

        try {
            $data = $this->services->getList($where);
            return app('json')->successful('搜索成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 审核申请
     * @param Request $request
     * @return mixed
     */
    public function review(Request $request)
    {
        $id = (int)$request->post('id');
        $status = (int)$request->post('status'); // 审核状态：1=通过，2=拒绝
        $failMsg = $request->post('fail_msg', ''); // 拒绝理由
        $adminId = 0; // 这里可以根据实际需求获取管理员ID
        
        // 验证审核状态
        if (!in_array($status, [1, 2])) {
            return app('json')->fail('审核状态无效');
        }
        
        // 如果是拒绝，必须提供拒绝理由
        if ($status == 2 && empty($failMsg)) {
            return app('json')->fail('拒绝申请必须提供拒绝理由');
        }

        try {
            $result = $this->services->reviewApply($id, $status, $failMsg, $adminId);
            if ($result) {
                $statusText = $status == 1 ? '通过' : '拒绝';
                return app('json')->successful('审核' . $statusText . '成功');
            } else {
                return app('json')->fail('审核失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 获取申请详情
     * @param int $id
     * @return mixed
     */
    public function detail(int $id)
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
     * @param int $id
     * @return mixed
     */
    public function delete(int $id)
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
            return app('json')->fail('请选择要删除的记录');
        }

        try {
            $result = $this->services->batchDelete($ids);
            if ($result) {
                return app('json')->successful('批量删除成功');
            } else {
                return app('json')->fail('批量删除失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }
}