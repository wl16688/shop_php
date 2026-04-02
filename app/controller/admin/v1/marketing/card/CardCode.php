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

namespace app\controller\admin\v1\marketing\card;

use app\controller\admin\AuthController;
use app\services\activity\card\CardCodeServices;
use think\annotation\Inject;


/**
 * 卡密
 * Class CardCode
 * @package app\admin\controller\card
 */
class CardCode extends AuthController
{

    /**
     * @var CardCodeServices
     */
    #[Inject]
    protected CardCodeServices $services;


    public function index()
    {
        $where = $this->request->getMore([
            ['field_key', ''],//卡号card_number,用户名nickname,uid
            ['keyword', ''],//关键字
            ['card_id', ''],//礼品卡 id
            ['batch_id', ''],//批次id
            ['status', ''],//状态: 0-未使用 1-已使用 2-已过期
            ['active_time', ''],//active_time
        ]);
        return $this->success($this->services->getList($where));
    }

    public function formRemark($id)
    {
        return $this->success($this->services->formRemark($id));
    }

    public function setRemark($id)
    {
        [$remark] = $this->request->postMore([
            ['remark', '']
        ], true);
        if (!$remark) {
            return $this->fail('请输入备注');
        }
        $info = $this->services->get($id);
        if (!$info) {
            return $this->fail('数据不存在');
        }
        $info->remark = $remark;
        if ($info->save()) {
            return $this->success('修改成功');
        } else {
            return $this->fail('修改失败');
        }
    }
}
