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
use app\services\order\UserCoinApplyServices;
use think\annotation\Inject;

/**
 * 用户通票申请控制器
 * Class UserCoinApply
 * @package app\controller\api\v2\order
 */
class UserCoinApply
{
    /**
     * @var UserCoinApplyServices
     */
    #[Inject]
    protected UserCoinApplyServices $services;

    /**
     * 获取用户申请列表
     * @param Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        // $uid = (int)$request->uid();
        $uid = 19;
        $where = [];
        
        // 状态筛选
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
            $data = $this->services->getUserApplyList($uid, $where);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 申请通票
     * @param Request $request
     * @return mixed
     */
    public function apply(Request $request)
    {
        // $uid = (int)$request->uid();
        $uid = 19;
        $data = $request->only(['amount']);

        try {
            $result = $this->services->applyForCoin($uid, $data);
            if ($result) {
                return app('json')->successful('申请提交成功，请等待审核');
            } else {
                return app('json')->fail('申请提交失败');
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
        // $uid = (int)$request->uid();
        $uid = 19;

        try {
            $data = $this->services->getApplyDetail($id, $uid);
            return app('json')->successful('获取成功', $data);
        } catch (\Exception $e) {
            return app('json')->fail($e->getMessage());
        }
    }

    /**
     * 更新申请
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function update(Request $request, int $id)
    {
        // $uid = (int)$request->uid();
        $uid = 19;
        $data = $request->only(['amount']);

        try {
            $result = $this->services->updateApply($id, $data, $uid);
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
     * 删除申请
     * @param Request $request
     * @param int $id
     * @return mixed
     */
    public function delete(Request $request, int $id)
    {
        // $uid = (int)$request->uid();
        $uid = 19;

        try {
            $result = $this->services->deleteApply($id, $uid);
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
     * 获取申请状态选项
     * @return mixed
     */
    public function getStatusOptions()
    {
        $options = [
            ['value' => 0, 'label' => '待审核'],
            ['value' => 1, 'label' => '审核通过'],
            ['value' => 2, 'label' => '审核拒绝']
        ];

        return app('json')->successful('获取成功', $options);
    }

}