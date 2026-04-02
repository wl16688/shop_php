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

namespace app\services\activity\card;

use app\dao\activity\card\CardGiftDao;
use app\jobs\activity\card\CardJob;
use app\services\BaseServices;
use app\services\product\product\StoreDescriptionServices;
use app\services\product\product\StoreProductServices;
use app\services\product\sku\StoreProductAttrValueServices;
use crmeb\services\FormBuilder as Form;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;


/**
 * 礼品卡
 * Class CardGiftServices
 * @package app\services\activity\card
 * @mixin CardGiftDao
 */
class CardGiftServices extends BaseServices
{

    /**
     * @var CardGiftDao
     */
    #[Inject]
    protected CardGiftDao $dao;

    /**
     * 列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:37
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->getList($where, '*', $page, $limit, 'sort desc,add_time desc');
        $count = $this->dao->count($where);
        $bIds = array_column($list, 'batch_id');
        $batchList = app()->make(CardBatchServices::class)->search(['id', $bIds])->column('name', 'id');
        foreach ($list as &$item) {
            $item['batch_name'] = $batchList[$item['batch_id']] ?? '';
            $item['fixed_time'] = [
                $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '',
                $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '',
            ];
        }
        return compact('list', 'count');
    }

    /**
     * 添加
     * @param array $data
     * @param int $id
     * @return true
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:46
     */
    public function saveData(array $data, int $id = 0, int $admin_id = 0)
    {
        if ($id && !$info = $this->dao->get($id)) {
            throw new ValidateException('礼品卡不存在');
        }
        //验证卡密未使用的数量是否超过礼品卡填写的总量
        if (!$id) {
            $codeCount = app()->make(CardCodeServices::class)->count(['batch_id' => $data['batch_id'], 'status' => 0, 'card_id' => 0]);
            if ($codeCount < $data['total_num']) {
                throw new ValidateException('卡密未使用的数量不能超过礼品卡填写的总量');
            }
        }

        if ($data['valid_type'] == 2) {
            $data['start_time'] = isset($data['fixed_time'][0]) ? strtotime($data['fixed_time'][0]) : 0;
            $data['end_time'] = isset($data['fixed_time'][1]) ? strtotime($data['fixed_time'][1]) : 0;
        }
        unset($data['fixed_time']);
        $this->transaction(function () use ($id, $data, $admin_id) {
            $product = $data['product'];
            $description = $data['description'];
            unset($data['product'], $data['description']);
            $data['add_time'] = time();
            if ($id) {
                $this->dao->update($id, $data);
            } else {
                $res = $this->dao->save($data);
                $id = $res->id;
                //修改卡密
                $this->saveCode($id, $data, $admin_id);

            }
            app()->make(StoreDescriptionServices::class)->saveDescription($id, $description, 5);
            //保存礼品卡商品
            $this->saveProduct($id, $product);
            return true;
        });
        $batch_id = $id ? $info['batch_id'] : $data['batch_id'];
        //校验卡密使用数量
        CardJob::dispatchDo('allocationCode', [$batch_id]);
        return true;
    }

    /**
     * 修改卡密
     * @param int $id
     * @param array $data
     * @return true
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:46
     */
    public function saveCode(int $id, array $data, int $admin_id = 0)
    {
        $cardCodeServices = app()->make(CardCodeServices::class);
        $list = $cardCodeServices->search(['batch_id' => $data['batch_id'], 'status' => 0, 'card_id' => 0])->order('id asc')->limit($data['total_num'])->select()->toArray();
        if (!$list) {
            throw new ValidateException('没有可操作的卡密');
        }
        $ids = [];
        $updateData = [
            'type' => $data['type'],
            'end_time' => $data['end_time'] ?? 0,
            'card_id' => $id,
            'status' => 2,
        ];
        $str = '';
        $i = $list[0]['id'];
        $status = true;
        foreach ($list as $k => $item) {
            if ($k == 0) {
                $str .= $item['card_number'] . '--';
            } elseif ($k == count($list) - 1) {
                //获取列表最后一条数据
                $str .= $item['card_number'];
            } else {
                if ($item['id'] != $i) {
                    if ($status) {
                        $str .= $item['card_number'] . ',';
                        $status = false;
                    } else {
                        $str .= $item['card_number'] . '--';
                        $status = true;
                    }
                }
            }
            $i = $item['id'] + 1;
            $ids[] = $item['id'];
            // 每满100条插入一次并清空缓存
            if (count($ids) >= 500) {
                $cardCodeServices->batchUpdate($ids, $updateData);
                $ids = [];
            }
        }
        if (count($ids)) {
            $cardCodeServices->batchUpdate($ids, $updateData);
        }
        app()->make(CardGiftRecordServices::class)->save([
            'gift_id' => $id,
            'record' => $str,
            'num' => count($list),
            'pm' => 1,
            'add_time' => time(),
            'admin_id' => $admin_id
        ]);

        return true;
    }

    /**
     * 新增商品
     * @param int $id
     * @param array $productData
     * @return true
     * User: liusl
     * DateTime: 2025/5/12 09:45
     */
    public function saveProduct(int $id, array $productData)
    {
        $auxiliary = app()->make(CardGiftAuxiliaryServices::class);
        $auxiliary->delete(['gift_id' => $id]);
        $saveData = [];
        foreach ($productData as $product) {
            $saveData[] = [
                'gift_id' => $id,
                'product_id' => $product['product_id'] ?? 0,
                'limit_num' => $product['limit_num'] ?? 0,
                'unique' => $product['unique'] ?? '',
            ];
        }
        if (count($saveData)) {
            $auxiliary->saveAll($saveData);
        }
        return true;
    }

    /**
     * 增加卡密表单
     * @param int $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 15:41
     */
    public function otherForm(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('礼品卡不存在');
        }
        $info = $info->toArray();
        $batchInfo = app()->make(CardBatchServices::class)->get($info['batch_id']);
        $remain_num = $batchInfo['total_num'] - $batchInfo['used_num'];
        $remain_num = max($remain_num, 0);
        $batchName = $batchInfo['name'] . "({$remain_num})";
        $field[] = Form::input('batch_name', '卡号批次', $batchName)->disabled(true);
        $field[] = Form::radio('type', '修改数量', $formData['status'] ?? 1)->options([
            ['label' => '增加', 'value' => 1],
            ['label' => '减少', 'value' => 2],
        ]);
        $field[] = Form::number('number', '数量', 0)->min(0);
        return create_form('修改数量', $field, Url::buildUrl('/marketing/card/gift/update_other/' . $id), 'POST');
    }

    /**
     * 增加卡密提交
     * @param int $id
     * @param int $type
     * @param int $number
     * @return true
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 15:42
     */
    public function updateOther(int $id, int $type, int $number, int $admin_id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('礼品卡不存在');
        }
        if ($type == 1) {
            $total_num = $info->total_num + $number;
            //增加
            $count = app()->make(CardCodeServices::class)->count(['batch_id' => $info['batch_id'], 'status' => 0, 'card_id' => 0]);
            if ($count < $number) {
                throw new ValidateException('卡密不足');
            }
            $this->saveCode($id, ['total_num' => $number, 'batch_id' => $info['batch_id'], 'type' => $info['type']], $admin_id);
        } else {
            $total_num = $info->total_num - $number;
            //减少
            $count = app()->make(CardCodeServices::class)->count(['batch_id' => $info['batch_id'], 'status' => 2, 'card_id' => $id]);
            if ($count < $number) {
                throw new ValidateException('未卡密不足,无法减少,未使用卡密:' . $count);
            }
            $cardCodeServices = app()->make(CardCodeServices::class);
            $list = $cardCodeServices->search(['batch_id' => $info['batch_id'], 'status' => 2, 'card_id' => $id])->order('id asc')->limit($number)->select()->toArray();
            if (!$list) {
                throw new ValidateException('没有可操作的卡密');
            }
            $ids = [];
            $updateData = [
                'type' => 0,
                'card_id' => 0,
                'end_time' => 0,
                'status' => 0,
            ];

            $str = '';
            $i = $list[0]['id'];
            $status = true;

            foreach ($list as $k => $item) {
                //检查 id 是否间断, 如果有给$str追加
                if ($k == 0) {
                    $str .= $item['card_number'] . '--';
                } elseif ($k == count($list) - 1) {
                    //获取列表最后一条数据
                    $str .= $item['card_number'];
                } else {
                    if ($item['id'] != $i) {
                        if ($status) {
                            $str .= $item['card_number'] . ',';
                            $status = false;
                        } else {
                            $str .= $item['card_number'] . '--';
                            $status = true;
                        }
                    }
                }
                $i = $item['id'] + 1;
                $ids[] = $item['id'];
                // 每满100条插入一次并清空缓存
                if (count($ids) >= 500) {
                    $cardCodeServices->batchUpdate($ids, $updateData);
                    $ids = [];
                }
            }
            if (count($ids)) {
                $cardCodeServices->batchUpdate($ids, $updateData);
            }
            app()->make(CardGiftRecordServices::class)->save([
                'gift_id' => $id,
                'record' => $str,
                'num' => count($list),
                'pm' => 2,
                'add_time' => time(),
                'admin_id' => $admin_id
            ]);
        }
        $info->total_num = $total_num;
        $info->save();
        //校验卡密使用数量
        CardJob::dispatchDo('allocationCode', [$info['batch_id']]);
        return true;
    }

    /**
     * 详情
     * @param $id
     * @return array
     * @throws \ReflectionException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 15:42
     */
    public function getInfo($id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('礼品卡不存在');
        }
        $info = $info->toArray();
        $auxiliaryList = app()->make(CardGiftAuxiliaryServices::class)->search(['gift_id' => $id])->select()->toArray();
        $info['product'] = [];
        $info['description'] = app()->make(StoreDescriptionServices::class)->getDescription(['product_id' => $id, 'type' => 5]);
        $info['fixed_time'] = [
            $info['start_time'] ? date('Y-m-d H:i:s', $info['start_time']) : '',
            $info['end_time'] ? date('Y-m-d H:i:s', $info['end_time']) : '',
        ];
        if (!$auxiliaryList) {
            return $info;
        }
        $productIds = array_unique(array_column($auxiliaryList, 'product_id'));
        $uniques = array_unique(array_column($auxiliaryList, 'unique'));
        $productList = app()->make(StoreProductServices::class)->search(['id' => $productIds])->column('store_name', 'id');
        $uniqueList = app()->make(StoreProductAttrValueServices::class)->search(['unique' => $uniques])->column('unique,suk,stock,price,image', 'unique');
        foreach ($auxiliaryList as &$item) {
            $item['store_name'] = $productList[$item['product_id']] ?? '';
            $item['suk'] = $uniqueList[$item['unique']]['suk'] ?? '';
            $item['stock'] = $uniqueList[$item['unique']]['stock'] ?? '';
            $item['price'] = $uniqueList[$item['unique']]['price'] ?? '';
            $item['image'] = $uniqueList[$item['unique']]['image'] ?? '';
        }
        $info['product'] = $auxiliaryList;
        return $info;
    }

    /**
     * 删除
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:45
     */
    public function delete($id)
    {
        //删除礼品卡,
        //删除礼品卡商品
        //删除未使用的礼品卡卡密

        $info = $this->dao->get($id);
        if (!$info) {
            throw new ValidateException('礼品卡不存在');
        }
        return $this->transaction(function () use ($id, $info) {
            $res = $this->dao->update($id, ['is_del' => 1]);
            $res = $res && app()->make(CardGiftAuxiliaryServices::class)->delete(['gift_id' => $id]);
            $res = $res && app()->make(CardCodeServices::class)->update(['card_id' => $id, 'status' => 2], ['type' => 0, 'status' => 0, 'card_id' => 0]);
            return !!$res;
        });
    }

    public function getTree()
    {
        return $this->search(['is_del' => 0])->field('id,name')->order('sort desc,add_time desc')->select()->toArray();
    }

    /**
     * 使用数量矫正
     * @return
     * User: liusl
     * DateTime: 2025/5/24 10:47
     */
    public function allocationCardGift($id)
    {
        $info = $this->dao->get($id);
        $used_num = app()->make(CardCodeServices::class)->search(['card_id' => $id, 'status' => 1])->count();
        $info->used_num = $used_num;
        $info->save();
        return true;
    }
}
