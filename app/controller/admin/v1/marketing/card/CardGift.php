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

namespace app\controller\admin\v1\marketing\card;

use app\controller\admin\AuthController;
use app\services\activity\card\CardGiftRecordServices;
use app\services\activity\card\CardGiftServices;
use app\validate\admin\marketing\card\CardBatchValidate;
use app\validate\admin\marketing\card\CardGiftValidate;
use think\annotation\Inject;


/**
 * 礼品卡
 * Class CardGift
 * @package app\admin\controller\card
 */
class CardGift extends AuthController
{

    /**
     * @var CardGiftServices
     */
    #[Inject]
    protected CardGiftServices $services;

    /**
     * 列表
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:37
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],//礼品卡名称
            ['type', ''],//礼品卡类型 1-储值卡 2-兑换卡
            ['status', ''],//礼品卡状态 0-禁用 1-启用
            ['time', '']//添加时间
        ]);
        return $this->success($this->services->getList($where));
    }

    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],//礼品卡名称
            ['type', 0],//礼品卡类型 1-储值卡 2-兑换卡
            ['batch_id', 0],//关联卡密
            ['total_num', 0],//总数量
            ['instructions', ''],//使用须知
            ['cover_image', ''],//封面图
            ['valid_type', 0],//有效期类型 0-永久有效 1-天数 2-固定时间
            ['fixed_time', []],//有效期
            ['status', 0],//礼品卡状态 0-禁用 1-启用
            ['sort', 0],//排序
            ['balance', 0],//储值金额
            ['exchange_type', 0],//兑换商品类型 1-固定商品打包 2-任选N件商品
            ['gift_num', 0],//任选 N 件
            ['product', []],//兑换商品
            [['description', 's'], ''],//详情
        ]);
        // 验证请求数据，确保数据的合法性
        $this->validate($data, CardGiftValidate::class, 'save');
        $res = $this->services->saveData($data, 0, $this->adminId);
        if ($res) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    public function read($id)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        return $this->success($this->services->getInfo($id));
    }

    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],//礼品卡名称
            ['type', 0],//礼品卡类型 1-储值卡 2-兑换卡
            ['instructions', ''],//使用须知
            ['cover_image', ''],//封面图
            ['valid_type', 0],//有效期类型 0-永久有效 1-固定时间
            ['fixed_time', []],//有效期
            ['status', 0],//礼品卡状态 0-禁用 1-启用
            ['sort', 0],//排序
            ['balance', 0],//储值金额
            ['exchange_type', 0],//兑换商品类型 1-固定商品打包 2-任选N件商品
            ['gift_num', 0],//任选 N 件
            ['product', []],//兑换商品
            [['description', 's'], ''],//详情
        ]);
        $this->validate($data, CardGiftValidate::class, 'update');
        $res = $this->services->saveData($data, $id, $this->adminId);
        if ($res) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    public function otherForm($id)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        return $this->success($this->services->otherForm($id));
    }

    public function updateOther($id)
    {
        [$type, $number] = $this->request->postMore([
            ['type', 0],
            ['number', 0],
        ], true);
        if (!$id) {
            return $this->fail('参数错误');
        }
        if ($number <= 0) {
            return $this->fail('数量必须大于0');
        }
        if ($this->services->updateOther($id, $type, $number, $this->adminId)) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 修改状态
     * @param $id
     * @param $status
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/5/12 09:38
     */
    public function setStatus($id, $status)
    {
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('礼品卡不存在');
        }
        $res = $this->services->update($id, ['status' => $status]);
        if ($res) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }

    /**
     * 删除
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/3 10:49
     */
    public function delete($id)
    {
        $this->services->delete($id);
        return $this->success('删除成功');
    }

    /**
     * 树列表
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/6/3 10:48
     */
    public function tree()
    {
        return $this->success($this->services->getTree());
    }

    /**
     * 记录
     * @param $id
     * @param CardGiftRecordServices $services
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/6/3 10:48
     */
    public function giftRecordList($id, CardGiftRecordServices $services)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        return $this->success($services->getList(['gift_id' => $id]));
    }
}
