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

use app\dao\activity\card\CardBatchDao;
use app\services\BaseServices;
use think\annotation\Inject;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;


/**
 * 卡次表
 * Class CardBatchServices
 * @package app\services\activity\card
 * @mixin CardBatchDao
 */
class CardBatchServices extends BaseServices
{
    /**
     * @var CardBatchDao
     */
    #[Inject]
    protected CardBatchDao $dao;

    /**
     * 获取列表和总数
     *
     * 该方法主要用于根据给定的条件获取数据列表和总记录数
     * 它封装了分页逻辑和数据获取逻辑，以简化调用
     *
     * @param array $where 查询条件数组
     * @return array 包含数据列表和总记录数的数组
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getList(array $where): array
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->selectList($where, '*', $page, $limit, 'add_time desc', true);
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 保存数据方法
     *
     * 该方法用于处理数据的保存操作，根据提供的标识$id判断是更新现有记录还是创建新记录
     * 使用事务确保数据一致性，在更新或创建过程中，涉及的操作要么全部完成，要么全部不完成
     *
     * @param int $id 要更新的记录的标识，如果为0则表示创建新记录
     * @param array $data 要保存的数据数组，包含需要更新或插入的字段和值
     *
     */
    public function saveData(array $data): bool
    {
        // 使用事务处理数据保存操作，确保数据的一致性和完整性
        return $this->transaction(function () use ($data) {
            // 如果$id不存在，则创建新记录，并在成功后处理相关的卡片信息
            $saveData = $data;
//            $saveData['total_num'] = pow(10, $data['total_num']);
            unset($saveData['pwd_type'], $saveData['pwd_num']);
            $saveData['add_time'] = time();
            $res = $this->dao->save($saveData);
            $data['batch_id'] = $res->id;
            // 调用CardCodeServices服务的addCard方法，为新创建的记录添加卡片信息
            app()->make(CardCodeServices::class)->addCard($data);
            return true;
        });
    }

    /**
     * 删除指定ID的批次记录
     * @param int $id
     * @return mixed
     * User: liusl
     * DateTime: 2025/5/9 15:48
     */
    public function del(int $id)
    {
        return $this->transaction(function () use ($id) {
            $this->update($id, ['is_del' => 1]);
            app()->make(CardCodeServices::class)->delete(['batch_id' => $id, 'card_id' => 0]);
            return true;
        });
    }

    public function getTree()
    {
        $list = $this->search(['is_del' => 0])->field('id,name,total_num,used_num')->select()->toArray();
        foreach ($list as &$item) {
            $item['remain_num'] = $item['total_num'] - $item['used_num'];
            $item['remain_num'] = max($item['remain_num'], 0);
            unset($item['total_num'], $item['used_num']);
        }
        unset($item);
        return $list;
    }

    public function allocationCode($id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            return true;
        }
        $used_num = app()->make(CardCodeServices::class)->search(['batch_id' => $id])->where('status', '<>', 0)->count();
        $info->used_num = $used_num;
        $info->save();
        return true;
    }
}
