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

namespace app\dao\user;

use app\dao\BaseDao;
use app\model\user\UserExtend;

/**
 * 用户扩展数据DAO
 * Class UserExtendDao
 * @package app\dao\user
 */
class UserExtendDao extends BaseDao
{
    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return UserExtend::class;
    }

    /**
     * 获取用户扩展数据列表
     * @param array $where
     * @param string $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = [], string $field = '*', int $page = 0, int $limit = 0, string $order = 'id desc'): array
    {
        return $this->search($where)->field($field)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })->order($order)->select()->toArray();
    }

    /**
     * 获取用户某个字段的扩展数据
     * @param int $uid
     * @param string $fieldName
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserFieldData(int $uid, string $fieldName)
    {
        return $this->search(['uid' => $uid, 'field_name' => $fieldName])->find();
    }

    /**
     * 获取用户所有扩展数据
     * @param int $uid
     * @param string $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllExtendData(int $uid, string $field = '*'): array
    {
        return $this->search(['uid' => $uid])->field($field)->select()->toArray();
    }

    /**
     * 设置用户扩展数据
     * @param int $uid
     * @param string $fieldName
     * @param string $fieldValue
     * @param string $remark
     * @return bool|\crmeb\basic\BaseModel|int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserExtendData(int $uid, string $fieldName,  $fieldValue,  $remark = '')
    {
        $existData = $this->getUserFieldData($uid, $fieldName);

        $data = [
            'uid' => $uid,
            'field_name' => $fieldName,
            'field_value' => $fieldValue,
        ];

        if ($existData) {
            // 更新现有数据
            return $this->update($existData['id'], $data);
        } else {
            // 创建新数据
            return $this->save($data);
        }
    }

    /**
     * 删除用户扩展数据
     * @param int $uid
     * @param string $fieldName
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteUserExtendData(int $uid, string $fieldName = ''): bool
    {
        $where = ['uid' => $uid];
        if ($fieldName) {
            $where['field_name'] = $fieldName;
        }

        return $this->delete($where) !== false;
    }

    /**
     * 批量设置用户扩展数据
     * @param int $uid
     * @param array $extendData
     * @return bool
     */
    public function setBatchUserExtendData(int $uid, array $extendData): bool
    {
        try {
            foreach ($extendData as $item) {
                $this->setUserExtendData($uid, $item['field_name'], $item['field_value']);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取用户扩展数据键值对
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserExtendDataKeyValue(int $uid): array
    {
        $data = $this->getUserAllExtendData($uid, 'field_name,field_value');
        $result = [];
        foreach ($data as $item) {
            $result[$item['field_name']] = $item['field_value'];
        }
        return $result;
    }

    //验证是否存在
    public function isExist(string $field_name,$value, $uid = null)
    {
        return !!$this->getModel()->where('field_name', $field_name)->where('field_value', $value)
            ->when($uid, function ($query) use ($uid) {
                $query->where('uid', '<>',$uid);
            })->find();
    }
}
