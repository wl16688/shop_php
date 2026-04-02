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

namespace app\services\user\label;

use app\services\BaseServices;
use app\dao\user\label\UserLabelExtendRelationDao;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use FormBuilder\Factory\Iview;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 标签规则扩展表
 * Class UserLabelExtendRelationServices
 * @package app\services\user\label
 * @mixin UserLabelExtendRelationDao
 */
class UserLabelExtendRelationServices extends BaseServices
{

    /**
     * @var UserLabelExtendRelationDao
     */
    #[Inject]
    protected UserLabelExtendRelationDao $dao;

    /**
     * 获取某一条标签扩展关联关系
     * @param $id
     * @return array|\think\Model|null
     */
    public function getLabelExtendRelation($id)
    {
        return $this->dao->get($id);
    }

    /**
     * 获取所有标签扩展关联关系
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelExtendRelationList(array $where = [], array $field = ['*'])
    {
        return $this->dao->getList(0, 0, $where, $field);
    }

    /**
     * 获取列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getList($page, $limit, $where);
        $count = $this->dao->count($where);
        return compact('list', 'count');
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
        return $this->dao->getByLeftId($leftId, $type);
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
        return $this->dao->getByRightId($rightId, $type);
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
        return $this->dao->getByType($type, $where);
    }

    /**
     * 添加修改标签扩展关联关系表单
     * @param int $id
     * @param int $leftId
     * @return mixed
     */
    public function add(int $id, int $leftId = 0)
    {
        $labelExtendRelation = $this->getLabelExtendRelation($id);
        $field = array();

        // 类型选项
        $typeOptions = [
            ['value' => 1, 'label' => '商品'],
            ['value' => 2, 'label' => '分类'],
            ['value' => 3, 'label' => '标签']
        ];

        /** @var UserLabelExtendServices $labelExtendService */
        $labelExtendService = app()->make(UserLabelExtendServices::class);
        $labelExtendOptions = [];
        foreach ($labelExtendService->getLabelExtendList() as $item) {
            $labelExtendOptions[] = ['value' => $item['id'], 'label' => '标签扩展规则-' . $item['id']];
        }

        if (!$labelExtendRelation) {
            $title = '添加标签扩展关联关系';
            $field[] = Form::select('left_id', '关联标签扩展', $leftId)->setOptions($labelExtendOptions)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择关联标签扩展')->required());
        } else {
            $title = '编辑标签扩展关联关系';
            $field[] = Form::select('left_id', '关联标签扩展', $labelExtendRelation['left_id'])->setOptions($labelExtendOptions)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择关联标签扩展')->required());
        }

        $field[] = Form::select('type', '类型', $labelExtendRelation['type'] ?? 0)->setOptions($typeOptions)->appendValidate(Iview::validateInt()->message('请选择类型')->required());
        $field[] = Form::number('right_id', '关联ID', $labelExtendRelation['right_id'] ?? 0)->appendValidate(Iview::validateInt()->message('请输入关联ID')->required());

        return create_form($title, $field, '/admin/user/label_extend_relation/save/' . $id, 'POST');
    }

    /**
     * 保存标签扩展关联关系
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function save(int $id, array $data)
    {
        if ($id) {
            $res = $this->dao->update($id, $data);
            if (!$res) throw new AdminException('修改失败');
        } else {
            $res = $this->dao->save($data);
            if (!$res) throw new AdminException('添加失败');
        }
        return $res;
    }

    /**
     * 删除标签扩展关联关系
     * @param int $id
     * @return bool
     */
    public function del(int $id)
    {
        $labelExtendRelation = $this->getLabelExtendRelation($id);
        if (!$labelExtendRelation) {
            throw new AdminException('标签扩展关联关系不存在');
        }
        return $this->dao->delete($id);
    }

    /**
     * 批量保存关联关系
     * @param int $leftId
     * @param int $type
     * @param array $rightIds
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveBatch(int $leftId, int $type, array $rightIds): bool
    {
        // 先删除原有关联关系
        $this->dao->deleteByLeftId($leftId, $type);

        // 批量保存新的关联关系
        if (!empty($rightIds)) {
            $data = [];
            foreach ($rightIds as $rightId) {
                $data[] = [
                    'type' => $type,
                    'left_id' => $leftId,
                    'right_id' => $rightId
                ];
            }
            return $this->dao->saveAll($data);
        }

        return true;
    }

    /**
     * 获取关联的右侧ID列表
     * @param int $leftId
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getRightIds(int $leftId, int $type = 0): array
    {
        $list = $this->getByLeftId($leftId, $type);
        return array_column($list, 'right_id');
    }

    /**
     * 获取关联的左侧ID列表
     * @param int $rightId
     * @param int $type
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLeftIds(int $rightId, int $type = 0): array
    {
        $list = $this->getByRightId($rightId, $type);
        return array_column($list, 'left_id');
    }
}
