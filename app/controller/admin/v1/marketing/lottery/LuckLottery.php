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
declare (strict_types=1);

namespace app\controller\admin\v1\marketing\lottery;

use think\annotation\Inject;
use app\controller\admin\AuthController;
use app\services\activity\lottery\LuckLotteryServices;

/**
 * 抽奖活动
 * Class LuckLottery
 * @package app\controller\admin\v1\marketing\lottery
 */
class LuckLottery extends AuthController
{

    /**
     * @var LuckLotteryServices
     */
    #[Inject]
    protected LuckLotteryServices $services;

    /**
     * 列表
     * @return mixed
     */
    public function index()
    {
        $where = $this->request->postMore([
            ['start', ''],
            ['status', ''],
            ['factor', ''],
            ['keyword', ''],
            ['time', '', '', 'time_ranges'],
        ]);
        return $this->success($this->services->getList($where));
    }

    /**
     * id查询详情
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function detail($id)
    {
        if (!$id) {
            return $this->fail('缺少参数id');
        }
        return $this->success($this->services->getlotteryInfo((int)$id));
    }

    /**
     * 活动类型查询详情
     * @param $factor
     * @return mixed
     */
    public function factorInfo($factor)
    {
        if (!$factor) {
            return app('json')->fail('缺少参数');
        }
        return app('json')->success($this->services->getlotteryFactorInfo((int)$factor));
    }

    /**
     * 添加
     * @return mixed
     */
    public function add()
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['desc', ''],
            ['image', ''],
            ['factor', 1],
            ['type', 1],
            ['factor_num', 1],
            ['attends_user', 1],
            ['user_level', []],
            ['user_label', []],
            ['is_svip', 0],
            ['period', [0, 0]],
            ['lottery_num_term', 1],
            ['lottery_num', 1],
            ['total_lottery_num', 1],
            ['spread_num', 1],
            ['is_all_record', 1],
            ['is_personal_record', 1],
            ['is_content', 1],
            ['status', 1],
            ['prize', []]
        ]);
        $data['content'] = $this->request->param('content', '');
        if (!$data['name']) {
            return $this->fail('请添加抽奖活动名称');
        }
        if ($data['is_content'] && !$data['content']) {
            return $this->fail('请添加抽奖描述等文案');
        }
        [$start, $end] = $data['period'];
        unset($data['period']);
        $data['start_time'] = $start ? strtotime($start) : 0;
        $data['end_time'] = $end ? strtotime($end) : 0;
        if ($data['start_time'] && $data['end_time'] && $data['end_time'] <= $data['start_time']) {
            return $this->fail('活动结束时间必须大于开始时间');
        }
        if (!$data['prize']) {
            return $this->fail('请添加奖品');
        }
        if (in_array($data['factor'], [1, 2]) && !$data['factor_num']) {
            return $this->fail('请填写消耗' . ($data['factor'] == '1' ? '积分' : '余额') . '数量');
        }
        if ($data['factor'] == 1 && $data['lottery_num'] > $data['total_lottery_num']) {
            return $this->fail('积分抽奖时每天抽奖次数不能大于总次数');
        }
        $this->services->add($data);
        return $this->success('保存成功');
    }

    /**
     * 修改
     * @param $id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
        $data = $this->request->postMore([
            ['name', ''],
            ['desc', ''],
            ['image', ''],
            ['factor', 1],
            ['type', 1],
            ['factor_num', 1],
            ['attends_user', 1],
            ['user_level', []],
            ['user_label', []],
            ['is_svip', 0],
            ['period', [0, 0]],
            ['lottery_num_term', 1],
            ['lottery_num', 1],
            ['spread_num', 1],
            ['total_lottery_num', 1],
            ['is_all_record', 1],
            ['is_personal_record', 1],
            ['is_content', 1],
            ['status', 1],
            ['prize', []]
        ]);
        $data['content'] = $this->request->param('content', '');
        if (!$id) {
            return $this->fail('缺少参数id');
        }
        if (!$data['name']) {
            return $this->fail('请添加抽奖活动名称');
        }
        [$start, $end] = $data['period'];
        unset($data['period']);
        $data['start_time'] = $start ? strtotime($start) : 0;
        $data['end_time'] = $end ? strtotime($end) : 0;
        if ($data['start_time'] && $data['end_time'] && $data['end_time'] <= $data['start_time']) {
            return $this->fail('活动结束时间必须大于开始时间');
        }
        if ($data['end_time'] < time()) {
            return $this->fail('活动结束时间必须大于当前时间');
        }
        if ($data['is_content'] && !$data['content']) {
            return $this->fail('请添加抽奖描述等文案');
        }
        if (!$data['prize']) {
            return $this->fail('请添加奖品');
        }
        if (in_array($data['factor'], [1, 2]) && !$data['factor_num']) {
            return $this->fail('请填写消耗' . ($data['factor'] == '1' ? '积分' : '余额') . '数量');
        }
        if ($data['factor'] == 1 && $data['lottery_num'] > $data['total_lottery_num']) {
            return $this->fail('积分抽奖时每天抽奖次数不能大于总次数');
        }
        $this->services->edit((int)$id, $data);
        return $this->success('保存成功');
    }

    /**
     * 删除
     * @param $id
     * @throws \Exception
     */
    public function delete()
    {
        [$id] = $this->request->getMore([
            ['id', 0],
        ], true);
        if (!$id) return $this->fail('数据不存在');
        $this->services->delLottery((int)$id);
        return $this->success('刪除成功！');
    }

    /**
     * 设置活动状态
     */
    public function setStatus($id = '', $status = '')
    {
        if ($status == '' || $id == '') {
            return $this->fail('缺少参数');
        }
        $res = $this->services->setStatus((int)$id, (int)$status);
        if (!$res) {
            return $this->fail('设置失败');
        }
        return $this->success($status == 1 ? '开启成功' : '关闭成功');
    }

    /**
     * 获取抽奖列表以及正在使用的抽奖活动
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/3/18
     */
    public function factorList()
    {
        return $this->success($this->services->factorList());
    }

    /**
     * 活动类型使用
     * @return \think\Response
     * @author wuhaotian
     * @email 442384644@qq.com
     * @date 2025/3/18
     */
    public function factorUse()
    {
        $data = $this->request->postMore([
            [['point', 'd'], 0],
            [['pay', 'd'], 0],
            [['evaluate', 'd'], 0],
            [['follow', 'd'], 0],
        ]);
        $this->services->factorUse($data);
        return app('json')->success('保存成功');
    }
}
