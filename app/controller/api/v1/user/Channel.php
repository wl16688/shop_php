<?php

namespace app\controller\api\v1\user;

use app\Request;
use app\services\user\channel\ChannelMerchantServices;
use app\validate\api\user\RegisterValidates;
use crmeb\services\CacheService;
use think\annotation\Inject;
use think\exception\ValidateException;

/**
 * 采购商控制器
 * Class Channel
 * @package app\controller\api\v1\user
 */
class Channel
{
    /**
     * @var ChannelMerchantServices
     */
    #[Inject]
    protected ChannelMerchantServices $services;

    /**
     * 申请成为采购商
     * @param Request $request
     * @return mixed
     */
    public function apply(Request $request)
    {
        $data = $request->postMore([
            ['channel_name', ''],      // 渠道名称
            ['real_name', ''],      // 真实姓名
            ['phone', ''],          // 手机号
            ['code', ''],           // 验证码
            ['province', ''],           // 省市区
            ['address', ''],           // 详细地址
            ['remark', ''],           // 备注
            ['certificate', ''], // 资质图片
            ['province_ids', []], // 省市区id
        ]);
        // 验证用户是否已经是采购商
        $uid = $request->uid();
        $userInfo = $request->user();
        if ($userInfo['is_channel'] == 1) {
            return app('json')->fail('您已经是采购商，请勿重复申请');
        }
        $info = $this->services->getChannelInfo(['uid' => $uid, 'is_del' => 0]);
        if ($info && in_array($info['verify_status'], [0, 1])) {
            return app('json')->fail('请勿重复申请');
        }
        if (!$data['channel_name']) {
            return app('json')->fail('渠道名称不能为空');
        }
        if (!$data['real_name']) {
            return app('json')->fail('真实姓名不能为空');
        }
        //验证手机号
        try {
            validate(RegisterValidates::class)->scene('code')->check(['phone' => $data['phone']]);
        } catch (ValidateException $e) {
            return app('json')->fail($e->getError());
        }
        if (!$data['province']) {
            return app('json')->fail('省市区不能为空');
        }
        if (!$data['address']) {
            return app('json')->fail('详细地址不能为空');
        }
        if (!$data['certificate']) {
            return app('json')->fail('资质图片不能为空');
        }
        if (sys_config('channel_apply_phone_verify') == 1) {
            //验证验证码
            $verifyCode = CacheService::get('code_' . $data['phone']);
            if (!$verifyCode)
                return app('json')->fail('请先获取验证码');
            $verifyCode = substr($verifyCode, 0, 6);
            if ($verifyCode != $data['code']) {
                return app('json')->fail('验证码错误');
            }
        }
        // 验证手机号是否与用户绑定的一致
        if ($userInfo['phone'] != $data['phone']) {
            return app('json')->fail('请使用绑定的手机号申请');
        }
        unset($data['code']);
        $data['uid'] = $uid;
        // 调用服务处理申请
        $result = $this->services->apply($data, $info['id'] ?? 0);

        return app('json')->success('申请提交成功，请等待审核');
    }

    /**
     * 获取申请状态
     * @param Request $request
     * @return mixed
     */
    public function getApplyInfo(Request $request)
    {
        $uid = $request->uid();

        // 获取用户最新的申请记录和状态
        $applyInfo = $this->services->getChannelInfo(['uid' => $uid, 'is_del' => 0]);
        $applyInfo = $applyInfo ? $applyInfo->toArray() : [];
        return app('json')->success($applyInfo);
    }
}
