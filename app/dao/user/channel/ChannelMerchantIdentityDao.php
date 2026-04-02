<?php
declare (strict_types=1);

namespace app\dao\user\channel;

use app\dao\BaseDao;
use app\model\user\channel\ChannelMerchantIdentity;

/**
 * 采购商身份DAO
 * Class ChannelMerchantIdentityDao
 * @package app\dao\user\channel
 */
class ChannelMerchantIdentityDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return ChannelMerchantIdentity::class;
    }

    /**
     * 获取采购商身份列表
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(array $where, int $page, int $limit)
    {
        return $this->search($where,false)
            ->page($page, $limit)
            ->order('id DESC')
            ->select()
            ->toArray();
    }

    /**
     * 获取单个采购商身份信息
     * @param int $id
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getInfo(int $id, string $field = '*')
    {
        return $this->getModel()->field($field)->find($id);
    }

    /**
     * 根据等级获取采购商身份
     * @param int $level
     * @param string $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getIdentityByLevel(int $level, string $field = '*')
    {
        return $this->getModel()->field($field)->where('level', $level)->where('is_show', 1)->find();
    }

    /**
     * 检查等级是否已存在
     * @param int $level
     * @param int $id
     * @return bool
     */
    public function isLevelExist(int $level, int $id = 0)
    {
        return $this->getModel()->where('level', $level)
            ->when($id > 0, function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->count() > 0;
    }

    /**
     * 检查名称是否已存在
     * @param string $name
     * @param int $id
     * @return bool
     */
    public function isNameExist(string $name, int $id = 0)
    {
        return $this->getModel()->where('name', $name)
            ->when($id > 0, function ($query) use ($id) {
                $query->where('id', '<>', $id);
            })
            ->count() > 0;
    }
}
