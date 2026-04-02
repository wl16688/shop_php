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
use app\model\order\UserCoinBill;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户积分通证明细DAO
 * Class UserCoinBillDao
 * @package app\dao\order
 */
class UserCoinBillDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserCoinBill::class;
    }

    /**
     * 获取明细列表
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
     * 获取明细总数
     * @param array $where
     * @return int
     */
    public function getCount(array $where): int
    {
        return $this->search($where)->count();
    }

    /**
     * 根据用户ID获取明细列表
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
    public function getUserBillList(int $uid, array $where = [], string $field = '*', int $page = 0, int $limit = 0): array
    {
        $where['uid'] = $uid;
        
        return $this->search($where)
            ->field($field)
            ->with(['orderToUser' => fn($query) => $query->field('uid,nickname,avatar')])
            ->when($page && $limit, fn($query) => $query->page($page, $limit))
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取用户明细总数
     * @param int $uid
     * @param array $where
     * @return int
     */
    public function getUserBillCount(int $uid, array $where = []): int
    {
        $where['uid'] = $uid;
        
        return $this->search($where)->count();
    }

    /**
     * 创建明细记录
     * @param array $data
     * @return mixed
     */
    public function createBill(array $data)
    {
        return $this->save($data);
    }

    /**
     * 获取用户某类型的总收入
     * @param int $uid
     * @param string $type
     * @param array $where
     * @return float
     */
    public function getUserTotalIncome(int $uid, string $type, array $where = []): float
    {
        $where['uid'] = $uid;
        $where['type'] = $type;
        $where['pm'] = UserCoinBill::PM_IN;
        
        return (float)$this->search($where)->sum('number');
    }

    /**
     * 获取用户某类型的总支出
     * @param int $uid
     * @param string $type
     * @param array $where
     * @return float
     */
    public function getUserTotalExpense(int $uid, string $type, array $where = []): float
    {
        $where['uid'] = $uid;
        $where['type'] = $type;
        $where['pm'] = UserCoinBill::PM_OUT;
        
        return (float)$this->search($where)->sum('number');
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
            'total_count' => $query->count(),
            'total_income' => (float)$query->where('pm', UserCoinBill::PM_IN)->sum('number'),
            'total_expense' => (float)$query->where('pm', UserCoinBill::PM_OUT)->sum('number'),
            'integral_income' => (float)$query->where(['pm' => UserCoinBill::PM_IN, 'type' => UserCoinBill::TYPE_INTEGRAL])->sum('number'),
            'integral_expense' => (float)$query->where(['pm' => UserCoinBill::PM_OUT, 'type' => UserCoinBill::TYPE_INTEGRAL])->sum('number'),
            'coin_income' => (float)$query->where(['pm' => UserCoinBill::PM_IN, 'type' => UserCoinBill::TYPE_COIN])->sum('number'),
            'coin_expense' => (float)$query->where(['pm' => UserCoinBill::PM_OUT, 'type' => UserCoinBill::TYPE_COIN])->sum('number'),
        ];
    }

    /**
     * 根据关联ID获取明细
     * @param string $linkId
     * @param string $type
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getBillByLinkId(string $linkId, string $type = ''): array
    {
        $where = ['link_id' => $linkId];
        if ($type) {
            $where['type'] = $type;
        }
        
        return $this->search($where)
            ->with(['user' => fn($query) => $query->field('uid,nickname,avatar')])
            ->order('id desc')
            ->select()
            ->toArray();
    }

    /**
     * 获取用户最近的明细记录
     * @param int $uid
     * @param string $type
     * @param int $limit
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserRecentBills(int $uid, string $type = '', int $limit = 10): array
    {
        $where = ['uid' => $uid];
        if ($type) {
            $where['type'] = $type;
        }
        
        return $this->search($where)
            ->field('id,title,type,pm,number,balance,mark,add_time')
            ->limit($limit)
            ->order('id desc')
            ->select()
            ->toArray();
    }
}