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

namespace app\listener\order;


use app\jobs\agent\AgentJob;
use app\jobs\order\OrderCreateAfterJob;
use app\jobs\order\OrderDeliveryJob;
use app\jobs\order\OrderJob;
use app\jobs\order\OrderPayHandelJob;
use app\jobs\order\OrderStatusJob;
use app\jobs\order\OrderTakeJob;
use app\jobs\order\ShareOrderJob;
use app\jobs\activity\pink\PinkJob;
use app\jobs\product\ProductCouponJob;
use app\jobs\product\ProductLogJob;
use app\jobs\activity\StorePromotionsJob;
use app\jobs\system\CapitalFlowJob;
use app\jobs\user\UserLevelJob;
use app\services\activity\card\CardCodeServices;
use app\services\order\StoreOrderCartInfoServices;
use app\services\order\StoreOrderComputedServices;
use app\services\order\StoreOrderCreateServices;
use app\services\order\StoreOrderInvoiceServices;
use app\services\user\channel\ChannelMerchantServices;
use app\services\user\UserServices;
use crmeb\interfaces\ListenerInterface;
use app\jobs\activity\LuckLotteryJob;
use think\facade\Log;

/**
 * 订单支付事件
 * Class Pay
 * @package app\listener\order
 */
class Pay implements ListenerInterface
{
    public function handle($event): void
    {
        [$orderInfo, $userInfo] = $event;
        //计算订单实际金额
//        OrderJob::dispatchDo('compute', [$orderInfo['uid'], $orderInfo['id']]);
        $this->compute($userInfo['uid'] ?? 0, $orderInfo['id']);
        //创建拼团
        if ($orderInfo['activity_id'] && !$orderInfo['refund_status']) {
            //拼团
            //创建拼团
            if ($orderInfo['type'] == 3) {
                PinkJob::dispatchDo('createPink', [$orderInfo]);
            }
        }
        //礼品卡
        if ($orderInfo['type'] == 9) {
            app()->make(CardCodeServices::class)->useCode($orderInfo);
        }

        //判断用户是否是推广员
        app()->make(UserServices::class)->checkUserPromoter($userInfo['uid'] ?? 0);
        //写入订单记录表
        OrderStatusJob::dispatch([$orderInfo['id'], 'pay_success', ['change_message' => '用户付款成功', 'change_manager_type' => 'user', 'change_manager_id' => $userInfo['uid']]]);
        //卡密、虚拟、次卡商品订单处理
        OrderPayHandelJob::dispatch([$orderInfo]);
        //自动分配订单
        ShareOrderJob::dispatch([$orderInfo]);
        //门店虚拟用户
        if ($orderInfo['uid']) {
            //赠送商品关联优惠卷
            ProductCouponJob::dispatch([$orderInfo]);
            //修改开票数据支付状态
            $orderInvoiceServices = app()->make(StoreOrderInvoiceServices::class);
            $orderInvoiceServices->update(['order_id' => $orderInfo['id']], ['is_pay' => 1]);
            //支付成功后计算商品节省金额
            OrderJob::dispatchDo('setEconomizeMoney', [$orderInfo]);
            //支付成功处理自己、上级分销等级升级
            AgentJob::dispatch([(int)$orderInfo['uid']]);
            //支付成功后更新用户支付订单数量
            OrderJob::dispatchDo('setUserPayCountAndPromoter', [$orderInfo]);
            //优惠活动赠送优惠卷
            StorePromotionsJob::dispatchDo('give', [$orderInfo]);
            //优惠活动关联用户标签设置
            StorePromotionsJob::dispatchDo('setUserLabel', [$orderInfo]);
            //检测用户等级升级
            UserLevelJob::dispatch([(int)$orderInfo['uid']]);
        }
        if ($orderInfo['shipping_type'] == 4 && $orderInfo['product_type'] != 4) {
            //订单发货
            OrderDeliveryJob::dispatch([$orderInfo, [], 4]);
            //订单收货
            OrderTakeJob::dispatchSece(60, [$orderInfo]);
            //清理购物车
            $cartIds = [];
            if (isset($orderInfo['cart_id']) && $orderInfo['cart_id']) {
                $cartIds = is_string($orderInfo['cart_id']) ? json_decode($orderInfo['cart_id'], true) : $orderInfo['cart_id'];
            }
            OrderCreateAfterJob::dispatchDo('updateUser', [$orderInfo, ['cartIds' => $cartIds, 'delCart' => true]]);
            OrderCreateAfterJob::dispatchDo('delCart', [['cartIds' => $cartIds, 'delCart' => true]]);
        }
        //支付成功后其他事件处理
        OrderJob::dispatchDo('otherTake', [$orderInfo]);
        //支付成功后向管理员发送模板消息
        OrderJob::dispatchDo('sendServicesAndTemplate', [$orderInfo]);
        //支付记录
        ProductLogJob::dispatch(['pay', ['uid' => $orderInfo['uid'], 'order_id' => $orderInfo['id']]]);
        //记录资金流水队列
        CapitalFlowJob::dispatch([$orderInfo, 'order']);
        //支付成功给客服发送消息
        event('notice.notice', [$orderInfo, 'admin_pay_success_code']);
        //对外接口推送事件
        event('out.outPush', ['order_pay_push', ['order_id' => (int)$orderInfo['id']]]);
        //自动打标签
        event('user.auto.label', [$orderInfo['uid'], '', [], []]);


        // 小程序订单管理 (自提商品或者卡密自动发货)
        if ($orderInfo['shipping_type'] == 2 || $orderInfo['product_type'] == 1) {
            event('order.routine.shipping', ['product', $orderInfo, 4, '', '']);
        }
        // 同步订单
//        if (sys_config('erp_open')) {
//            OrderSyncJob::dispatchDo('syncOrder', [(int)$orderInfo['id']]);
//        }
        if ($orderInfo['channel'] == 1) {
            $ChannelMerchantServices = app()->make(ChannelMerchantServices::class);
            $channelInfo = $ChannelMerchantServices->get(['uid' => $orderInfo['uid'], 'is_del' => 0]);
            if ($channelInfo) {
                $channelInfo->order_count = $channelInfo->order_count + 1;
                $channelInfo->order_price = bcadd((string)$channelInfo->order_price, (string)$orderInfo['pay_price'], 2);
                $channelInfo->save();
            }
        }
    }

    public function compute($uid, $oid)
    {
        if (!$oid) {
            return true;
        }
        /** @var StoreOrderCreateServices $createService */
        $createService = app()->make(StoreOrderCreateServices::class);
        $orderInfo = $createService->get($oid);
        if (!$orderInfo || $uid != $orderInfo['uid']) {
            return true;
        }
        try {
            $uid = (int)$orderInfo['uid'];
            $userInfo = [];
            if ($uid) {
                /** @var UserServices $userService */
                $userService = app()->make(UserServices::class);
                $userInfo = $userService->getUserCacheInfo($uid);
            }
            $orderId = (int)$orderInfo['id'];
            /** @var StoreOrderCartInfoServices $cartServices */
            $cartServices = app()->make(StoreOrderCartInfoServices::class);
            $cartInfoList = $cartServices->getColumn(['oid' => $orderId], 'cart_info');
            if (!$cartInfoList) {
                return true;
            }
            $cartInfo = [];
            foreach ($cartInfoList as $cart) {
                $cartInfo[] = is_string($cart) ? json_decode($cart, true) : $cart;
            }
            $priceData = [
                'usedIntegral' => $orderInfo['use_integral'],
                'deduction_price' => $orderInfo['deduction_price'],
                'first_order_price' => $orderInfo['first_order_price']
            ];
            $spread_ids = [];
            $spread_uid = $spread_two_uid = 0;
            if ($cartInfo && $priceData) {
                [$cartInfo, $spread_ids] = $createService->computeOrderProductTruePrice($orderInfo, $cartInfo, $priceData, $uid, $userInfo);
                $cartServices->updateCartInfo($orderId, $cartInfo);
            }
            $orderData = [];
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            if ($spread_ids) {
                [$spread_uid, $spread_two_uid] = $spread_ids;
            } else {
                $spread_uid = $userServices->getSpreadUid($uid);
                if ($spread_uid > 0 && sys_config('brokerage_level', 2) == 2) {
                    $spread_two_uid = $userServices->getSpreadUid($spread_uid, [], false);
                }
            }
            if ($spread_uid > 0) {
                $orderData['spread_uid'] = $spread_uid;
            }
            if ($spread_two_uid > 0) {
                $orderData['spread_two_uid'] = $spread_two_uid;
            }
            if ($cartInfo && (isset($orderInfo['type']) && !in_array($orderInfo['type'], [4, 5, 7, 8]))) {
                /** @var StoreOrderComputedServices $orderComputed */
                $orderComputed = app()->make(StoreOrderComputedServices::class);
                if ($userServices->checkUserPromoter($spread_uid)) $orderData['one_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'one_brokerage', false);
                if ($userServices->checkUserPromoter($spread_two_uid)) $orderData['two_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'two_brokerage', false);
                $orderData['division_staff_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_staff_brokerage', false);
                $orderData['division_agent_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_agent_brokerage', false);
                $orderData['division_brokerage'] = $orderComputed->getOrderSumPrice($cartInfo, 'division_brokerage', false);
            }
            \think\facade\Log::error('计算订单实际优惠、积分、邮费、佣金成功:' . json_encode($orderData, JSON_UNESCAPED_UNICODE));
            if ($orderData) $createService->update(['id' => $orderId], $orderData);

        } catch (\Throwable $e) {
            Log::error('计算订单实际优惠、积分、邮费、佣金失败，原因：' . $e->getMessage());
        }

        return true;
    }
}
