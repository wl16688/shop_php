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
namespace app\controller\api\v1\activity;


use app\Request;
use app\services\activity\card\CardCodeServices;
use app\services\activity\card\CardGiftServices;
use think\annotation\Inject;


/**
 * 礼品卡
 * Class CardGift
 * @package app\api\controller\activity
 */
class CardGift
{

    /**
     * @var CardGiftServices
     */
    #[Inject]
    protected CardGiftServices $services;

    public function giftInfo(Request $request, CardCodeServices $codeServices)
    {
        [$card_number, $card_pwd] = $request->postMore([
            ['card_number', ''],
            ['card_pwd', ''],
        ], true);
        if (!$card_number || !$card_pwd) return app('json')->fail('请输入卡密');
        return app('json')->success($codeServices->verifyGiftCode($card_number, $card_pwd));
    }

    public function receive(Request $request, CardCodeServices $codeServices)
    {
        [$card_number, $card_pwd, $product] = $request->postMore([
            ['card_number', ''],
            ['card_pwd', ''],
            ['product', []]
        ], true);
        if (!$card_number || !$card_pwd) return app('json')->fail('请输入卡密');
        $data = $codeServices->receiveGiftCode($card_number, $card_pwd, $product, (int)$request->uid());
        return app('json')->success($data['type'] == 1 ? '领取成功' : $data['data']);
    }
}
