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
namespace app\validate\admin\marketing\card;

use think\Validate;

class CardBatchValidate extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'name' => 'require|max:50',
        'card_prefix' => 'require|max:20',
        'card_suffix' => 'require|max:20',
        'total_num' => 'require|integer|gt:0|max:99999',
        'pwd_type' => 'require|array',
        'pwd_num'   => 'require|integer|gt:0|max:10',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '请填写卡次名称',
        'name.max' => '卡次名称最多不能超过50个字符',
        'card_prefix.require' => '请填写卡号前缀',
        'card_prefix.max' => '卡号前缀最多不能超过20个字符',
        'card_suffix.require' => '请填写卡号后缀',
        'card_suffix.max' => '卡号后缀最多不能超过20个字符',
        'total_num.require' => '请填写总数量',
        'total_num.integer' => '总数量必须为整数',
        'total_num.gt' => '总数量必须大于0',
        'pwd_type.require' => '请选择卡密内容',
        'pwd_type.array' => '卡密内容格式错误',
        'pwd_num.require' => '请填写卡密位数',
        'pwd_num.integer' => '卡密位数必须为整数',
        'pwd_num.gt' => '卡密位数必须大于0',
    ];

    /**
     * 定义验证场景
     * @var array
     */
    protected $scene = [
        'save' => ['name', 'card_prefix', 'card_suffix', 'total_num', 'pwd_type', 'pwd_num']
    ];
}