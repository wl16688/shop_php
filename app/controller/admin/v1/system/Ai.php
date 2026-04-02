<?php

namespace app\controller\admin\v1\system;

use app\controller\admin\AuthController;
use app\services\system\AiServices;
use think\annotation\Inject;

class Ai extends AuthController
{
    /**
     * @var AiServices
     */
    #[Inject]
    protected AiServices $services;

    public function chatAi()
    {
        $data = $this->request->postMore([
            ['message', ''],
            ['store_name', ''],
            ['product_id', 0],
            ['unique', ''],
            ['type', '']
        ]);
        $result = $this->services->AiType($data);
        return $this->success($result);
    }
}
