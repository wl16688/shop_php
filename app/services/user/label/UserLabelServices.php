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

use app\jobs\user\UserLabelJob;
use app\services\BaseServices;
use app\dao\user\label\UserLabelDao;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use crmeb\services\wechat\Work;
use FormBuilder\Factory\Iview;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 * 用户标签
 * Class UserLabelServices
 * @package app\services\user\label
 * @mixin UserLabelDao
 */
class UserLabelServices extends BaseServices
{

    /**
     * @var UserLabelDao
     */
    #[Inject]
    protected UserLabelDao $dao;

    /**
     * 获取某一本标签
     * @param $id
     * @return array|\think\Model|null
     */
    public function getLable($id)
    {
        return $this->dao->get($id);
    }

    /**
     * 获取所有用户标签
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelList(array $where = [], array $field = ['*'])
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
        $list = $this->dao->getList($page, $limit, $where, ['*'], ['category']);
        $ids = array_column($list, 'id');
        $userCount = app()->make(UserLabelRelationServices::class)->getLabelUserCount($ids ?: []);
        $count = $this->dao->count($where);
        foreach ($list as &$item) {
            $item['user_count'] = $userCount[$item['id']] ?? 0;
        }
        return compact('list', 'count');
    }


    /**
     * 添加修改标签表单
     * @param int $id
     * @param int $type
     * @param int $relation_id
     * @param int $label_cate
     * @return mixed
     */
    public function add(int $id, int $type = 0, int $relation_id = 0, int $label_cate = 0)
    {
        $label = $this->getLable($id);
        $field = array();
        /** @var UserLabelCateServices $service */
        $service = app()->make(UserLabelCateServices::class);
        $options = [];
        foreach ($service->getLabelCateAll($type, $relation_id) as $item) {
            $options[] = ['value' => $item['id'], 'label' => $item['name']];
        }
        if (!$label) {
            $title = '添加标签';
            $field[] = Form::select('label_cate', '标签分类', $label_cate)->setOptions($options)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择标签分类')->required());
            $field[] = Form::input('label_name', '标签名称', '')->maxlength(20)->required('请填写标签名称');
        } else {
            $title = '修改标签';
            $field[] = Form::select('label_cate', '分类', (int)$label->getData('label_cate'))->setOptions($options)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择标签分类')->required());
            $field[] = Form::hidden('id', $label->getData('id'));
            $field[] = Form::input('label_name', '标签名称', $label->getData('label_name'))->maxlength(20)->required('请填写标签名称');
        }
        return create_form($title, $field, Url::buildUrl('/user/user_label/save'), 'POST');
    }

    /**
     * 保存标签表单数据
     * @param int $id
     * @param array $data
     * @param int $type
     * @param int $relation_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function saveData(int $id, array $data, int $type = 0, int $relation_id = 0)
    {
        if (!$data['label_cate']) {
            throw new ValidateException('请选择标签分类');
        }

        $processedIds = [];
        $action = $id ? 'update' : 'create';

        $this->transaction(function () use ($id, $data, $type, $relation_id, &$processedIds) {
            $data['type'] = $type;
            $data['relation_id'] = $relation_id;
            unset($data['id']);
            if ($id) {
                if (!$this->getLable($id)) {
                    throw new AdminException('数据不存在');
                }
                $this->dao->update($id, $data);
                //删除旧标签
                app()->make(UserLabelExtendServices::class)->searchDel(['label_id' => $id]);
                app()->make(UserLabelExtendRelationServices::class)->search(['left_id' => $id])->delete();
                $processedIds = [$id];
            } else {
                //组合标签数据
                $processedIds = [];
                foreach ($data['label_name'] as $key => $item) {
                    $allLabelData = [
                        'label_name' => $item,
                        'label_cate' => $data['label_cate'],
                        'type' => $data['type'],
                        'relation_id' => $data['relation_id'],
                        'label_type' => $data['label_type'],
                        'is_product' => $data['is_product'],
                        'is_property' => $data['is_property'],
                        'is_trade' => $data['is_trade'],
                        'is_customer' => $data['is_customer'],
                        'is_condition' => $data['is_condition'],
                        'status' => $data['status'],
                        'add_time' => time(),
                    ];
                    $processedIds[] = $this->dao->save($allLabelData)->id;
                }
            }
            //批量保存
            $this->saveExtend($processedIds, $data);
            return true;
        });

        // 如果是自动标签，触发批量用户处理
        if (!empty($processedIds) && isset($data['label_type']) && $data['label_type'] == 2) {
            try {
                /** @var UserLabelBatchProcessServices $batchProcessServices */
                $batchProcessServices = app()->make(UserLabelBatchProcessServices::class);
                $batchProcessServices->processLabelChange($processedIds, $action);
            } catch (\Throwable $e) {
                // 记录错误但不影响标签保存
                \think\facade\Log::error('触发标签批量处理失败', [
                    'label_ids' => $processedIds,
                    'action' => $action,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return true;
    }

    public function getInfo($id)
    {
        $info = $this->getLable($id);
        if (!$info) {
            throw new AdminException('数据不存在');
        }
        $info = $info->toArray();
        $userLabelExtendServices = app()->make(UserLabelExtendServices::class);
        $extendList = $userLabelExtendServices->getLabelExtendList(['label_id' => $id]);
//        getLabelSpecify
        $specifyId = 0;
        foreach ($extendList as &$item) {
            switch ($item['rule_type']) {
                case 1:
                    $specifyId = $item['id'];
                    $product_ids = $item['product_ids'] ? explode(',', $item['product_ids']) : [];
                    $info['product'] = [
                        'specify_dimension' => $item['specify_dimension'],
                        'time_dimension' => $item['time_dimension'],
                        'time_value' => $item['time_value'],
                        'ids' => $product_ids,
                    ];
                    break;
                case 2:
                    $info['property']['property_rule'][] = $item;
                    break;
                case 3:
                    $info['trade']['trade_rule'][] = $item;
                    break;
                case 4:
                    if ($item['customer_time_start']) {
                        $item['customer_time_start'] = date('Y-m-d', $item['customer_time_start']);
                    }
                    if ($item['customer_time_end']) {
                        $item['customer_time_end'] = date('Y-m-d', $item['customer_time_end']);
                    }
                    $item['time_val'] = [$item['customer_time_start'], $item['customer_time_end']];
                    $info['customer']['customer_rule'][] = $item;
                    break;
            }
        }
        $info['specifyData'] = [];
        if ($specifyId) {
            $info['specifyData'] = $userLabelExtendServices->getLabelSpecify($specifyId);
        }
        return $info;
    }


    public function saveExtend(array $ids, array $data)
    {
        if (count($ids) == 0 || count($data) == 0) {
            return true;
        }
        $extendData = [];
        $relationData = [];
        foreach ($ids as $id) {
            //组合商品数据
            if ($data['is_product'] == 1) {
                $product = $data['product'];
                foreach ($product['ids'] as $productId) {
                    $relationData[] = [
                        'type' => $product['specify_dimension'],
                        'left_id' => $id,
                        'right_id' => $productId,
                    ];
                }
                $extendData[] = [
                    'label_id' => $id,
                    'rule_type' => 1,
                    'time_dimension' => $product['time_dimension'],
                    'time_value' => $product['time_value'],
                    'specify_dimension' => $product['specify_dimension'],
                    'product_ids' => $product['ids'] ? implode(',', $product['ids']) : '',
                ];
            }
            //资产
            if ($data['is_property'] == 1) {
                $property = $data['property'];
                foreach ($property['property_rule'] as $item) {
                    $extendData[] = [
                        'label_id' => $id,
                        'rule_type' => 2,
                        'sub_type' => $item['sub_type'],
                        'balance_type' => $item['balance_type'],
                        'operation_type' => $item['operation_type'],
                        'time_dimension' => $item['time_dimension'],
                        'amount_value_max' => $item['amount_value_max'],
                        'amount_value_min' => $item['amount_value_min'],
                        'operation_times_max' => $item['operation_times_max'],
                        'operation_times_min' => $item['operation_times_min'],
                    ];
                }

            }
            //交易
            if ($data['is_trade'] == 1) {
                $trade_rule = $data['trade']['trade_rule'];
                foreach ($trade_rule as $item) {
                    $extendData[] = [
                        'label_id' => $id,
                        'rule_type' => 3,
                        'amount_value_max' => $item['amount_value_max'],
                        'amount_value_min' => $item['amount_value_min'],
                        'operation_times_max' => $item['operation_times_max'],
                        'operation_times_min' => $item['operation_times_min'],
                        'operation_type' => $item['operation_type'],
                        'time_dimension' => $item['time_dimension'],
                    ];
                }
            }
            //客户
            if ($data['is_customer'] == 1) {
                $customer_rule = $data['customer']['customer_rule'];
                foreach ($customer_rule as $item) {
                    $time_val = $item['time_val'];
                    $customer_time_start = isset($item['time_val'][0]) && $item['time_val'][0] ? strtotime($time_val[0]) : 0;
                    $customer_time_end = isset($item['time_val'][1]) && $item['time_val'][1] ? strtotime($time_val[1]) + 86399 : 0;
                    $extendData[] = [
                        'label_id' => $id,
                        'rule_type' => 4,
                        'customer_identity' => $item['customer_identity'],
                        'customer_num' => $item['customer_num'],
                        'customer_time_start' => $customer_time_start,
                        'customer_time_end' => $customer_time_end,
                    ];
                }
            }
        }
        foreach ($extendData as $v) {
            app()->make(UserLabelExtendServices::class)->save($v);
        }

        if (count($relationData) > 0) {
            app()->make(UserLabelExtendRelationServices::class)->saveAll($relationData);
        }
        return true;
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delLabel(int $id)
    {
        if ($this->getLable($id)) {
            //删除旧标签
            app()->make(UserLabelExtendServices::class)->searchDel(['label_id' => $id]);
            app()->make(UserLabelExtendRelationServices::class)->search(['left_id' => $id])->delete();
            app()->make(UserLabelRelationServices::class)->search(['label_id' => $id])->delete();
            if (!$this->dao->delete($id)) {
                throw new AdminException('删除失败,请稍候再试!');
            }
        }
        return true;
    }

    /**
     * 同步标签
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authWorkClientLabel()
    {
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $data = $cateService->getLabelList(['group' => 0, 'owner_id' => 0]);
        if ($data['list']) {
            foreach ($data['list'] as $item) {
                UserLabelJob::dispatchDo('authLabel', [$item['id'], $item['name']]);
            }
        }
        UserLabelJob::dispatchSece(count($data['list']) + 1, 'authWorkLabel');
        return true;
    }

    /**
     * 同步平台标签到企业微信客户
     * @param int $cateId
     * @param string $groupName
     * @return bool
     */
    public function addCorpClientLabel(int $cateId, string $groupName)
    {
        try {
            $list = $this->dao->getList(0, 0, ['not_tag_id' => 1, 'type' => 0, 'label_cate' => $cateId], ['label_name as name', 'id']);
            if (!$list) {
                return true;
            }
            $data = [];
            foreach ($list as $item) {
                $data[] = ['name' => $item['name']];
            }

            $res = Work::addCorpTag($groupName, $data);
            /** @var UserLabelCateServices $categoryService */
            $categoryService = app()->make(UserLabelCateServices::class);
            $categoryService->update($cateId, ['other' => $res['tag_group']['group_id']]);
            foreach ($res['tag_group']['tag'] ?? [] as $item) {
                foreach ($list as $value) {
                    if ($item['name'] == $value['name']) {
                        $this->dao->update($value['id'], ['tag_id' => $item['id']]);
                    }
                }
            }

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * 客户标签同步到平台
     * @param array $tagIds
     * @param array $group
     * @return bool
     */
    public function authWorkLabel(array $tagIds = [], array $group = [])
    {
        $res = Work::getCorpTags($tagIds, $group);
        $tagGroup = $res['tag_group'] ?? [];
        $cateData = [];
        $labelData = [];
        $groupIds = [];
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $this->transaction(function () use ($tagGroup, $cateData, $cateService, $labelData, $groupIds) {
            foreach ($tagGroup as $item) {
                if ($id = $cateService->value(['other' => $item['group_id']], 'id')) {
                    $cateService->update(['id' => $id], ['name' => $item['group_name'], 'other' => $item['group_id'], 'sort' => $item['order']]);
                } else {
                    $cateData[] = [
                        'name' => $item['group_name'],
                        'sort' => $item['order'],
                        'add_time' => $item['create_time'],
                        'other' => $item['group_id'],
                        'group' => 0
                    ];
                }
                $groupIds[] = $item['group_id'];
                foreach ($item['tag'] as $tag) {
                    if ($labelId = $this->dao->value(['tag_id' => $tag['id']], 'id')) {
                        $this->dao->update($labelId, ['tag_id' => $tag['id']]);
                    } else {
                        $labelData[$item['group_id']][] = [
                            'label_name' => $tag['name'],
                            'type' => 0,
                            'tag_id' => $tag['id'],
                        ];
                    }
                }
            }
            if ($cateData) {
                $cateService->saveAll($cateData);
            }
            $cateIds = $cateService->getColumn([
                ['other', 'in', $groupIds],
                ['type', '=', 0],
                ['owner_id', '=', 0],
                ['group', '=', 0],
            ], 'id', 'other');
            if ($labelData) {
                $saveData = [];
                foreach ($labelData as $groupId => $labels) {
                    $cateId = $cateIds[$groupId];
                    foreach ($labels as $label) {
                        $label['label_cate'] = $cateId;
                        $saveData[] = $label;
                    }
                }
                $this->dao->saveAll($saveData);
            }
        });
        $cateService->deleteCateCache();
        return true;
    }

    /**
     * 获取同步企业微信的标签数据
     * @return array
     */
    public function getWorkLabel()
    {
        /** @var UserLabelCateServices $cateService */
        $cateService = app()->make(UserLabelCateServices::class);
        $list = $cateService->getLabelTree(['type' => 0, 'owner_id' => 0, 'group' => 0, 'other' => true], ['name', 'id', 'other as value'], [
            'label' => function ($query) {
                $query->where('tag_id', '<>', '')->where('type', 0)->field(['id', 'label_cate', 'tag_id as value', 'label_name as label']);
            }
        ]);
        foreach ($list as &$item) {
            $label = $item['label'];
            $item['children'] = $label;
            unset($item['label']);
            $item['label'] = $item['name'];
        }
        return $list;
    }

    /**
     * 企业微信创建客户标签事件
     * @param string $corpId
     * @param string $strId
     * @param string $type
     * @return bool
     */
    public function createUserLabel(string $corpId, string $strId, string $type)
    {
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : []);
    }

    /**
     * 企业微信更新客户标签事件
     * @param string $corpId
     * @param string $strId
     * @param string $type
     * @return bool
     */
    public function updateUserLabel(string $corpId, string $strId, string $type)
    {
        return $this->authWorkLabel($type === 'tag' ? [$strId] : [], $type === 'tag_group' ? [$strId] : []);
    }

    /**
     * 删除标签
     * @param string $corpId
     * @param string $strId
     * @param string $type
     */
    public function deleteUserLabel(string $corpId, string $strId, string $type)
    {
        if ('tag' === $type) {
            $this->dao->delete(['tag_id' => $strId]);
        } else if ('tag_group' === $type) {
            /** @var UserLabelCateServices $cateService */
            $cateService = app()->make(UserLabelCateServices::class);
            $cateInfo = $cateService->get(['type' => 0, 'owner_id' => 0, 'group' => 0, 'other' => $strId]);
            if ($cateInfo) {
                $this->dao->delete(['label_cate' => $cateInfo->id, 'type' => 0]);
                $cateInfo->delete();
            }
        }
    }

}
