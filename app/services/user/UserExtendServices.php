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

namespace app\services\user;

use app\dao\user\UserCardDao;
use app\dao\user\UserExtendDao;
use app\services\BaseServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\facade\Route as Url;

/**
 * 用户扩展数据服务
 * Class UserExtendServices
 * @package app\services\user
 * @mixin UserExtendDao
 */
class UserExtendServices extends BaseServices
{

    /**
     * @var UserExtendDao
     */
    #[Inject]
    protected UserExtendDao $dao;



    /**
     * 获取用户扩展数据列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where = []): array
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($where, '*', $page, $limit);
        $count = $this->dao->count($where);

        return compact('list', 'count');
    }

    /**
     * 获取用户扩展数据
     * @param int $uid
     * @param string $fieldName
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserExtendValue(int $uid, string $fieldName): string
    {
        $data = $this->dao->getUserFieldData($uid, $fieldName);
        return $data ? $data['field_value'] : '';
    }

    /**
     * 获取用户所有扩展数据
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserAllExtendData(int $uid): array
    {
        return $this->dao->getUserExtendDataKeyValue($uid);
    }

    /**
     * 设置用户扩展数据
     * @param int $uid
     * @param string $fieldName
     * @param string $fieldValue
     * @param string $remark
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setUserExtendData(int $uid, string $fieldName, string $fieldValue, string $remark = ''): bool
    {
        if (!$uid || !$fieldName) {
            throw new AdminException('参数错误');
        }

        return $this->dao->setUserExtendData($uid, $fieldName, $fieldValue, $remark) !== false;
    }

    /**
     * 批量设置用户扩展数据
     * @param int $uid
     * @param array $extendData
     * @return bool
     */
    public function setBatchUserExtendData(int $uid, array $extendData): bool
    {
        if (!$uid || empty($extendData)) {
            throw new AdminException('参数错误');
        }

        return $this->dao->setBatchUserExtendData($uid, $extendData);
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
        if (!$uid) {
            throw new AdminException('参数错误');
        }

        return $this->dao->deleteUserExtendData($uid, $fieldName);
    }

    /**
     * 保存扩展数据
     * @param array $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function save(array $data): bool
    {
        if (!isset($data['uid']) || !isset($data['field_name']) || !isset($data['field_value'])) {
            throw new AdminException('参数错误');
        }

        return $this->dao->setUserExtendData(
            (int)$data['uid'],
            $data['field_name'],
            $data['field_value'],
        ) !== false;
    }
}
