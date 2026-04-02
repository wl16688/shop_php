<?php

declare (strict_types=1);

namespace app\controller\api\v1\activity;

use app\services\activity\bargain\StoreBargainServices;
use app\services\activity\holiday\HolidayGiftServices;
use app\services\activity\holiday\HolidayGiftRecordServices;
use app\services\activity\holiday\HolidayGiftPushServices;
use app\Request;
use app\services\user\UserServices;
use think\annotation\Inject;
use think\facade\App;

/**
 * 节日有礼控制器
 * Class HolidayGiftController
 * @package app\api\controller\v1\activity
 */
class HolidayGift
{
    /**
     * @var HolidayGiftServices
     */
    #[Inject]
    protected HolidayGiftServices $services;


    /**
     * 获取用户可领取的节日有礼列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList(Request $request)
    {
        $uid = (int)$request->uid();
        if (!$uid) {
            return app('json')->success([]);
        }

        // 获取活动中的节日有礼
        $where = [
            'status' => 1,
            'is_del' => 0,
        ];
        $list = $this->services->getActiveHolidayGift($where);
        if (!$list) {
            return app('json')->success([]);
        }
        $holidayGiftPushServices = app()->make(HolidayGiftPushServices::class);
        // 过滤不符合条件的活动
        $result = [];
        foreach ($list as $item) {
            //检查提前推送是否在日期内
            if (!$holidayGiftPushServices->checkAdvancePush($item, false, $uid)) {
                continue;
            }

            // 检查用户是否符合条件
            if (!$this->services->checkUserCondition($uid, $item)) {
                continue;
            }

            // 检查是否在推送时段内
            if (!$this->services->checkPushTimeRange($item)) {
                continue;
            }
            //检查推送频次是否推送过
            if (!$this->services->checkPopupAdCondition($uid, $item)) {
                continue;
            }

            // 添加到结果中
            $result[] = [
                'id' => $item['id'],
                'name' => $item['name'],
                'gift_type' => $item['gift_type'],
                'push_channel' => $item['push_channel'],
                'show_page' => $item['show_page'],
                'push_frequency' => $item['push_frequency'],
                'wechat_image' => $item['wechat_image'],
                'end_time' => $item['end_time'],
                'end_time_format' => date('Y-m-d H:i:s', $item['end_time'])
            ];
        }

        return app('json')->success(['list' => $result]);
    }


    /**
     * 推送记录
     * @return void
     * User: liusl
     * DateTime: 2025/7/21 15:27
     */
    public function record(Request $request, HolidayGiftPushServices $services)
    {
        $uid = (int)$request->uid();
        [$ids] = $request->postMore([['ids', '']], true);
        if (!$ids || !$uid) {
            return app('json')->fail('参数错误');
        }
        $ids = explode(',', $ids);
        $data = [
            'push_type' => 3,
            'status' => 1
        ];
        $result = $services->saveGiftPush($ids, $uid, $data);
        return app('json')->success($result ? '推送成功' : '推送失败');
    }
}
