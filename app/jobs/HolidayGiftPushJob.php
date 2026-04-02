<?php

declare(strict_types=1);

namespace app\jobs;

use app\services\activity\holiday\HolidayGiftPushServices;
use app\services\activity\holiday\HolidayGiftServices;
use app\services\message\sms\SmsSendServices;
use app\services\wechat\WechatServices;
use crmeb\basic\BaseJobs;
use crmeb\traits\QueueTrait;
use think\facade\Log;

/**
 * 节日有礼推送任务
 * Class HolidayGiftPushJob
 * @package app\jobs
 */
class HolidayGiftPushJob extends BaseJobs
{
    use QueueTrait;

    /**
     * 执行推送任务
     * @param int $pushId 推送记录ID
     * @return bool
     */
    public function doJob(int $pushId): bool
    {
        try {
            /** @var HolidayGiftPushServices $pushServices */
            $pushServices = app()->make(HolidayGiftPushServices::class);
            /** @var HolidayGiftServices $giftServices */
            $giftServices = app()->make(HolidayGiftServices::class);

            // 获取推送记录
            $pushInfo = $pushServices->get($pushId);
            if (!$pushInfo || $pushInfo->status != 0) {
                return true;
            }

            // 获取活动信息
            $activity = $giftServices->get($pushInfo->holiday_gift_id);
            if (!$activity || $activity->status != 1) {
                // 更新推送状态为失败
                $pushServices->update($pushId, ['status' => 2, 'push_time' => time()]);
                return true;
            }

            // 根据推送类型执行不同的推送
            $result = false;
            switch ($pushInfo->push_type) {
                case 1: // 短信
                    $result = $this->sendSms($pushInfo->uid, $activity->toArray());
                    break;
                case 2: // 公众号
                    $result = $this->sendWechat($pushInfo->uid, $activity->toArray());
                    break;
                case 3: // 弹框广告
                    $result = $this->sendPopup($pushInfo->uid, $activity->toArray());
                    break;
            }

            // 更新推送状态
            $pushServices->update($pushId, [
                'status' => $result ? 1 : 2,
                'push_time' => time()
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('节日有礼推送任务执行失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送短信
     * @param int $uid 用户ID
     * @param array $activity 活动信息
     * @return bool
     */
    protected function sendSms(int $uid, array $activity): bool
    {
        try {
            /** @var SmsSendServices $smsServices */
            $smsServices = app()->make(SmsSendServices::class);

            // 获取用户手机号
            $userInfo = app()->make(\app\services\user\UserServices::class)->getUserInfo($uid);
            if (!$userInfo || !$userInfo['phone']) {
                return false;
            }

            // 短信模板变量
            $data = [
                'activity_name' => $activity['name'],
                'gift_type' => $this->getGiftTypeName($activity['gift_type']),
                'date' => date('Y-m-d')
            ];

            // 发送短信
            return $smsServices->send($userInfo['phone'], $data, 'HOLIDAY_GIFT_NOTICE');
        } catch (\Throwable $e) {
            Log::error('节日有礼短信推送失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送公众号消息
     * @param int $uid 用户ID
     * @param array $activity 活动信息
     * @return bool
     */
    protected function sendWechat(int $uid, array $activity): bool
    {
        try {
            /** @var WechatServices $wechatServices */
            $wechatServices = app()->make(WechatServices::class);

            // 获取用户openid
            $userInfo = app()->make(\app\services\user\UserServices::class)->getUserInfo($uid);
            if (!$userInfo || !$userInfo['openid']) {
                return false;
            }

            // 消息内容
            $title = '节日有礼通知';
            $description = "亲爱的用户，您有一个节日礼物待领取\n\n活动名称：{$activity['name']}\n礼物类型：{$this->getGiftTypeName($activity['gift_type'])}\n有效期至：" . date('Y-m-d', $activity['end_time']);
            $url = sys_config('site_url') . '/pages/activity/holiday_gift/detail?id=' . $activity['id'];
            $image = $activity['wechat_image'] ?: sys_config('site_logo');

            // 发送图文消息
            return $wechatServices->sendTemplate($userInfo['openid'], $title, $description, $url, $image);
        } catch (\Throwable $e) {
            Log::error('节日有礼公众号推送失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送弹框广告
     * @param int $uid 用户ID
     * @param array $activity 活动信息
     * @return bool
     */
    protected function sendPopup(int $uid, array $activity): bool
    {
        try {
            // 创建弹框广告记录
            $popupData = [
                'uid' => $uid,
                'title' => '节日有礼',
                'image' => $activity['popup_image'] ?: '',
                'type' => 'holiday_gift',
                'link' => '/pages/activity/holiday_gift/detail?id=' . $activity['id'],
                'status' => 1,
                'add_time' => time(),
                'is_show' => 1,
                'sort' => 0
            ];

            // 保存弹框广告记录
            return (bool)app()->make(\app\services\user\UserPopupServices::class)->save($popupData);
        } catch (\Throwable $e) {
            Log::error('节日有礼弹框广告推送失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取礼物类型名称
     * @param string $giftType
     * @return string
     */
    protected function getGiftTypeName(string $giftType): string
    {
        $types = [
            '1' => '优惠券',
            '2' => '积分',
            '3' => '多倍积分',
            '4' => '余额',
            '5' => '全场包邮'
        ];

        $names = [];
        $giftTypes = explode(',', $giftType);
        foreach ($giftTypes as $type) {
            if (isset($types[$type])) {
                $names[] = $types[$type];
            }
        }

        return implode('、', $names) ?: '礼品';
    }
}
