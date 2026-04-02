<?php

namespace crmeb\services\ai;

use crmeb\basic\BaseManager;
use crmeb\services\AccessTokenServeService;
use think\Container;
use think\facade\Config;

class Ai extends BaseManager
{
    /**
     * 空间名
     * @var string
     */
    protected string $namespace = '\\crmeb\\services\\ai\\storage\\';

    protected function getDefaultDriver()
    {
        return Config::get('ai.default', 'ai');
    }

    protected function invokeClass($class)
    {
        if (!class_exists($class)) {
            throw new \RuntimeException('class not exists: ' . $class);
        }

        $this->getConfigFile();

        if (!$this->config) {
            $this->config = Config::get($this->configFile . '.stores.' . $this->name, []);
        }
        $handleAccessToken = new AccessTokenServeService($this->config['access_key'] ?? '', $this->config['secret_key'] ?? '');
        $handle = Container::getInstance()->invokeClass($class, [$this->name, $handleAccessToken, $this->configFile]);
        $this->config = [];
        return $handle;
    }
}
