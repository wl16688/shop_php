<?php
/**
 *  +----------------------------------------------------------------------
 *  | CRMEB [ CRMEB赋能开发者，助力企业发展 ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2016~2022 https://www.crmeb.com All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed CRMEB并不是自由软件，未经许可不能去掉CRMEB相关版权
 *  +----------------------------------------------------------------------
 *  | Author: CRMEB Team <admin@crmeb.com>
 *  +----------------------------------------------------------------------
 */

namespace app\controller\api\v2;

use app\Request;
use think\facade\Cache;
use think\Response;

/**
 * Class Common
 * @package app\api\controller
 */
class Common
{
    /**
     * Redis配置项前缀
     */
    private const CONFIG_PREFIX = 'config:';
    /**
     * Redis配置项表示价格是否显示通票
     */
    private const CONFIG_PRICE_SHOW = 'ps';
    /**
     * Redis配置项表示设置积分释放比例
     */
    private const CONFIG_INTEGRAL_RELEASE_RATE = 'irr';

    /**
     * 获取Redis实例
     * @return \Redis
     */
    private function getRedis()
    {
        return Cache::store('redis')->handler();
    }

    /**
     * 测试接口
     * @return Response
     */
    public function test()
    {
        return app('json')->successful('测试成功', ['message' => 'Hello from v2.Common']);
    }

    /**
     * 设置配置项
     * @param Request $request
     * @return Response
     */
    public function setConfig(Request $request)
    {
        try {
            $key = $request->param('key', '');
            $value = $request->param('value', '');
            // $expire = $request->param('expire', 0); // 过期时间，0表示永不过期
            $expire = 0;

            if (empty($key)) {
                return app('json')->fail('配置项key不能为空');
            }
            if($key === self::CONFIG_PRICE_SHOW || $key === self::CONFIG_INTEGRAL_RELEASE_RATE){
                $redis = $this->getRedis();
                $redisKey = self::CONFIG_PREFIX . $key;

                if ($expire > 0) {
                    $result = $redis->setex($redisKey, $expire, $value);
                } else {
                    $result = $redis->set($redisKey, $value);
                }

                if ($result) {
                    return app('json')->successful('配置项设置成功', ['key' => $key, 'value' => $value]);
                } else {
                    return app('json')->fail('配置项设置失败');
                }
            }else{
                return app('json')->fail('配置项不支持');
            }
        } catch (\Exception $e) {
            return app('json')->fail('设置配置项异常：' . $e->getMessage());
        }
    }

    /**
     * 获取配置项
     * @param Request $request
     * @return Response
     */
    public function getConfig(Request $request)
    {
        try {
            $key = $request->param('key', '');

            if (empty($key)) {
                return app('json')->fail('配置项key不能为空');
            }

            $redis = $this->getRedis();
            $redisKey = self::CONFIG_PREFIX . $key;
            $value = $redis->get($redisKey);

            if ($value === false) {
                return app('json')->fail('配置项不存在');
            }

            // 获取TTL（剩余生存时间）
            // $ttl = $redis->ttl($redisKey);

            return app('json')->successful('获取配置项成功', [
                'key' => $key,
                'value' => $value
                // 'ttl' => $ttl > 0 ? $ttl : -1 // -1表示永不过期
            ]);
        } catch (\Exception $e) {
            return app('json')->fail('获取配置项异常：' . $e->getMessage());
        }
    }

    /**
     * 更新配置项
     * @param Request $request
     * @return Response
     */
    public function updateConfig(Request $request)
    {
        try {
            $key = $request->param('key', '');
            $value = $request->param('value', '');
            $expire = $request->param('expire', null); // null表示保持原有过期时间

            if (empty($key)) {
                return app('json')->fail('配置项key不能为空');
            }

            $redis = $this->getRedis();
            $redisKey = self::CONFIG_PREFIX . $key;

            // 检查key是否存在
            if (!$redis->exists($redisKey)) {
                return app('json')->fail('配置项不存在');
            }

            // 如果指定了过期时间，则设置新的过期时间
            if ($expire !== null) {
                if ($expire > 0) {
                    $result = $redis->setex($redisKey, $expire, $value);
                } else {
                    $redis->set($redisKey, $value);
                    $redis->persist($redisKey); // 移除过期时间
                    $result = true;
                }
            } else {
                // 保持原有过期时间
                $ttl = $redis->ttl($redisKey);
                if ($ttl > 0) {
                    $result = $redis->setex($redisKey, $ttl, $value);
                } else {
                    $result = $redis->set($redisKey, $value);
                }
            }

            if ($result) {
                return app('json')->successful('配置项更新成功', ['key' => $key, 'value' => $value]);
            } else {
                return app('json')->fail('配置项更新失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail('更新配置项异常：' . $e->getMessage());
        }
    }

    /**
     * 删除配置项
     * @param Request $request
     * @return Response
     */
    public function deleteConfig(Request $request)
    {
        try {
            $key = $request->param('key', '');

            if (empty($key)) {
                return app('json')->fail('配置项key不能为空');
            }

            $redis = $this->getRedis();
            $redisKey = self::CONFIG_PREFIX . $key;

            $result = $redis->del($redisKey);

            if ($result > 0) {
                return app('json')->successful('配置项删除成功', ['key' => $key]);
            } else {
                return app('json')->fail('配置项不存在或删除失败');
            }
        } catch (\Exception $e) {
            return app('json')->fail('删除配置项异常：' . $e->getMessage());
        }
    }

    /**
     * 获取所有配置项列表
     * @param Request $request
     * @return Response
     */
    public function getConfigList(Request $request)
    {
        try {
            $pattern = $request->param('pattern', '*'); // 支持模糊匹配
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 20);

            $redis = $this->getRedis();
            $searchPattern = self::CONFIG_PREFIX . $pattern;
            
            // 获取所有匹配的key
            $keys = $redis->keys($searchPattern);
            
            $total = count($keys);
            $offset = ($page - 1) * $limit;
            $pageKeys = array_slice($keys, $offset, $limit);
            
            $configs = [];
            foreach ($pageKeys as $redisKey) {
                $key = str_replace(self::CONFIG_PREFIX, '', $redisKey);
                $value = $redis->get($redisKey);
                $ttl = $redis->ttl($redisKey);
                
                $configs[] = [
                    'key' => $key,
                    'value' => $value,
                    'ttl' => $ttl === -1 ? '永不过期' : ($ttl === -2 ? '已过期' : $ttl . '秒'),
                    'redis_key' => $redisKey
                ];
            }

            return app('json')->successful('获取配置项列表成功', [
                'list' => $configs,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'pages' => ceil($total / $limit)
            ]);
        } catch (\Exception $e) {
            return app('json')->fail('获取配置项列表异常：' . $e->getMessage());
        }
    }

    /**
     * 批量删除配置项
     * @param Request $request
     * @return Response
     */
    public function batchDeleteConfig(Request $request)
    {
        try {
            $keys = $request->param('keys', []);

            if (empty($keys) || !is_array($keys)) {
                return app('json')->fail('请提供要删除的配置项key数组');
            }

            $redis = $this->getRedis();
            $redisKeys = array_map(function($key) {
                return self::CONFIG_PREFIX . $key;
            }, $keys);

            $result = $redis->del($redisKeys);

            return app('json')->successful("成功删除 {$result} 个配置项", [
                'deleted_count' => $result,
                'keys' => $keys
            ]);
        } catch (\Exception $e) {
            return app('json')->fail('批量删除配置项异常：' . $e->getMessage());
        }
    }

    /**
     * 检查配置项是否存在
     * @param Request $request
     * @return Response
     */
    public function existsConfig(Request $request)
    {
        try {
            $key = $request->param('key', '');

            if (empty($key)) {
                return app('json')->fail('配置项key不能为空');
            }

            $redis = $this->getRedis();
            $redisKey = self::CONFIG_PREFIX . $key;
            $exists = $redis->exists($redisKey);

            return app('json')->successful($exists ? '配置项存在' : '配置项不存在', [
                'key' => $key,
                'exists' => (bool)$exists
            ]);
        } catch (\Exception $e) {
            return app('json')->fail('检查配置项异常：' . $e->getMessage());
        }
    }

}
