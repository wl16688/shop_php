<?php

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

/**
 * 定时任务基类
 * Class Timer
 * @package app\command
 */
class Timer extends Command
{
    /**
     * 配置指令
     */
    protected function configure()
    {
        // 指令配置
        $this->setName('timer')
            ->setDescription('定时任务');
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @return int
     */
    protected function execute(Input $input, Output $output)
    {
        $this->task($output);
        return 0;
    }

    /**
     * 定时任务
     * @param Output $output
     */
    protected function task(Output $output)
    {
        $time = time();
        $prefix = config('database.connections.' . config('database.default') . '.prefix');
        $list = Db::name('system_timer')->where('is_open', 1)->select();
        if (!$list) return;
        foreach ($list as $item) {
            if ($time < $item['last_execution_time'] + $item['execution_cycle']) {
                continue;
            }
            $output->writeln('执行定时任务:' . $item['name']);
            $this->runTimer($item['command'], $output);
            Db::name('system_timer')->where('id', $item['id'])->update(['last_execution_time' => time()]);
        }
    }

    /**
     * 执行定时任务
     * @param string $command
     * @param Output $output
     */
    protected function runTimer(string $command, Output $output)
    {
        $class = '\\app\\command\\' . $command;
        if (class_exists($class)) {
            try {
                /** @var Command $instance */
                $instance = app()->make($class);
                $instance->run(new Input([]), $output);
            } catch (\Throwable $e) {
                $output->writeln('执行定时任务失败:' . $e->getMessage());
            }
        } else {
            $output->writeln('定时任务不存在:' . $command);
        }
    }
}