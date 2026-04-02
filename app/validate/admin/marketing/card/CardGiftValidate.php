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

/**
 * 礼品卡验证器
 * Class CardGiftValidate
 * @package app\validate\admin\marketing\card
 */
class CardGiftValidate extends Validate
{
    /**
     * 自定义验证规则
     * @param $value
     * @param $rule
     * @param $data
     * @return bool|string
     */

    public function checkTimeGiftNum($value, $rule, $data)
    {
        if ($data[$rule] == 1) {
            return false;
        }
        return true;
    }

    public function checkBalance($value, $rule, $data)
    {
        if ($data[$rule] == 1) {
            // 先检查字段是否存在
            if (!isset($data['balance'])) {
                return '余额字段必须填写';
            }
            $validate = new Validate();
            $validate->rule('balance', 'float|gt:0');
            return $validate->check($data) ?: $validate->getError();
        }
        return true;
    }

    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:50',
        'type' => 'require|in:1,2',
        'batch_id' => 'require|integer|gt:0',
        'total_num' => 'require|integer|gt:0',
        'instructions' => 'require|max:500',
        'cover_image' => 'require',
        'valid_type' => 'require|in:1,2',
        'fixed_time' => 'requireIf:valid_type,2|array',
        'status' => 'require|in:0,1',
        'sort' => 'require|integer|between:0,99999',
        'balance' => 'checkBalance:type',
//        'balance' => 'requireIf:type,1|float|gt:0',
        'exchange_type' => 'requireIf:type,2|in:1,2',
        'gift_num' => 'requireIf:type,2|integer|gt:0|checkTimeGiftNum:exchange_type',
        'product' => 'requireIf:type,2|array',
        'product.*.product_id' => 'requireIf:type,2|integer|gt:0',
//        'product.*.limit_num' => 'requireIf:type,2|integer|gt:0',
        'product.*.unique' => 'requireIf:type,2|require',
        'description' => 'require',
    ];

    /**
     * 错误信息
     * @var array
     */
    protected $message = [
        'name.require' => '请输入礼品卡名称',
        'name.max' => '礼品卡名称不能超过50个字符',
        'type.require' => '请选择礼品卡类型',
        'type.in' => '礼品卡类型错误',
        'batch_id.require' => '请选择关联卡密',
        'batch_id.integer' => '关联卡密ID必须为整数',
        'batch_id.gt' => '关联卡密ID必须大于0',
        'total_num.require' => '请输入总数量',
        'total_num.integer' => '总数量必须为整数',
        'total_num.gt' => '总数量必须大于0',
        'instructions.require' => '请输入使用须知',
        'instructions.max' => '使用须知不能超过500个字符',
        'cover_image.require' => '请上传封面图',
        'valid_type.require' => '请选择有效期类型',
        'valid_type.in' => '有效期类型错误',
        'fixed_time.requireIf' => '请设置有效期',
        'fixed_time.array' => '有效期必须为数组',
        'status.require' => '请选择礼品卡状态',
        'status.in' => '礼品卡状态错误',
        'sort.require' => '请输入排序',
        'sort.integer' => '排序必须为整数',
        'sort.between' => '排序必须在0-99999之间',
        'balance.requireIf' => '请输入储值金额',
        'balance.float' => '储值金额必须为数字',
        'balance.gt' => '储值金额必须大于0',
        'exchange_type.requireIf' => '请选择兑换商品类型',
        'exchange_type.in' => '兑换商品类型错误',
        'gift_num.requireIf' => '赠送商品类型错误',
        'gift_num.integer' => '赠送商品必须是整数',
        'gift_num.gt' => '赠送商品必须大于 0',
        'product.requireIf' => '请选择兑换商品',
        'product.array' => '兑换商品格式错误',
        'product.*.product_id.requireIf' => '请选择兑换商品',
        'product.*.product_id.integer' => '商品ID必须为整数',
        'product.*.product_id.gt' => '商品ID必须大于0',
//        'product.*.limit_num.requireIf' => '请输入兑换数量限制',
//        'product.*.limit_num.integer' => '兑换数量限制必须为整数',
//        'product.*.limit_num.gt' => '兑换数量限制必须大于0',
        'product.*.unique.requireIf' => '请输入商品唯一标识',
        'product.*.unique.require' => '商品唯一标识不能为空',
        'description.require' => '请填写详情',

    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'save' => ['name', 'type', 'batch_id', 'total_num', 'instructions', 'cover_image', 'valid_type', 'fixed_time', 'status', 'sort', 'balance', 'exchange_type', 'product', 'description'],
        'update' => ['name', 'type', 'instructions', 'cover_image', 'valid_type', 'fixed_time', 'status', 'sort', 'balance', 'exchange_type', 'product', 'description']
    ];
}
