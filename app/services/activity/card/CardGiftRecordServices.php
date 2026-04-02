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

namespace app\services\activity\card;

use app\dao\activity\card\CardGiftRecordDao;
use app\services\BaseServices;
use app\services\system\admin\SystemAdminServices;
use think\annotation\Inject;


/**
 * 礼品卡
 * Class CardGiftServices
 * @package app\services\activity\card
 * @mixin CardGiftRecordDao
 */
class CardGiftRecordServices extends BaseServices
{

    /**
     * @var CardGiftRecordDao
     */
    #[Inject]
    protected CardGiftRecordDao $dao;

    /**
     * 列表
     * @param array $where
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * User: liusl
     * DateTime: 2025/5/12 09:37
     */
    public function getList(array $where)
    {
        [$page, $limit] = $this->getPageValue();
        $where['is_del'] = 0;
        $list = $this->dao->getList($where, '*', $page, $limit, 'add_time desc');
        $count = $this->dao->count($where);
        $adminIds = array_column($list, 'admin_id');
        $batchList = app()->make(SystemAdminServices::class)->search(['id' => $adminIds])->column('real_name', 'id');
        foreach ($list as &$item) {
            $item['admin_name'] = $batchList[$item['admin_id']] ?? '';
        }
        return compact('list', 'count');
    }
}
