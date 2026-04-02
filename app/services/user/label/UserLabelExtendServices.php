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
use app\dao\user\label\UserLabelExtendDao;
use app\services\product\category\StoreProductCategoryServices;
use app\services\product\label\StoreProductLabelServices;
use app\services\product\product\StoreProductServices;
use crmeb\exceptions\AdminException;
use crmeb\services\FormBuilder as Form;
use FormBuilder\Factory\Iview;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 标签规则关联表
 * Class UserLabelExtendServices
 * @package app\services\user\label
 * @mixin UserLabelExtendDao
 */
class UserLabelExtendServices extends BaseServices
{

    /**
     * @var UserLabelExtendDao
     */
    #[Inject]
    protected UserLabelExtendDao $dao;

    /**
     * 获取某一条标签扩展规则
     * @param $id
     * @return array|\think\Model|null
     */
    public function getLabelExtend($id)
    {
        return $this->dao->get($id);
    }

    /**
     * 获取所有标签扩展规则
     * @param array $where
     * @param array|string[] $field
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getLabelExtendList(array $where = [], array $field = ['*'])
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
     * 根据标签ID获取扩展规则
     * @param int $labelId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getByLabelId(int $labelId): array
    {
        return $this->dao->getByLabelId($labelId);
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
        return $this->dao->getByRuleType($ruleType, $where);
    }

    /**
     * 添加修改标签扩展规则表单
     * @param int $id
     * @param int $labelId
     * @return mixed
     */
    public function add(int $id, int $labelId = 0)
    {
        $labelExtend = $this->getLabelExtend($id);
        $field = array();

        /** @var UserLabelServices $labelService */
        $labelService = app()->make(UserLabelServices::class);
        $labelOptions = [];
        foreach ($labelService->getLabelList() as $item) {
            $labelOptions[] = ['value' => $item['id'], 'label' => $item['label_name']];
        }

        // 规则类型选项
        $ruleTypeOptions = [
            ['value' => 1, 'label' => '商品'],
            ['value' => 2, 'label' => '资产'],
            ['value' => 3, 'label' => '交易'],
            ['value' => 4, 'label' => '客户(所有)']
        ];

        // 规则子类型选项
        $subTypeOptions = [
            ['value' => 1, 'label' => '积分'],
            ['value' => 2, 'label' => '余额']
        ];

        // 余额类型选项
        $balanceTypeOptions = [
            ['value' => 1, 'label' => '充值'],
            ['value' => 2, 'label' => '消耗']
        ];

        // 次数/金额选项
        $operationTypeOptions = [
            ['value' => 1, 'label' => '次数'],
            ['value' => 2, 'label' => '金额']
        ];

        // 时间维度选项
        $timeDimensionOptions = [
            ['value' => 1, 'label' => '历史'],
            ['value' => 2, 'label' => '最近'],
            ['value' => 3, 'label' => '累计']
        ];

        // 商品维度选项
        $specifyDimensionOptions = [
            ['value' => 1, 'label' => '商品'],
            ['value' => 2, 'label' => '分类'],
            ['value' => 3, 'label' => '标签']
        ];

        // 客户身份选项
        $customerIdentityOptions = [
            ['value' => 1, 'label' => '注册时间'],
            ['value' => 2, 'label' => '访问时间'],
            ['value' => 3, 'label' => '用户等级'],
            ['value' => 4, 'label' => '客户身份']
        ];

        if (!$labelExtend) {
            $title = '添加标签扩展规则';
            $field[] = Form::select('label_id', '关联标签', $labelId)->setOptions($labelOptions)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择关联标签')->required());
        } else {
            $title = '编辑标签扩展规则';
            $field[] = Form::select('label_id', '关联标签', $labelExtend['label_id'])->setOptions($labelOptions)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择关联标签')->required());
        }

        $field[] = Form::select('rule_type', '规则类型', $labelExtend['rule_type'] ?? 0)->setOptions($ruleTypeOptions)->appendValidate(Iview::validateInt()->message('请选择规则类型')->required());
        $field[] = Form::select('sub_type', '规则子类型', $labelExtend['sub_type'] ?? 0)->setOptions($subTypeOptions);
        $field[] = Form::select('balance_type', '余额类型', $labelExtend['balance_type'] ?? 0)->setOptions($balanceTypeOptions);
        $field[] = Form::select('operation_type', '次数/金额', $labelExtend['operation_type'] ?? 0)->setOptions($operationTypeOptions);
        $field[] = Form::select('time_dimension', '时间维度', $labelExtend['time_dimension'] ?? 0)->setOptions($timeDimensionOptions);
        $field[] = Form::number('time_value', '时间值（天）', $labelExtend['time_value'] ?? 0);
        $field[] = Form::select('specify_dimension', '商品维度', $labelExtend['specify_dimension'] ?? 0)->setOptions($specifyDimensionOptions);
        $field[] = Form::number('amount_value_min', '金额/数值最小', $labelExtend['amount_value_min'] ?? 0)->precision(2);
        $field[] = Form::number('amount_value_max', '金额/数值最大', $labelExtend['amount_value_max'] ?? 0)->precision(2);
        $field[] = Form::number('operation_times_min', '操作次数最小', $labelExtend['operation_times_min'] ?? 0);
        $field[] = Form::number('operation_times_max', '操作次数最大', $labelExtend['operation_times_max'] ?? 0);
        $field[] = Form::select('customer_identity', '客户身份', $labelExtend['customer_identity'] ?? 0)->setOptions($customerIdentityOptions);
        $field[] = Form::number('customer_num', '客户身份数据', $labelExtend['customer_num'] ?? 0);

        return create_form($title, $field, '/admin/user/label_extend/save/' . $id, 'POST');
    }

    /**
     * 删除标签扩展规则
     * @param int $id
     * @return bool
     */
    public function del(int $id)
    {
        $labelExtend = $this->getLabelExtend($id);
        if (!$labelExtend) {
            throw new AdminException('标签扩展规则不存在');
        }
        return $this->dao->delete($id);
    }


    //根据specify_dimension商品维度获取商品分类标签商品信息
    public function getLabelSpecify(int $id)
    {
        $labelExtend = $this->getLabelExtend($id);
        if (!$labelExtend) {
            return [];
        }
        $product_ids = $labelExtend['product_ids'] ? explode(',', $labelExtend['product_ids']) : [];
        $data = [];
        switch ($labelExtend['specify_dimension']) {
            case 1:
                //商品
                $data = app()->make(StoreProductServices::class)->search([])->whereIn('id', $product_ids)->column('id,store_name,image');
                break;
            case 2:
                //分类
                $data = app()->make(StoreProductCategoryServices::class)->search([])->whereIn('id', $product_ids)->column('id,cate_name');
                break;
            case 3:
                //标签
                $data = app()->make(StoreProductLabelServices::class)->search([])->whereIn('id', $product_ids)->column('id,label_name');
                break;
        }
        return $data;
    }
}
