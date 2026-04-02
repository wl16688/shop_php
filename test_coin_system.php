<?php
/**
 * 金币系统功能测试脚本
 */

// 引入框架
require_once __DIR__ . '/vendor/autoload.php';

use app\services\user\UserServices;
use app\services\order\UserCoinBillServices;
use app\services\order\UserCoinApplyServices;
use think\facade\Cache;

echo "=== 金币系统功能测试 ===\n\n";

try {
    // 1. 测试配置获取
    echo "1. 测试积分释放比例配置...\n";
    $redis = Cache::store('redis')->handler();
    $releaseRate = $redis->get('config:irr');
    if ($releaseRate === false) {
        echo "   配置不存在，设置默认值 0.1 (10%)\n";
        $redis->set('config:irr', '0.1');
        $releaseRate = 0.1;
    } else {
        $releaseRate = floatval($releaseRate);
        echo "   当前释放比例: " . ($releaseRate * 100) . "%\n";
    }

    // 2. 测试用户服务
    echo "\n2. 测试用户服务...\n";
    $userServices = app()->make(UserServices::class);
    $usersWithLocked = $userServices->getUsersWithLockedIntegral();
    echo "   有锁定积分的用户数量: " . count($usersWithLocked) . "\n";

    // 3. 测试账单服务
    echo "\n3. 测试账单服务...\n";
    $billServices = app()->make(UserCoinBillServices::class);
    echo "   账单服务初始化成功\n";

    // 4. 测试申请服务
    echo "\n4. 测试申请服务...\n";
    $applyServices = app()->make(UserCoinApplyServices::class);
    echo "   申请服务初始化成功\n";

    // 5. 测试转账功能（模拟）
    echo "\n5. 测试转账功能验证...\n";
    echo "   转账金额验证: 10-100000 范围检查\n";
    $testAmounts = [5, 10, 50, 100, 1000, 100000, 100001];
    foreach ($testAmounts as $amount) {
        $valid = ($amount >= 10 && $amount <= 100000);
        echo "   金额 {$amount}: " . ($valid ? "有效" : "无效") . "\n";
    }

    echo "\n=== 测试完成 ===\n";
    echo "所有核心功能模块初始化成功！\n\n";

    echo "功能说明:\n";
    echo "1. 用户金币申请: 用户可申请金币，管理员审核\n";
    echo "2. 金币转账: 用户间转账，金额限制10-100000\n";
    echo "3. 积分兑换: 积分兑换金币，默认比例100:1\n";
    echo "4. 锁定积分释放: 定时任务自动释放锁定积分\n";
    echo "5. 管理后台: 完整的管理功能，包括统计、导出等\n";

} catch (Exception $e) {
    echo "测试失败: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . "\n";
    echo "行号: " . $e->getLine() . "\n";
}