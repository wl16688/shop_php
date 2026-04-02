<?php

namespace crmeb\basic;

use crmeb\services\AccessTokenServeService;

abstract class BaseAi extends BaseStorage
{
    /**
     * access_token
     * @var null
     */
    protected $accessToken = NULL;

    /**
     * BaseProduct constructor.
     * @param string $name
     * @param AccessTokenServeService $accessTokenServeService
     * @param string $configFile
     */
    public function __construct(string $name, AccessTokenServeService $accessTokenServeService, string $configFile)
    {
        parent::__construct($name, [], $configFile);
        $this->accessToken = $accessTokenServeService;
    }

    /**
     * 初始化
     * @param array $config
     * @return mixed|void
     */
    protected function initialize(array $config = [])
    {
    }
}
