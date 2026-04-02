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
use app\services\activity\card\CardBatchServices;
use app\validate\admin\marketing\card\CardBatchValidate;
use think\annotation\Inject;


/**
 * 卡次
 * Class CardBatch
 * @package app\admin\controller\card
 */
class CardBatch extends AuthController
{

    /**
     * @var CardBatchServices
     */
    #[Inject]
    protected CardBatchServices $services;

    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['time', ''],
        ]);
        return app('json')->success($this->services->getList($where));
    }

    /**
     *  保存卡批次信息
     *
     *  该方法用于处理卡批次的创建请求它从请求中获取必要的数据，
     *  进行数据验证，检查卡号前缀和后缀的唯一性，并保存数据
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/5/9 12:18
     */
    public function save()
    {
        // 获取请求数据，包括卡次名称、卡号前缀、卡号后缀、总数量、卡密内容和卡密位数
        $data = $this->request->postMore([
            ['name', ''],//卡次名称
            ['card_prefix', ''],//卡号前缀
            ['card_suffix', ''],//卡号后缀
            ['total_num', ''],//卡密数量
            ['pwd_type', []],//卡密内容
            ['pwd_num', 0],//卡密位数
        ]);

        // 验证请求数据，确保数据的合法性
        validate(CardBatchValidate::class)->check($data);
        $this->validate($data, CardBatchValidate::class, 'save');

        // 组合卡号前缀和后缀，以便进行唯一性检查
        $data['prefix'] = $data['card_prefix'] . $data['card_suffix'];

        // 检查数据库中是否已存在相同的卡号前缀和后缀组合，以确保卡号的唯一性
        if ($this->services->get(['prefix' => $data['prefix'], 'is_del' => 0])) {
            return app('json')->fail('卡号已存在');
        }
        // 调用服务层方法保存卡批次数据，并返回保存结果
        $res = $this->services->saveData($data);

        // 返回成功响应，通知用户卡批次已成功添加
        return app('json')->success('添加成功');
    }

    /**
     *  更新卡次信息
     *
     *  该方法用于更新特定卡次的名称它首先检查是否提供了有效的ID，然后获取当前的卡次信息，
     *  并尝试更新名称如果更新成功，它会返回一个成功的响应，否则返回一个失败的响应
     * @param $id
     * @return \think\Response
     * User: liusl
     * DateTime: 2025/5/9 12:18
     */
    public function update($id)
    {
        [$name] = $this->request->postMore([
            ['name', ''],//卡次名称
        ], true);
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $info = $this->services->get($id);
        if (!$info) {
            return app('json')->fail('数据不存在');
        }
        $info->name = $name;
        if ($info->save()) {
            return app('json')->success('修改成功');
        } else {
            return app('json')->fail('修改失败');
        }
    }

    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('缺少参数');
        }
        $info = $this->services->get($id);
        if (!$info) {
            return app('json')->fail('数据不存在');
        }
        if ($this->services->del($id)) {
            return app('json')->success('删除成功');
        } else {
            return app('json')->fail('删除失败');
        }
    }

    public function tree()
    {
        return app('json')->success($this->services->getTree());
    }
}
