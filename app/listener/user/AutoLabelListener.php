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

namespace app\listener\user;

use app\jobs\user\UserAutoLabelJob;
use crmeb\interfaces\ListenerInterface;

/**
 * 自动打标签事件监听器
 * Class AutoLabelListener
 * @package app\listener\user
 */
class AutoLabelListener implements ListenerInterface
{
    /**
     * 处理自动打标签事件
     * @param $event
     * @return void
     */
    public function handle($event): void
    {
        [$uid, $trigger_type, $trigger_data, $label_ids] = $event;

        // 调用队列处理自动标签
        UserAutoLabelJob::dispatch([$uid, $trigger_type, $trigger_data, $label_ids]);
    }
}
