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
use app\dao\order\UserCoinApplyDao;
use app\services\user\UserServices;
use app\services\order\UserCoinBillServices;
use think\annotation\Inject;
use think\Exception;
use think\exception\ValidateException;

/**
 * 用户通票申请服务
 * Class UserCoinApplyServices
 * @package app\services\order
 * @mixin UserCoinApplyDao
 */
class UserCoinApplyServices extends BaseServices
{
    /**
     * @var UserServices
     */
    #[Inject]
    protected UserServices $userServices;

    /**
     * @var UserCoinBillServices
     */
    #[Inject]
    protected UserCoinBillServices $userCoinBillServices;

    /**
     * @var UserCoinApplyDao
     */
    #[Inject]
    protected UserCoinApplyDao $dao;

    /**
     * 获取申请列表
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
     * 用户申请通票
     * @param int $uid
     * @param array $data
     * @return bool
     * @throws ValidateException
     */
    public function applyForCoin(int $uid, array $data): bool
    {
        // 检查用户是否存在
        $user = $this->userServices->getUserInfo($uid);
        if (!$user) {
            throw new ValidateException('用户不存在');
        }

        // 检查是否有待审核的申请
        if ($this->dao->hasPendingApply($uid)) {
            throw new ValidateException('您有待审核的申请，请等待审核结果');
        }

        // 验证申请数据
        $this->validateApplyData($data);

        $applyData = [
            'uid' => $uid,
            'amount' => $data['amount'],
            'status' => 0, // 待审核
            'add_time' => time(),
            'is_del' => 0
        ];

        return $this->dao->save($applyData) ? true : false;
    }

    /**
     * 验证申请数据
     * @param array $data
     * @throws ValidateException
     */
    protected function validateApplyData(array $data): void
    {
        if (!isset($data['amount']) || $data['amount'] <= 0) {
            throw new ValidateException('申请数量必须大于0');
        }

        if ($data['amount'] > 100000) {
            throw new ValidateException('单次申请数量不能超过100000');
        }

        // if (isset($data['apply_reason']) && mb_strlen($data['apply_reason']) > 500) {
        //     throw new ValidateException('申请理由不能超过500字符');
        // }
    }

    /**
     * 审核申请
     * @param int $id
     * @param int $status
     * @param string $failMsg
     * @param int $adminId
     * @return bool
     * @throws Exception
     */
    public function reviewApply(int $id, int $status, string $failMsg = '', int $adminId = 0): bool
    {
        $apply = $this->dao->get($id);
        if (!$apply) {
            throw new ValidateException('申请记录不存在');
        }

        if ($apply['status'] != 0) {
            throw new ValidateException('该申请已经审核过了');
        }

        // 如果是驳回申请，必须提供驳回理由
        if ($status == 2 && empty($failMsg)) {
            throw new ValidateException('驳回申请必须提供驳回理由');
        }

        // 更新申请状态
        $result = $this->dao->updateStatus($id, $status, $failMsg);
        
        if ($result && $status == 1) { // 审核通过
            // 增加用户通票
            $this->userServices->incField($apply['uid'], 'coin_num', $apply['amount']);
            
            // 记录通票明细
            $this->userCoinBillServices->createBill([
                'uid' => $apply['uid'],
                'type' => 'coin',
                'pm' => 1, // 收入
                'title' => '通票申请审核通过',
                'number' => $apply['amount'],
                'balance' => $this->userServices->getUserInfo($apply['uid'])['coin_num'] ?? 0,
                'mark' => '通票申请审核通过，获得' . $apply['amount'] . '通票',
                'link_id' => (string)$id,
                'add_time' => time()
            ]);
        }

        return $result;
    }

    /**
     * 获取用户申请列表
     * @param int $uid
     * @param array $where
     * @param array $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserApplyList(int $uid, array $where = [], array $field = ['*']): array
    {
        [$page, $limit] = $this->getPageValue();
        $data = $this->dao->getUserApplyList($uid, $where, implode(',', $field), $page, $limit);
        $count = $this->dao->getUserApplyCount($uid, $where);
        
        return compact('data', 'count');
    }

    /**
     * 删除申请
     * @param int $id
     * @param int $uid
     * @return bool
     * @throws ValidateException
     */
    public function deleteApply(int $id, int $uid = 0): bool
    {
        $apply = $this->dao->get($id);
        if (!$apply) {
            throw new ValidateException('申请记录不存在');
        }

        if ($uid && $apply['uid'] != $uid) {
            throw new ValidateException('无权限删除此申请');
        }

        if ($apply['status'] == 0) {
            throw new ValidateException('待审核的申请不能删除');
        }

        return $this->dao->softDelete($id);
    }

    /**
     * 批量删除申请
     * @param array $ids
     * @return bool
     */
    public function batchDelete(array $ids): bool
    {
        if (empty($ids)) {
            throw new ValidateException('请选择要删除的记录');
        }

        return $this->dao->batchSoftDelete($ids);
    }

    /**
     * 获取申请详情
     * @param int $id
     * @param int $uid
     * @return array
     * @throws ValidateException
     */
    public function getApplyDetail(int $id, int $uid = 0): array
    {
        $apply = $this->dao->get($id, ['*'], ['user']);
        if (!$apply) {
            throw new ValidateException('申请记录不存在');
        }

        if ($uid && $apply['uid'] != $uid) {
            throw new ValidateException('无权限查看此申请');
        }

        return $apply->toArray();
    }

    /**
     * 批量删除申请
     * @param array $ids
     * @return bool
     */
    public function batchDeleteApply(array $ids): bool
    {
        if (empty($ids)) {
            throw new ValidateException('请选择要删除的记录');
        }

        return $this->dao->batchSoftDelete($ids);
    }

    /**
     * 更新申请信息
     * @param int $id
     * @param array $data
     * @param int $uid
     * @return bool
     * @throws ValidateException
     */
    public function updateApply(int $id, array $data, int $uid = 0): bool
    {
        $apply = $this->dao->get($id);
        if (!$apply) {
            throw new ValidateException('申请记录不存在');
        }

        if ($uid && $apply['uid'] != $uid) {
            throw new ValidateException('无权限修改此申请');
        }

        if ($apply['status'] != 0) {
            throw new ValidateException('只能修改待审核的申请');
        }

        // 验证申请数据
        if (isset($data['amount']) || isset($data['apply_reason']) || isset($data['contact_info'])) {
            $this->validateApplyData($data);
        }

        return $this->dao->update($id, $data) ? true : false;
    }

    /**
     * 检查用户是否有待处理的申请
     * @param int $uid
     * @return bool
     */
    public function hasPendingApply(int $uid): bool
    {
        return $this->dao->hasPendingApply($uid);
    }

    /**
     * 获取统计数据
     * @param string $startTime
     * @param string $endTime
     * @return array
     */
    public function getStatistics(string $startTime = '', string $endTime = ''): array
    {
        $where = [];
        if ($startTime) {
            $where['add_time'] = ['>=', strtotime($startTime)];
        }
        if ($endTime) {
            $where['add_time'] = ['<=', strtotime($endTime)];
        }

        return $this->dao->getStatistics($where);
    }

    /**
     * 导出申请数据
     * @param array $where
     * @return array
     */
    public function exportApplyData(array $where = []): array
    {
        $list = $this->dao->getList($where, '*', 0, 0);
        
        // 格式化导出数据
        $exportData = [];
        foreach ($list as $item) {
            $exportData[] = [
                'ID' => $item['id'],
                '申请人ID' => $item['uid'],
                '申请人昵称' => $item['user']['nickname'] ?? '',
                '申请人手机号' => $item['user']['phone'] ?? '',
                '申请金额' => $item['amount'],
                '申请状态' => $this->getStatusText($item['status']),
                '驳回理由' => $item['fail_msg'] ?? '',
                '申请时间' => date('Y-m-d H:i:s', $item['add_time']),
                '审核时间' => $item['review_time'] ? date('Y-m-d H:i:s', $item['review_time']) : '',
            ];
        }

        return $exportData;
    }

    /**
     * 获取状态文本
     * @param int $status
     * @return string
     */
    private function getStatusText(int $status): string
    {
        $statusMap = [
            0 => '待审核',
            1 => '审核通过',
            2 => '审核驳回'
        ];

        return $statusMap[$status] ?? '未知状态';
    }
}