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

use app\dao\activity\card\CardGiftAuxiliaryDao;
use app\services\BaseServices;
use think\annotation\Inject;


/**
 * 礼品卡
 * Class CardGiftAuxiliaryServices
 * @package app\services\activity\card
 * @mixin CardGiftAuxiliaryDao
 */
class CardGiftAuxiliaryServices extends BaseServices
{

    /**
     * @var CardGiftAuxiliaryDao
     */
    #[Inject]
    protected CardGiftAuxiliaryDao $dao;
}
