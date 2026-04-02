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
use app\model\user\label\UserLabelExtend;

/**
 * 标签规则关联表
 * Class UserLabelExtendDao
 * @package app\dao\user\label
 */
class UserLabelExtendDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserLabelExtend::class;
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
     * 根据标签ID获取扩展规则
     * @param int $labelId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByLabelId(int $labelId): array
    {
        return $this->search(['label_id' => $labelId])->select()->toArray();
    }

    /**
     * 根据规则类型获取扩展规则
     * @param int $ruleType
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByRuleType(int $ruleType, array $where = []): array
    {
        $where['rule_type'] = $ruleType;
        return $this->search($where)->select()->toArray();
    }

    //批量删除
    public function searchDel(array $where)
    {
        return $this->search($where)->delete();
    }
}
