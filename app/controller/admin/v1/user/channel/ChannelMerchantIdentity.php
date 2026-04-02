<?php
declare (strict_types=1);

namespace app\controller\admin\v1\user\channel;

use app\controller\admin\AuthController;
use app\services\user\channel\ChannelMerchantIdentityServices;
use app\services\user\channel\ChannelMerchantServices;
use app\validate\admin\user\channel\ChannelMerchantIdentityValidate;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\App;

/**
 * 采购商身份管理
 * Class ChannelMerchantIdentity
 * @package app\controller\admin\v1\user\channel
 */
class ChannelMerchantIdentity extends AuthController
{
    /**
     * @var ChannelMerchantIdentityServices
     */
    #[Inject]
    protected ChannelMerchantIdentityServices $services;

    /**
     * 获取采购商身份列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['keyword', ''],
            ['is_show', ''],
        ]);
        $where['is_del'] = 0;
        return $this->success($this->services->getList($where));
    }

    /**
     * 获取创建表单
     * @return mixed
     */
    public function create()
    {
        return $this->success($this->services->createForm());
    }

    /**
     * 保存采购商身份
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['level', 0],
            ['discount', 0],
            ['is_show', 1],
        ]);

        try {
            validate(ChannelMerchantIdentityValidate::class)->check($data);
        } catch (ValidateException $e) {
            return $this->fail($e->getError());
        }

        if ($this->services->add($data)) {
            return $this->success('添加成功');
        } else {
            return $this->fail('添加失败');
        }
    }

    /**
     * 获取编辑表单
     * @param int $id
     * @return mixed
     */
    public function edit($id)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        return $this->success($this->services->createForm((int)$id));
    }

    /**
     * 更新采购商身份
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['level', 0],
            ['discount', 0],
            ['is_show', 1],
        ]);

        try {
            validate(ChannelMerchantIdentityValidate::class)->check($data);
        } catch (ValidateException $e) {
            return $this->fail($e->getError());
        }

        if ($this->services->edit((int)$id, $data)) {
            return $this->success('编辑成功');
        } else {
            return $this->fail('编辑失败');
        }
    }

    /**
     * 删除采购商身份
     * @param $id
     * @return mixed
     */
    public function delete($id, ChannelMerchantServices $services)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        if ($services->search(['channel_type' => $id, 'is_del' => 0])->find()) {
            return $this->fail('该身份已被使用，无法删除');
        }
        if ($this->services->delete((int)$id)) {
            return $this->success('删除成功');
        } else {
            return $this->fail('删除失败');
        }
    }

    /**
     * 修改采购商身份状态
     * @param int $id
     * @param int $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        if (!$id) {
            return $this->fail('参数错误');
        }
        if ($this->services->setStatus((int)$id, (int)$status)) {
            return $this->success($status == 1 ? '启用成功' : '禁用成功');
        } else {
            return $this->fail($status == 1 ? '启用失败' : '禁用失败');
        }
    }

    /**
     * 获取所有采购商身份
     * @return mixed
     */
    public function getAll()
    {
        $where = [
            'is_show' => 1,
            'is_del' => 0
        ];
        return $this->success($this->services->getAllIdentities($where));
    }
}
