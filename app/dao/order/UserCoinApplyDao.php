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

namespace app\dao\order;

use app\dao\BaseDao;
use app\model\order\UserCoinApply;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户通证申请DAO
 * Class UserCoinApplyDao
 * @package app\dao\order
 */
class UserCoinApplyDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserCoinApply::class;
    }

    /**
     * 获取申请列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0): array
    {
        return $this->search($where)
            ->field($field)
            ->with(['user' => fn($query) => $query->field('uid,nickname,avatar,phone')])
            ->when($page && $limit, fn($query) => $query->page($page, $limit))
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取申请总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where): int
    {
        return $this->search($where)->count();
    }

    /**
     * 根据用户ID获取申请记录
     * @param int $uid
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserApplyList(int $uid, array $where = [], string $field = '*', int $page = 0, int $limit = 0): array
    {
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        
        return $this->search($where)
            ->field($field)
            ->when($page && $limit, fn($query) => $query->page($page, $limit))
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取用户申请总数
     * @param int $uid
     * @param array $where
     * @return int
     */
    public function getUserApplyCount(int $uid, array $where = []): int
    {
        $where['uid'] = $uid;
        $where['is_del'] = 0;
        
        return $this->search($where)->count();
    }

    /**
     * 审核申请
     * @param int $id
     * @param int $status
     * @param string $failMsg
     * @return bool
     */
    public function updateStatus(int $id, int $status, string $failMsg = ''): bool
    {
        $data = [
            'status' => $status,
            'status_time' => time(),
        ];
        
        if ($status == UserCoinApply::STATUS_REJECTED && $failMsg) {
            $data['fail_msg'] = $failMsg;
        }
        
        return $this->getModel()->where('id', $id)->update($data) > 0;
    }

    /**
     * 软删除申请
     * @param int $id
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        return $this->getModel()->where('id', $id)->update(['is_del' => 1]) > 0;
    }

    /**
     * 批量软删除
     * @param array $ids
     * @return bool
     */
    public function batchSoftDelete(array $ids): bool
    {
        return $this->getModel()->whereIn('id', $ids)->update(['is_del' => 1]);
    }

    /**
     * 检查用户是否有待审核的申请
     * @param int $uid
     * @return bool
     */
    public function hasPendingApply(int $uid): bool
    {
        return $this->search([
            'uid' => $uid,
            'status' => UserCoinApply::STATUS_PENDING,
            'is_del' => 0
        ])->count() > 0;
    }

    /**
     * 获取统计数据
     * @param array $where
     * @return array
     */
    public function getStatistics(array $where = []): array
    {
        $query = $this->search($where);
        
        return [
            'total' => $query->count(),
            'pending' => $query->where('status', UserCoinApply::STATUS_PENDING)->count(),
            'approved' => $query->where('status', UserCoinApply::STATUS_APPROVED)->count(),
            'rejected' => $query->where('status', UserCoinApply::STATUS_REJECTED)->count(),
        ];
    }
}