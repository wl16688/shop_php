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

namespace app\services\user\channel;

use app\dao\user\channel\ChannelMerchantDao;
use app\services\BaseServices;
use app\services\order\StoreOrderServices;
use app\services\user\UserServices;
use crmeb\services\FormBuilder as Form;
use FormBuilder\Factory\Iview;
use think\annotation\Inject;
use think\exception\ValidateException;
use think\facade\Route as Url;

/**
 * 采购商服务类
 * Class ChannelMerchantServices
 * @package app\services\user\channel
 * @mixin ChannelMerchantDao
 */
class ChannelMerchantServices extends BaseServices
{

    /**
     * @var ChannelMerchantDao
     */
    #[Inject]
    protected ChannelMerchantDao $dao;

    /**
     * 获取采购商信息
     * @param int $id
     * @return array|\think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/5/10
     */
    public function read(int $id)
    {
        $info = $this->dao->get($id);
        if (!$info) {
            return app('json')->fail('数据不存在');
        }
        $info = $info->toArray();
        $info['avatar'] = app()->make(UserServices::class)->getUserCacheInfo($info['uid'])['avatar'] ?? '';
        $orderData = app()->make(StoreOrderServices::class)->getOneData(['uid' => $info['uid'], 'channel' => 1, ['pid', '<=', 0], 'paid' => 1, 'is_del' => 0, 'is_system_del' => 0], 'COUNT(*) as order_count,SUM(pay_price) as order_price');
        $info['order_count'] = $orderData['order_count'] ?? 0;
        $info['order_price'] = $orderData['order_price'] ?? 0;
        return $info;
    }

    /**
     * 获取采购商列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getChannelList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $list = $this->dao->getChannelList($where, $page, $limit);
        $userService = app()->make(UserServices::class);
        $identityService = app()->make(ChannelMerchantIdentityServices::class);
        $identityIds = array_column($list, 'channel_type');
        $identityList = $identityService->search(['id' => $identityIds, 'is_del' => 0])->column('name,discount', 'id');
        $userIds = array_column($list, 'uid');
        $userList = $userService->getColumn([['uid', 'in', $userIds]], 'uid,nickname,avatar,phone', 'uid');
        foreach ($list as &$item) {
            $item['identity_name'] = $identityList[$item['channel_type']]['name'] ?? '';
            $item['identity_discount'] = $identityList[$item['channel_type']]['discount'] ?? '';
            $item['user_nickname'] = $userList[$item['uid']]['nickname'] ?? '';
            $item['user_avatar'] = $userList[$item['uid']]['avatar'] ?? '';
            $item['user_phone'] = $userList[$item['uid']]['phone'] ?? '';
        }
        $count = $this->dao->count($where);
        return compact('list', 'count');
    }

    /**
     * 保存采购商数据
     * @param array $data
     * @return mixed
     */
    public function saveChannel(array $data)
    {
        $data['add_time'] = time();
        if ($this->dao->count(['uid' => $data['uid'], 'is_del' => 0])) {
            throw new ValidateException('该用户已经是采购商了，无需重复添加！');
        }
        $res = $this->dao->save($data);
        return $res && $this->updateUserChannelFlag($data['uid'], 1);
    }

    /**
     * 修改采购商数据
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateChannel(int $id, array $data)
    {
        return $this->dao->update($id, $data);
    }

    /**
     * 审核采购商
     * @param int $id
     * @param int $verifyStatus
     * @param string $reason
     * @param int $channelType
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function verifyChannel(int $id, int $verifyStatus, int $channelType = 0, string $reason = '')
    {
        $channel = $this->dao->getInfo($id);
        if (!$channel) {
            throw new ValidateException('采购商不存在');
        }
        if ($channel['verify_status'] == $verifyStatus) {
            throw new ValidateException('请勿重复审核');
        }

        $updateData = [
            'verify_status' => $verifyStatus,
            'verify_time' => time(),
        ];

        // 如果是审核通过，需要更新采购商身份和状态
        if ($verifyStatus == 1) {
            $updateData['channel_type'] = $channelType;
            $updateData['status'] = 1; // 审核通过后自动开启
        }

        // 如果有备注，更新备注
        if ($verifyStatus == 2) {
            $updateData['reject'] = $reason;
        }

        $this->dao->update($id, $updateData);

        // 审核通过后，更新用户表的采购商标识
        if ($verifyStatus == 1) {
            $this->updateUserChannelFlag($channel['uid'], 1);
        }

        return true;
    }

    public function updateUserChannelFlag(int $uid, int $isChannel)
    {
        $userServices = app()->make(UserServices::class);
        $userServices->update($uid, ['is_channel' => $isChannel]);
        $userServices->cacheTag()->clear();
        return true;
    }

    /**
     * 创建审核表单
     * @param int $id
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createVerifyForm(int $id)
    {
        $channel = $this->dao->getInfo($id);
        if (!$channel) {
            throw new ValidateException('采购商不存在');
        }

        $identityService = app()->make(ChannelMerchantIdentityServices::class);
        $identity = $identityService->getAllIdentities(['is_show' => 1]);
        $options = [];
        foreach ($identity as $v) {
            $options[] = [
                'label' => $v['name'],
                'value' => $v['id']
            ];
        }
        // 创建表单
        $form = [];

        // 基本信息展示（只读）
        $form[] = Form::hidden('id', $id);
        $form[] = Form::input('channel_name', '采购商名称', $channel['channel_name'])->disabled(true);
        $form[] = Form::input('real_name', '联系人', $channel['real_name'])->disabled(true);
        $form[] = Form::input('phone', '联系电话', $channel['phone'])->disabled(true);

        // 审核状态选择
        $form[] = Form::radio('verify_status', '审核结果', 1)
            ->options([
                ['label' => '通过', 'value' => 1],
                ['label' => '拒绝', 'value' => 2]
            ])
            ->control([
                [
                    'value' => 1,
                    'rule' => [
                        Form::select('channel_type', '采购商身份', $channel['channel_type'] ?: 1)->setOptions($options)->filterable(true)->appendValidate(Iview::validateInt()->message('请选择采购商身份')->required())
                    ]
                ],
                [
                    'value' => 2,
                    'rule' => [
                        Form::textarea('reject', '拒绝原因', '')
                            ->placeholder('请填写拒绝原因')
                            ->required('请填写拒绝原因')
                    ]
                ]
            ]);
        return create_form('采购商审核', $form, Url::buildUrl('/channel/merchant/verify'), 'POST');
    }

    /**
     * 修改采购商状态
     * @param int $id
     * @param int $status
     * @return mixed
     */
    public function setStatus(int $id, int $status)
    {
        if (!$this->dao->getInfo($id)) {
            throw new ValidateException('采购商不存在');
        }
        $this->updateUserChannelFlag($id, $status);
        return $this->dao->update($id, ['status' => $status]);
    }

    /**
     * 删除采购商
     * @param int $id
     * @return mixed
     */
    public function deleteChannel(int $id)
    {
        if (!$info = $this->dao->getInfo($id)) {
            throw new ValidateException('采购商不存在');
        }
        $this->updateUserChannelFlag($info['uid'], 0);
        return $this->dao->update($id, ['is_del' => 1]);
    }

    /**
     * 获取表单
     * @param array $formData
     * @return array
     * @throws \FormBuilder\Exception\FormBuilderException
     */
    public function createForm(array $formData = [])
    {
        $id = $formData['id'] ?? 0;
        $form = [];
        if (!$formData) {
            $form[] = Form::frameImage('image', '商城用户', $this->url('admin/system.user/list', ['fodder' => 'image'], true))
                ->icon('ios-add')->width('960px')->height('550px')->modal(['footer-hide' => true])->Props(['srcKey' => 'image']);
            $form[] = Form::hidden('uid', 0);
        }
        $form = $form + [
                Form::hidden('id', $id),
                Form::input('channel_name', '采购商名称', $formData['channel_name'] ?? '')->required('请填写采购商名称'),
                Form::input('real_name', '联系人', $formData['real_name'] ?? '')->required('请填写联系人'),
                Form::input('phone', '联系电话', $formData['phone'] ?? '')->required('请填写联系电话'),
                Form::cityArea('province', '所在地区', $formData['province'] ?? '')->type('city_area')->info('请选择省市区')->required('请选择地区'),
                Form::input('address', '详细地址', $formData['address'] ?? '')->required('请填写详细地址'),
                Form::radio('channel_type', '采购商身份', $formData['channel_type'] ?? 1)->options([
                    ['label' => '省级', 'value' => 1],
                    ['label' => '市级', 'value' => 2],
                    ['label' => '区级', 'value' => 3],
                ]),
                Form::frameImages('certificate', '资质照片', url('admin/widget.images/index', ['fodder' => 'certificate']), $formData['certificate'] ?? [])->maxLength(5),
                Form::radio('status', '状态', $formData['status'] ?? 1)->options([
                    ['label' => '开启', 'value' => 1],
                    ['label' => '关闭', 'value' => 0],
                ]),
                Form::textarea('admin_remark', '备注信息', $formData['remark'] ?? ''),
            ];
        return create_form($id ? '编辑采购商' : '添加采购商', $form, Url::buildUrl('/channel/merchant/save'), 'POST');
    }


    /**
     * 申请成为采购商
     * @param array $data
     * @param array $userInfo
     * @param int $id
     * @return bool
     */
    public function apply(array $data, int $id = 0)
    {

        // 检查是否有正在审核的申请
        $existApply = $this->dao->getChannelInfo(['uid' => $data['uid'], 'verify_status' => 0]);
        if ($existApply) {
            throw new ValidateException('您有正在审核的申请，请耐心等待');
        }
        $data['province_ids'] = is_array($data['province_ids']) ? implode(',', $data['province_ids']) : '';
        $channelData = [
            'uid' => $data['uid'],
            'channel_name' => $data['channel_name'] ?? '',
            'real_name' => $data['real_name'],
            'phone' => $data['phone'],
            'province' => $data['province'] ?? '',
            'province_ids' => $data['province_ids'],
            'address' => $data['address'] ?? '',
            'certificate' => $data['certificate'] ?? '',
            'remark' => $data['remark'] ?? '',
            'verify_status' => 0,
            'status' => 0,
        ];
        if ($id) {
            $result = $this->dao->update($id, $channelData);
        } else {
            $channelData['add_time'] = time();
            $result = $this->dao->save($channelData);
        }
        if (!$result) {
            throw new ValidateException('申请提交失败');
        }

        return true;
    }

    /**
     * 是否采购商
     * @param $uid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/4/8 下午3:07
     */
    public function isChannel(int $uid, bool $is_discount = false)
    {
        if (!$uid) {
            return false;
        }
        $info = $this->dao->getChannelInfo(['uid' => $uid, 'status' => 1, 'is_del' => 0]);
        if (!$info) {
            return false;
        }
        if ($is_discount) {
            $iden_info = app()->make(ChannelMerchantIdentityServices::class)->search(['id' => $info['channel_type']])->find();
            $discount = $iden_info['discount'] ?? 100;
            $discount = bcmul($discount, 0.01, 2);
            $iden_name = $iden_info['name'] ?? '';
            return compact('iden_name', 'discount');
        }
        return true;
    }

}
