<?php

declare (strict_types=1);

namespace app\controller\admin\v1\marketing\holiday;

use app\controller\admin\AuthController;
use app\services\activity\holiday\HolidayGiftServices;
use app\services\activity\holiday\HolidayGiftRecordServices;
use app\services\activity\holiday\HolidayGiftPushServices;
use think\annotation\Inject;

/**
 * 节日有礼控制器
 * Class HolidayGift
 * @package app\adminapi\controller\v1\activity
 */
class HolidayGift extends AuthController
{

    /**
     * @var HolidayGiftServices
     */
    #[Inject]
    protected HolidayGiftServices $services;


    /**
     * 节日有礼列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $where = $this->request->getMore([
            ['name', ''],
            ['status', ''],
            ['task_type', ''],
            ['time', '', '', 'gift_time'],
            ['is_del', 0],
            ['push_channels', ''],
        ]);
        return app('json')->success($this->services->getHolidayGiftList($where));
    }

    /**
     * 获取节日有礼详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $info = $this->services->getHolidayGiftInfo((int)$id);
        return app('json')->success($info);
    }

    /**
     * 获取并处理节日有礼数据
     * 从请求中获取节日有礼活动的所有参数，并进行数据处理和验证
     * @return array|mixed 成功返回处理后的数据数组，失败返回错误响应
     * @throws \Exception 当活动时间范围错误时抛出异常
     */
    private function getHolidayGiftData()
    {
        $data = $this->request->postMore([
            ['name', ''],                    // 活动名称
            ['is_permanent', 0],             // 是否永久：0-否，1-是
            ['start_time', []],              // 活动时间范围数组[开始时间, 结束时间]
            ['task_type', 0],                // 任务类型：1-用户生日，2-活动日期
            ['birthday_type', 0],            // 生日类型：1-生日当天，2-生日当周，3-生日当月，0-无
            ['activity_date_type', 0],       // 生日类型：活动日期类型：1-自定义日期，2-每月，3-每周
            ['activity_date', []],           // 自定义活动开始结束时间
            ['activity_month_days', []],     // 每月活动日期，多个用逗号分隔，如：1,2,4,12,27
            ['activity_week_days', []],      // 每周活动日期，多个用逗号分隔：1-7表示周一到周日
            ['advance_push', 0],             // 是否提前推送：0-否，1-是
            ['advance_days', 0],             // 提前推送天数
            ['day_num', 0],                  // 天数（备用字段）
            ['push_time_type', 1],           // 推送时段类型：1-全时段，2-指定时段
            ['push_start_time', ''],         // 推送开始时间，格式：HH:mm
            ['push_end_time', ''],           // 推送结束时间，格式：HH:mm
            ['push_user_type', 1],           // 推送人群类型：1-全部人群，2-指定人群
            ['user_level', 0],              // 用户等级数组，多个等级ID
            ['user_label', []],              // 用户标签数组，多个标签ID
            ['user_tag', []],                // 客户标签数组，多个标签ID
            ['condition_type', 1],           // 条件满足类型：1-满足任一条件，2-满足全部条件
            ['gift_type', ''],               // 赠送内容类型，多个用逗号分隔：1-优惠券，2-积分，3-多倍积分，4-余额，5-全场包邮
            ['coupon_ids', []],              // 优惠券ID数组，多个优惠券ID
            ['couponName', []],              // 优惠券名称数组（临时字段，后续会移除）
            ['integral', 0],                 // 赠送积分数量
            ['integral_multiple', 0],        // 积分倍数
            ['balance', 0],                  // 赠送余额
            ['push_channel', []],            // 推送渠道数组：1-短信，2-公众号，3-弹框广告
            ['wechat_image', []],            // 公众号推送图片链接数组
            ['push_frequency', 0],           // 推送频次：1-永久一次，2-每次进入，3-每天，4-每月，5-每周
            ['push_week_days', []],          // 每周推送星期几数组：1-7表示周一到周日
            ['show_page', []],               // 应用界面数组：1-商城首页，2-分类页，3-购物车，4-个人中心，5-支付成功，6-专题页面
            ['topic_ids', []],               // 专题页面ID数组，多个专题ID
            ['status', 0],                   // 状态：0-关闭，1-开启
        ]);
        // 处理时间范围
        $start_time = $data['start_time'];
        if ($start_time && is_array($data['start_time']) && count($data['start_time']) == 2) {
            $data['start_time'] = strtotime($start_time[0]);
            $data['end_time'] = strtotime($start_time[1]);
        }
        if (isset($data['activity_date']) && is_array($data['activity_date']) && count($data['activity_date']) == 2) {
            $data['activity_start_date'] = strtotime($data['activity_date'][0]);
            $data['activity_end_date'] = strtotime($data['activity_date'][1]);
        }

        // 处理推送频率
        if (isset($data['push_frequency']) && !is_numeric($data['push_frequency'])) {
            $data['push_frequency'] = intval($data['push_frequency']);
        }

        // 处理余额
        if (isset($data['balance']) && !is_numeric($data['balance'])) {
            $data['balance'] = floatval($data['balance']);
        }

        // 移除不需要的字段
        unset($data['couponName']);

        return $data;
    }

    /**
     * 保存节日有礼
     * @return mixed
     */
    public function save()
    {
        $data = $this->getHolidayGiftData();
        if (!is_array($data)) {
            return $data; // 返回错误信息
        }

        $this->services->saveHolidayGift($data);
        return app('json')->success('添加成功');
    }

    /**
     * 修改节日有礼
     * @param $id
     * @return mixed
     */
    public function update($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }

        $data = $this->getHolidayGiftData();
        if (!is_array($data)) {
            return $data; // 返回错误信息
        }

        $this->services->updateHolidayGift((int)$id, $data);
        return app('json')->success('修改成功');
    }

    /**
     * 删除节日有礼
     * @param $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $this->services->deleteHolidayGift((int)$id);
        return app('json')->success('删除成功');
    }

    /**
     * 修改节日有礼状态
     * @param $id
     * @param $status
     * @return mixed
     */
    public function setStatus($id, $status)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        $this->services->setStatus((int)$id, (int)$status);
        return app('json')->success($status == 1 ? '启用成功' : '关闭成功');
    }

    /**
     * 获取节日有礼领取记录列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recordList()
    {
        $where = $this->request->getMore([
            ['gift_id', 0],
            ['uid', 0],
            ['status', ''],
            ['receive_time', ''],
            ['is_del', 0],
        ]);
        /** @var HolidayGiftRecordServices $service */
        $service = app()->make(HolidayGiftRecordServices::class);
        return app('json')->success($service->getHolidayGiftRecordList($where));
    }

    /**
     * 获取节日有礼领取记录详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function recordDetail($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftRecordServices $service */
        $service = app()->make(HolidayGiftRecordServices::class);
        $info = $service->getHolidayGiftRecordInfo((int)$id);
        return app('json')->success($info);
    }

    /**
     * 删除节日有礼领取记录
     * @param $id
     * @return mixed
     */
    public function deleteRecord($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftRecordServices $service */
        $service = app()->make(HolidayGiftRecordServices::class);
        $service->deleteRecord((int)$id);
        return app('json')->success('删除成功');
    }

    /**
     * 批量删除节日有礼领取记录
     * @return mixed
     */
    public function batchDeleteRecord()
    {
        $ids = $this->request->post('ids');
        if (!$ids || !is_array($ids)) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftRecordServices $service */
        $service = app()->make(HolidayGiftRecordServices::class);
        $service->batchDeleteRecord($ids);
        return app('json')->success('删除成功');
    }

    /**
     * 获取节日有礼领取统计数据
     * @return mixed
     */
    public function recordStatistics()
    {
        $where = $this->request->getMore([
            ['gift_id', 0],
            ['is_del', 0],
        ]);
        /** @var HolidayGiftRecordServices $service */
        $service = app()->make(HolidayGiftRecordServices::class);
        $statistics = $service->getRecordStatistics($where);
        return app('json')->success($statistics);
    }

    /**
     * 获取节日有礼推送记录列表
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pushList()
    {
        $where = $this->request->getMore([
            ['gift_id', 0],
            ['uid', 0],
            ['push_type', ''],
            ['status', ''],
            ['push_time', ''],
            ['is_del', 0],
        ]);
        /** @var HolidayGiftPushServices $service */
        $service = app()->make(HolidayGiftPushServices::class);
        return app('json')->success($service->getHolidayGiftPushList($where));
    }

    /**
     * 获取节日有礼推送记录详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function pushDetail($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftPushServices $service */
        $service = app()->make(HolidayGiftPushServices::class);
        $info = $service->getHolidayGiftPushInfo((int)$id);
        return app('json')->success($info);
    }

    /**
     * 删除节日有礼推送记录
     * @param $id
     * @return mixed
     */
    public function deletePush($id)
    {
        if (!$id) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftPushServices $service */
        $service = app()->make(HolidayGiftPushServices::class);
        $service->deletePush((int)$id);
        return app('json')->success('删除成功');
    }

    /**
     * 批量删除节日有礼推送记录
     * @return mixed
     */
    public function batchDeletePush()
    {
        $ids = $this->request->post('ids');
        if (!$ids || !is_array($ids)) {
            return app('json')->fail('参数错误');
        }
        /** @var HolidayGiftPushServices $service */
        $service = app()->make(HolidayGiftPushServices::class);
        $service->batchDeletePush($ids);
        return app('json')->success('删除成功');
    }

    /**
     * 获取节日有礼推送统计数据
     * @return mixed
     */
    public function pushStatistics()
    {
        $where = $this->request->getMore([
            ['gift_id', 0],
            ['is_del', 0],
        ]);
        /** @var HolidayGiftPushServices $service */
        $service = app()->make(HolidayGiftPushServices::class);
        $statistics = $service->getPushStatistics($where);
        return app('json')->success($statistics);
    }

}
