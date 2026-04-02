<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\jobs\user\IntegralReleaseJob;
use think\facade\Queue;

/**
 * 积分释放定时任务
 * Class IntegralReleaseCommand
 * @package app\command
 */
class IntegralReleaseCommand extends Command
{
    /**
     * 配置指令
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('integral:release')
            ->setDescription('积分释放定时任务');
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output)
    {
        $output->writeln('开始执行积分释放任务...');
        
        try {
            // 推送积分释放任务到队列
            Queue::push(IntegralReleaseJob::class, [], 'integral_release');
            $output->writeln('积分释放任务已推送到队列');
        } catch (\Throwable $e) {
            $output->writeln('积分释放任务执行失败: ' . $e->getMessage());
            return 1;
        }
        
        $output->writeln('积分释放任务执行完成');
        return 0;
    }
}