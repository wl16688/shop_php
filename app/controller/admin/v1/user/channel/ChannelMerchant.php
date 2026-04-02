<?php
// +----------------------------------------------------------------------
// | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2023 https://www.crmeb.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
// +----------------------------------------------------------------------
// | Author: CRMEB Team <admin@crmeb.com>
// +----------------------------------------------------------------------

namespace app\controller\admin\v1\user\channel;

use app\controller\admin\AuthController;
use app\services\user\channel\ChannelMerchantServices;
use think\annotation\Inject;
use think\facade\App;
use think\Request;

/**
 * 采购商管理
 * Class ChannelMerchant
 * @package app\controller\admin\v1\user\channel
 */
class ChannelMerchant extends AuthController
{
    /**
     * @var ChannelMerchantServices
     */
    #[Inject]
    protected ChannelMerchantServices $services;

    /**
     * 采购商列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['channel_name', ''],//采购商名称
            ['real_name', ''],//真实姓名
            ['phone', ''],//手机号码
            ['status', ''],//是否显示
            ['verify_status', ''],//身份审核状态
            ['channel_type', ''],//采购商类型
            ['date', '', '', 'time'],
            ['field_key', ''],
            ['keyword', ''],
            ['is_admin', ''],
            ['count', ''],
            ['money', ''],
        ]);
        if ($where['is_admin'] === '') {
            $where['verify_status'] = 1;
        }
        return app('json')->success($this->services->getChannelList($where));
    }

    /**
     * 采购商详情
     * @param $id
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/5/10
     */
    public function read($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        return app('json')->success($this->services->read($id));

    }

    /**
     * 获取创建表单
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function create()
    {
        return app('json')->success($this->services->createForm());
    }

    /**
     * 获取编辑表单
     * @param int $id
     * @return mixed
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $channelInfo = $this->services->get($id);
        if (!$channelInfo) {
            return app('json')->fail('数据不存在');
        }
        return app('json')->success($this->services->createForm($channelInfo->toArray()));
    }

    /**
     * 保存采购商信息
     * @return mixed
     */
    public function save()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['uid', 0],
            ['channel_name', ''],
            ['real_name', ''],
            ['phone', ''],
            ['province', ''],
            ['province_ids', ''],
            ['address', ''],
            ['channel_type', 1],
            ['certificate', []],
            ['status', 0],
            ['admin_remark', ''],
        ]);
        if (!$data['uid']) {
            return app('json')->fail('请选择用户');
        }
        if (!$data['channel_name']) {
            return app('json')->fail('请输入采购商名称');
        }
        if (!$data['real_name']) {
            return app('json')->fail('请输入联系人');
        }
        if (!$data['phone']) {
            return app('json')->fail('请输入联系电话');
        }
        if (!$data['province']) {
            return app('json')->fail('请选择所在地区');
        }
        if (!$data['address']) {
            return app('json')->fail('请输入详细地址');
        }
        if (!$data['certificate']) {
            return app('json')->fail('请上传资质图片');
        }

        if ($data['id']) {
            $this->services->updateChannel($data['id'], $data);
        } else {
            $data['verify_status'] = 1;
            $data['status'] = 1;
            $data['is_admin'] = 1;
            $this->services->saveChannel($data);
        }
        return app('json')->success('保存成功');
    }

    public function verifyForm($id)
    {
        return app('json')->success($this->services->createVerifyForm($id));
    }

    /**
     * 审核采购商
     * @return mixed
     */
    public function verify()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['verify_status', 0],
            ['channel_type', 0],
            ['reject', ''],
        ]);

        if (!$data['id']) {
            return app('json')->fail('参数错误');
        }
        if (!in_array($data['verify_status'], [1, 2])) {
            return app('json')->fail('审核状态错误');
        }

        $this->services->verifyChannel($data['id'], $data['verify_status'], $data['channel_type'], $data['reject']);
        return app('json')->success($data['verify_status'] == 1 ? '审核通过' : '审核拒绝');
    }

    /**
     * 修改状态
     * @return mixed
     */
    public function setStatus()
    {
        $data = $this->request->postMore([
            ['id', 0],
            ['status', 0],
        ]);

        if (!$data['id']) {
            return app('json')->fail('参数错误');
        }
        if (!in_array($data['status'], [0, 1])) {
            return app('json')->fail('状态错误');
        }

        $this->services->setStatus($data['id'], $data['status']);
        return app('json')->success($data['status'] == 1 ? '启用成功' : '关闭成功');
    }

    /**
     * 删除采购商
     * @param int $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }

        $this->services->deleteChannel($id);
        return app('json')->success('删除成功');
    }
}
