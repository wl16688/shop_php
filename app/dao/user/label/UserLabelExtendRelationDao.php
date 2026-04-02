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

namespace app\dao\user\label;

use app\dao\BaseDao;
use app\model\user\label\UserLabelExtendRelation;

/**
 * 标签规则扩展表
 * Class UserLabelExtendRelationDao
 * @package app\dao\user\label
 */
class UserLabelExtendRelationDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserLabelExtendRelation::class;
    }

    /**
     * 获取列表
     * @param int $page
     * @param int $limit
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(int $page = 0, int $limit = 0, array $where = [], array $field = ['*']): array
    {
        return $this->search($where)->when($page && $limit, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->field($field)->order('id desc')->select()->toArray();
    }

    /**
     * 根据左侧ID获取关联关系
     * @param int $leftId
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByLeftId(int $leftId, int $type = 0): array
    {
        $where = ['left_id' => $leftId];
        if ($type) {
            $where['type'] = $type;
        }
        return $this->search($where)->select()->toArray();
    }

    /**
     * 根据右侧ID获取关联关系
     * @param int $rightId
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByRightId(int $rightId, int $type = 0): array
    {
        $where = ['right_id' => $rightId];
        if ($type) {
            $where['type'] = $type;
        }
        return $this->search($where)->select()->toArray();
    }

    /**
     * 根据类型获取关联关系
     * @param int $type
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByType(int $type, array $where = []): array
    {
        $where['type'] = $type;
        return $this->search($where)->select()->toArray();
    }

    /**
     * 批量删除关联关系
     * @param int $leftId
     * @param int $type
     * @return bool
     */
    public function deleteByLeftId(int $leftId, int $type = 0): bool
    {
        $where = ['left_id' => $leftId];
        if ($type) {
            $where['type'] = $type;
        }
        return $this->getModel()->where($where)->delete();
    }

}
