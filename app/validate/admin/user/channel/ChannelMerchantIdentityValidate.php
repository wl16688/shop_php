<?php
declare (strict_types=1);

namespace app\validate\admin\user\channel;

use think\Validate;

class ChannelMerchantIdentityValidate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name' => 'require|string',
//        'level' => 'require|integer|egt:0',
        'discount' => 'require|integer|between:0,100',
        'is_show' => 'require|in:0,1'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '请输入身份名称',
        'name.string' => '身份名称必须为字符串',
//        'level.require' => '请输入等级',
//        'level.integer' => '等级必须为整数',
//        'level.egt' => '等级不能小于0',
        'discount.require' => '请输入折扣比例',
        'discount.integer' => '折扣比例必须为整数',
        'discount.between' => '折扣比例必须在0-100之间',
        'is_show.require' => '请选择显示状态',
        'is_show.in' => '显示状态值错误'
    ];
}
