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
use app\model\activity\card\CardGiftAuxiliary;

/**
 * 礼品卡
 * Class CardGiftAuxiliaryDao
 * @package app\dao\activity\card
 */
class CardGiftAuxiliaryDao extends BaseDao
{

    /**
     * 设置模型
     * @return string
     */
    protected function setModel(): string
    {
        return CardGiftAuxiliary::class;
    }

}
