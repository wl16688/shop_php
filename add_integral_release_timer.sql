-- 添加积分释放定时任务
INSERT INTO `eb_system_timer` (`name`, `mark`, `type`, `title`, `is_open`, `cycle`, `last_execution_time`, `update_execution_time`, `is_del`, `add_time`) 
VALUES ('积分释放定时任务', 'integral_release', 4, '每天凌晨1:30执行积分释放任务', 1, '1/30', 0, 0, 0, UNIX_TIMESTAMP());


-- 添加积分释放定时任务
INSERT INTO `eb_system_timer` (`name`, `mark`, `type`, `title`, `is_open`, `cycle`, `last_execution_time`, `update_execution_time`, `is_del`, `add_time`) 
VALUES ('积分释放定时任务', 'integral_release', 1, '每30分钟执行积分释放任务', 1, '30', 0, 0, 0, UNIX_TIMESTAMP());


-- 如果已经添加了记录，可以用这个SQL修改
UPDATE `eb_system_timer` 
SET `type` = 1, `cycle` = '30', `title` = '每30分钟执行积分释放任务' 
WHERE `mark` = 'integral_release';


-- 关闭任务
UPDATE `eb_system_timer` SET `is_open` = 0 WHERE `mark` = 'integral_release';

-- 开启任务  
UPDATE `eb_system_timer` SET `is_open` = 1 WHERE `mark` = 'integral_release';





-- 查看任务状态
SELECT * FROM `eb_system_timer` WHERE `mark` = 'integral_release';

-- 查看最后执行时间是否更新
SELECT `name`, `last_execution_time`, FROM_UNIXTIME(`last_execution_time`) as last_run 
FROM `eb_system_timer` WHERE `mark` = 'integral_release';








-- 现在关于如何让 IntegralReleaseJob.php 每天凌晨1:30执行一次，有以下几种方法：

-- ### 方法一：使用项目内置的定时任务系统（推荐）
-- 1. 1.
--    创建命令文件 ：已经创建了 `IntegralReleaseCommand.php`
-- 2. 2.
--    注册命令 ：已经在 `console.php` 中注册
-- 3. 3.
--    添加数据库记录 ：执行上面的SQL脚本添加定时任务记录
-- 4. 4.
--    启动定时器 ：运行以下命令启动项目的定时任务系统：
-- php think timer

-- ### 方法二：使用系统Cron（Linux/Unix）或任务计划程序（Windows）
-- Windows 任务计划程序：

-- 1. 1.
--    打开"任务计划程序"
-- 2. 2.
--    创建基本任务
-- 3. 3.
--    设置触发器为"每天"，时间为"01:30"
-- 4. 4.
--    设置操作为启动程序： php
-- 5. 5.
--    参数： think integral:release
-- 6. 6.
--    起始于： c:\workspace\trae.ai\yyyy
-- Linux Cron：
-- # 编辑crontab
-- crontab -e

-- # 添加以下行（每天凌晨1:30执行）
-- 30 1 * * * cd /path/to/your/project && php think integral:release