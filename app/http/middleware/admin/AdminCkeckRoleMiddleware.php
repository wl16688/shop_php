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

namespace app\http\middleware\admin;

use app\Request;
use app\services\system\SystemRoleServices;
use crmeb\exceptions\AuthException;
use crmeb\interfaces\MiddlewareInterface;
use crmeb\utils\ApiErrorCode;

/**
 * 权限规则验证
 * Class AdminCkeckRoleMiddleware
 * @package app\http\middleware
 */
class AdminCkeckRoleMiddleware implements MiddlewareInterface
{

    public function handle(Request $request, \Closure $next)
    {
        if (!$request->adminId() || !$request->adminInfo())
            throw new AuthException(ApiErrorCode::ERR_ADMINID_VOID);
//        $data = [
//            'adminapi/channel/merchant/verify'
//        ];
//        $noData = [
//            'config/storage',
//            'print/form',
//            'edit_new_build/routine'
//        ];
//        $rule = trim(strtolower($request->rule()->getRule()));
//        foreach ($noData as $tiem){
//            if (strpos($rule, $tiem) !== false) {
//                return app('json')->fail('暂无权限');
//            }
//        }
//
//        if ($request->adminId() != 1 && in_array($request->method(), ['POST', 'PUT', 'DELETE']) && !in_array($rule, $data)){
//            return app('json')->fail('暂无权限');
//        }
        if ($request->adminInfo()['level']) {
            /** @var SystemRoleServices $systemRoleService */
            $systemRoleService = app()->make(SystemRoleServices::class);
            $systemRoleService->verifiAuth($request);
        }

        return $next($request);
    }
}
