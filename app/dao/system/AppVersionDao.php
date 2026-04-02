<?php

namespace app\dao\system;

use app\dao\BaseDao;
use app\model\system\AppVersion;

class AppVersionDao extends BaseDao
{
    /**
     * 设置模型名
     * @return string
     */
    protected function setModel(): string
    {
        return AppVersion::class;
    }

    /**
     * 获取条件模型
     * @param $where
     * @return \crmeb\basic\BaseModel
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/7/4
     */
    public function getConditionModel($where)
    {
        return $this->getModel()->when($where['platform'] != '', function ($query) use ($where) {
            $query->where('platform', $where['platform']);
        });
    }

    /**
     * 版本列表
     * @param $where
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/7/4
     */
    public function versionList($where, $page = 0, $limit = 0, $order = 'id desc')
    {
        return $this->getConditionModel($where)->order('id desc')->when($page != 0, function ($query) use ($page, $limit) {
            $query->page($page, $limit);
        })->order($order)->select()->toArray();
    }

    /**
     * 获取版本数量
     * @param $where
     * @return int
     * @throws \think\db\exception\DbException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/7/4
     */
    public function versionCount($where)
    {
        return $this->getConditionModel($where)->count();
    }
}
