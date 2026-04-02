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

namespace app\services\order;


use app\dao\order\StoreOrderDao;
use app\services\activity\lottery\LuckLotteryServices;
use app\services\BaseServices;
use app\services\pay\IntegralPayServices;
use app\services\pay\PayServices;
use app\services\user\UserServices;
use app\services\order\UserCoinBillServices;
use think\annotation\Inject;
use think\exception\ValidateException;
use crmeb\exceptions\PayException;

/**
 * Class StoreOrderSuccessServices
 * @package app\services\order
 * @mixin StoreOrderDao
 */
class StoreOrderSuccessServices extends BaseServices
{
    /**
     * @var StoreOrderDao
     */
    #[Inject]
    protected StoreOrderDao $dao;

    /**
     * 0元支付
     * @param array $orderInfo
     * @param int $uid
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function zeroYuanPayment(array $orderInfo, int $uid, string $payType = PayServices::YUE_PAY)
    {
        $id = $orderInfo['id'] ?? 0;
        if (!$orderInfo || !$id) {
            throw new ValidateException('订单不存在!');
        }
        //更新订单信息
        $orderInfo = $this->dao->get($id);
        if (!$orderInfo) {
            throw new ValidateException('订单不存在');
        }
        $orderInfo = $orderInfo->toArray();
        if ($orderInfo['paid']) {
            throw new ValidateException('该订单已支付!');
        }
        $orderInfo['paid1'] = 1;
        $orderInfo['id'] = $id;
        return $this->paySuccess($orderInfo, $payType);//余额支付成功
    }

    /**
     * 支付成功
     * @param array $orderInfo
     * @param string $paytype
     * @return bool
     */
    public function paySuccess(array $orderInfo, string $paytype = PayServices::WEIXIN_PAY, array $other = [])
    {
         // 计算获得积分
        $gainIntegral = round($orderInfo['total_price'] * 3, 2);
        if(isset($orderInfo['paid1'])){
            $orderInfo['paid'] = 1;
            $orderInfo['pay_type'] = $paytype;
            $orderInfo['pay_time'] = time();
            $updata = ['paid' => 1, 'pay_type' => $paytype, 'pay_time' => time(), 'gain_integral' => $gainIntegral,'pay_price'=>$orderInfo['pay_price'],'pay_integral'=>$orderInfo['pay_integral'],'pay_coin'=>$orderInfo['pay_coin']];
            $res1 = $this->dao->update($orderInfo['id'], $updata);
        }else{
            /** @var UserServices $userServices */
            $userServices = app()->make(UserServices::class);
            $userInfo = $userServices->get($orderInfo['uid']);

            $orderInfo['pay_price'] = round($orderInfo['total_price']/2, 2);
            $orderInfo['pay_integral'] = 0;
            $orderInfo['pay_coin'] = $orderInfo['total_price'] - $orderInfo['pay_price'];
            
            // 检查通票余额是否足够
            if ($userInfo['coin_num'] < $orderInfo['pay_coin']) {
                throw new PayException('通票余额不足，当前通票：' . $userInfo['coin_num'] . '，需要通票：' . $orderInfo['pay_coin']);
            }
            
            $updata = ['paid' => 1, 'pay_type' => $paytype, 'pay_time' => time(), 'gain_integral' => $gainIntegral,'pay_price'=>$orderInfo['pay_price'],'pay_integral'=>$orderInfo['pay_integral'],'pay_coin'=>$orderInfo['pay_coin']];
            if ($other && isset($other['trade_no'])) {
                $updata['trade_no'] = $other['trade_no'];
            }
            $res1 = $this->dao->update($orderInfo['id'], $updata);
            $orderInfo['trade_no'] = $other['trade_no'] ?? '';
            $orderInfo['pay_time'] = time();
            $orderInfo['pay_type'] = $paytype;
            $orderInfo['gain_integral'] = $gainIntegral;
            
            //缓存抽奖次数 除过线下支付和抽奖订单
            if (isset($orderInfo['pay_type']) && $orderInfo['pay_type'] != 'offline' && isset($orderInfo['type']) && $orderInfo['type'] != 8 && $orderInfo['type'] != 9) {
                /** @var LuckLotteryServices $luckLotteryServices */
                $luckLotteryServices = app()->make(LuckLotteryServices::class);
                $luckLotteryServices->setCacheLotteryNum((int)$orderInfo['uid'], 'order');
            }
        }
        
        // 处理积分和通票的消耗及记录
        $this->handleUserIntegralAndCoin((int)$orderInfo['uid'], $userInfo->toArray(), $orderInfo, $updata);
        
        // if ($orderInfo['pay_integral']) {//需要支付积分
        //     /** @var IntegralPayServices $integralPayServices */
        //     $integralPayServices = app()->make(IntegralPayServices::class);
        //     $integralPayServices->integralOrderPay((int)$userInfo['uid'], $orderInfo, $userInfo->toArray());
        // }
        
        //订单支付成功事件
        event('order.pay', [$orderInfo, $userInfo]);
        $res = $res1;
        return false !== $res;
    }

    /**
     * 处理用户积分和通票的消耗及记录
     * @param int $uid 用户ID
     * @param array $userInfo 用户信息
     * @param array $order 订单信息
     * @param array $updateData 更新数据
     * @return void
     * @throws \Exception
     */
    private function handleUserIntegralAndCoin(int $uid, array $userInfo, array $order, array $updateData): void
    {
        /** @var UserServices $userServices */
        $userServices = app()->make(UserServices::class);
        /** @var UserCoinBillServices $userCoinBillServices */
        $userCoinBillServices = app()->make(UserCoinBillServices::class);
        
        // 优化积分和通票处理逻辑，减少SQL调用
        $userUpdateData = [];
        $billData = [];
        $currentTime = time();
        if($userInfo['is_first_order'] != 1){
            $userUpdateData['is_first_order'] = 1;
        }
        
        // 处理积分消耗
        if (isset($updateData['pay_integral']) && $updateData['pay_integral'] > 0) {
            $newIntegral = max(0, $userInfo['integral'] - $updateData['pay_integral']);
            $userUpdateData['integral'] = $newIntegral;
            
            // 准备积分消耗账单数据
            $billData[] = [
                'uid' => $uid,
                'type' => 'integral',
                'pm' => 0, // 0表示支出
                'title' => '订单支付消耗积分',
                'number' => $updateData['pay_integral'],
                'balance' => $newIntegral,
                'mark' => "订单号：{$order['order_id']}，支付消耗积分",
                'link_id' => $order['order_id'],
                'add_time' => $currentTime
            ];
            
            $userInfo['integral'] = $newIntegral;
        }
        
        // 处理通票消耗
        if (isset($updateData['pay_coin']) && $updateData['pay_coin'] > 0) {
            $newCoin = max(0, $userInfo['coin_num'] - $updateData['pay_coin']);
            $userUpdateData['coin_num'] = $newCoin;
            
            // 准备通票消耗账单数据
            $billData[] = [
                'uid' => $uid,
                'type' => 'coin',
                'pm' => 0, // 0表示支出
                'title' => '订单支付消耗通票',
                'number' => $updateData['pay_coin'],
                'balance' => $newCoin,
                'mark' => "订单号：{$order['order_id']}，支付消耗通票",
                'link_id' => $order['order_id'],
                'add_time' => $currentTime
            ];
            
            $userInfo['coin_num'] = $newCoin;
        }
        
        // 处理获得积分的锁定
        if (isset($updateData['gain_integral']) && $updateData['gain_integral'] > 0) {
            $userUpdateData['integral_lock'] = ($userInfo['integral_lock'] ?? 0) + $updateData['gain_integral'];
            
            // 准备积分获得账单数据
            $billData[] = [
                'uid' => $uid,
                'type' => 'integral',
                'pm' => 1, // 1表示收入
                'title' => '订单支付获得积分',
                'number' => $updateData['gain_integral'],
                'balance' => $userInfo['integral'], // 这里使用当前积分余额，因为积分是锁定状态
                'mark' => "订单号：{$order['order_id']}，支付获得积分并锁定",
                'link_id' => $order['order_id'],
                'add_time' => $currentTime
            ];
        }
        
        // 使用事务批量执行操作
        if (!empty($userUpdateData) || !empty($billData)) {
            $userServices->transaction(function () use ($userServices, $userCoinBillServices, $uid, $userUpdateData, $billData) {
                // 批量更新用户字段（一次SQL）
                if (!empty($userUpdateData)) {
                    $userServices->update($uid, $userUpdateData);
                }
                
                // 批量插入账单记录（一次SQL）
                if (!empty($billData)) {
                    $userCoinBillServices->saveBatchBills($billData);
                }
            });
        }
    }

}
