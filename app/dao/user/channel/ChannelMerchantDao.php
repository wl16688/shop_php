<?php
declare (strict_types=1);

namespace app\dao\user\channel;

use app\dao\BaseDao;
use app\model\user\channel\ChannelMerchant;

/**
 * 采购商DAO
 * Class ChannelMerchantDao
 * @package app\dao\user\channel
 */
class ChannelMerchantDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ChannelMerchant::class;
    }

    /**
     * 获取采购商列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getChannelList(array $where, int $page, int $limit)
    {
        $where['is_del'] = 0;
        return $this->search($where)
            ->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
                if (isset($where['field_key']) && $where['keyword'] !== '') {
                    $query->where($where['field_key'], 'like', '%' . $where['keyword'] . '%');
                } else {
                    $query->where('channel_name|real_name|phone|uid', 'like', '%' . $where['keyword'] . '%');
                }
            })->when(isset($where['verify_status']) && $where['verify_status'] !== '', function ($query) use ($where) {
                $query->where('verify_status', $where['verify_status']);
            })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
                getModelTime($query, $where['date'], 'add_time');
            })->when(isset($where['count']) && $where['count'] !== '', function ($query) use ($where) {
                $counts = explode('-', $where['count']);
                if(count($counts) == 1){
                    $query->where('order_count', '>=', $counts[0]);
                }
                if (count($counts) == 2 && ($counts[0] !== '' || $counts[1] !== '')) {
                    if ($counts[0] === '') {
                        $query->where('order_count', '<=', $counts[1]);
                    } elseif ($counts[1] === '') {
                        $query->where('order_count', '>=', $counts[0]);
                    } else {
                        $query->whereBetween('order_count', $counts);
                    }
                }
            })->when(isset($where['money']) && $where['money'] !== '', function ($query) use ($where) {
                $moneys = explode('-', $where['money']);
                if(count($moneys) == 1){
                    $query->where('order_price', '>=', $moneys[0]);
                }
                if (count($moneys) == 2 && ($moneys[0] !== '' || $moneys[1] !== '')) {
                    if ($moneys[0] === '') {
                        $query->where('order_price', '<=', $moneys[1]);
                    } elseif ($moneys[1] === '') {
                        $query->where('order_price', '>=', $moneys[0]);
                    } else {
                        $query->whereBetween('order_price', $moneys);
                    }
                }
            })->page($page, $limit)->order('id DESC')->select()->toArray();
    }

    /**
     * 获取采购商数量
     * @param array $where
     * @return int
     */
    public function count(array $where = []): int
    {
        $where['is_del'] = 0;
        return $this->search($where)->when(isset($where['keyword']) && $where['keyword'] !== '', function ($query) use ($where) {
            $query->where('channel_name|real_name|phone|uid', 'like', '%' . $where['keyword'] . '%');
        })->when(isset($where['verify_status']) && $where['verify_status'] !== '', function ($query) use ($where) {
            $query->where('verify_status', $where['verify_status']);
        })->when(isset($where['date']) && $where['date'] !== '', function ($query) use ($where) {
            getModelTime($query, $where['date'], 'add_time');
        })->count();
    }

    /**
     * 获取单个采购商信息
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id, string $field = '*')
    {
        return $this->getModel()->where('id', $id)->where('is_del', 0)->field($field)->find($id);
    }

    /**
     * 根据条件获取采购商信息
     * @param array $where
     * @param string $field
     * @param array $with
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelInfo(array $where, string $field = '*', array $with = [])
    {
        return $this->search($where, false)->field($field)->when(!empty($with), function ($query) use ($with) {
            $query->with($with);
        })->find();
    }


    /**
     * 根据用户ID获取采购商信息
     * @param int $uid
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelByUid(int $uid, string $field = '*')
    {
        return $this->getModel()->field($field)->where('uid', $uid)->find();
    }

    /**
     * 获取所有采购商
     * @param array $where
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAllChannel(array $where, string $field = '*')
    {
        return $this->getModel()->field($field)->where($where)->where('verify_status', 1)->select()->toArray();
    }

    /**
     * 检查采购商是否存在
     * @param int $uid
     * @param int $id
     * @return bool
     */
    public function isChannelExist(int $uid, int $id = 0)
    {
        return $this->getModel()->where('uid', $uid)->when($id > 0, function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })->count() > 0;
    }

    /**
     * 检查手机号是否已被使用
     * @param string $phone
     * @param int $id
     * @return bool
     */
    public function isPhoneExist(string $phone, int $id = 0)
    {
        return $this->getModel()->where('phone', $phone)->when($id > 0, function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })->count() > 0;
    }

    /**
     * 获取指定地区的采购商
     * @param string $province
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelByProvince(string $province, string $field = '*')
    {
        return $this->getModel()->field($field)->whereFindInSet('province', $province)->where('verify_status', 1)->select()->toArray();
    }
}
