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

namespace app\dao\activity\card;

use app\dao\BaseDao;
use app\model\activity\card\CardGift;

/**
 * 礼品卡
 * Class CardGiftDao
 * @package app\dao\activity\card
 */
class CardGiftDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return CardGift::class;
    }

    public function getList(array $where, string $field = '*', int $page = 0, int $limit = 0, string $order = '')
    {
        return $this->search($where)
            ->field($field)
            ->when($page && $limit, function ($query) use ($page, $limit) {
                $query->page($page, $limit);
            })
            ->when($order, function ($query) use ($order) {
                $query->order($order);
            })
            ->select()
            ->toArray();
    }

}
