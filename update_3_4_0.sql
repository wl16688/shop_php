--
-- 表的结构 `eb_app_version`
--

DROP TABLE IF EXISTS `eb_app_version`;
CREATE TABLE `eb_app_version`
(
    `id`       int           NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `version`  varchar(20)   NOT NULL DEFAULT '' COMMENT '版本号',
    `platform` tinyint(1)    NOT NULL DEFAULT '0' COMMENT '平台类型:1.安卓 2.IOS',
    `info`     text COMMENT '升级信息',
    `url`      varchar(1000) NOT NULL DEFAULT '' COMMENT '下载链接',
    `is_force` tinyint(1)    NOT NULL DEFAULT '1' COMMENT '是否强制升级',
    `is_new`   tinyint(1)    NOT NULL DEFAULT '0' COMMENT '是否最新',
    `is_del`   tinyint(1)    NOT NULL DEFAULT '0' COMMENT '是否删除',
    `add_time` int           NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb3 COMMENT ='APP版本表';

DROP TABLE IF EXISTS `eb_holiday_gift`;
CREATE TABLE `eb_holiday_gift`
(
    `id`                  int            NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `name`                varchar(255)   NOT NULL DEFAULT '' COMMENT '活动名称',
    `is_permanent`        tinyint(1)     NOT NULL DEFAULT '0' COMMENT '是否永久：0-否，1-是',
    `start_time`          int            NOT NULL DEFAULT '0' COMMENT '开始时间',
    `end_time`            int            NOT NULL DEFAULT '0' COMMENT '结束时间',
    `task_type`           tinyint(1)     NOT NULL DEFAULT '1' COMMENT '任务类型：1-用户生日，2-活动日期',
    `birthday_type`       tinyint(1)     NOT NULL DEFAULT '1' COMMENT '生日类型：1-生日当天，2-生日当周，3-生日当月，0-无',
    `activity_date_type`  tinyint(1)     NOT NULL DEFAULT '1' COMMENT '活动日期类型：1-自定义日期，2-每月，3-每周',
    `activity_start_date` varchar(10)    NOT NULL DEFAULT '' COMMENT '自定义活动开始日期，格式：MM-DD（仅当activity_date_type=1时使用）',
    `activity_end_date`   varchar(10)    NOT NULL DEFAULT '' COMMENT '自定义活动结束日期，格式：MM-DD（仅当activity_date_type=1时使用）',
    `activity_month_days` varchar(255)   NOT NULL DEFAULT '' COMMENT '每月活动日期，多个用逗号分隔，如：1,2,4,12,27',
    `activity_week_days`  varchar(255)   NOT NULL DEFAULT '' COMMENT '每周活动日期，多个用逗号分隔：1-7表示周一到周日',
    `advance_push`        tinyint(1)     NOT NULL DEFAULT '0' COMMENT '是否提前推送：0-否，1-是',
    `advance_days`        int            NOT NULL DEFAULT '0' COMMENT '提前推送天数',
    `push_time_type`      tinyint(1)     NOT NULL DEFAULT '1' COMMENT '推送时段类型：1-全时段，2-指定时段',
    `push_start_time`     varchar(10)    NOT NULL DEFAULT '' COMMENT '推送开始时间，格式：HH:mm',
    `push_end_time`       varchar(10)    NOT NULL DEFAULT '' COMMENT '推送结束时间，格式：HH:mm',
    `push_user_type`      tinyint(1)     NOT NULL DEFAULT '1' COMMENT '推送人群类型：1-全部人群，2-指定人群',
    `user_level`          varchar(255)   NOT NULL DEFAULT '0' COMMENT '用户等级，多个用逗号分隔',
    `user_label`          varchar(255)   NOT NULL DEFAULT '' COMMENT '用户标签，多个用逗号分隔',
    `user_tag`            varchar(255)   NOT NULL DEFAULT '' COMMENT '客户标签，多个用逗号分隔',
    `condition_type`      tinyint(1)     NOT NULL DEFAULT '1' COMMENT '条件满足类型：1-满足任一条件，2-满足全部条件',
    `gift_type`           varchar(255)   NOT NULL DEFAULT '' COMMENT '赠送内容类型，多个用逗号分隔：1-优惠券，2-积分，3-多倍积分，4-余额，5-全场包邮',
    `coupon_ids`          varchar(255)   NOT NULL DEFAULT '' COMMENT '优惠券ID，多个用逗号分隔',
    `integral`            int            NOT NULL DEFAULT '0' COMMENT '赠送积分数量',
    `integral_multiple`   int            NOT NULL DEFAULT '1' COMMENT '积分倍数',
    `balance`             decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '赠送余额',
    `push_channel`        varchar(255)   NOT NULL DEFAULT '' COMMENT '推送渠道，多个用逗号分隔：1-短信，2-公众号，3-弹框广告',
    `wechat_image`        varchar(2000)  NOT NULL DEFAULT '' COMMENT '公众号推送图片链接',
    `push_frequency`      tinyint(1)     NOT NULL DEFAULT '1' COMMENT '推送频次：1-永久一次，2-每次进入，3-每天，4-每月，5-每周',
    `push_week_days`      varchar(255)   NOT NULL DEFAULT '' COMMENT '每周推送星期几，多个用逗号分隔：1-7表示周一到周日',
    `show_page`           varchar(255)   NOT NULL DEFAULT '' COMMENT '应用界面，多个用逗号分隔：1-商城首页，2-分类页，3-购物车，4-个人中心，5-支付成功，6-专题页面',
    `topic_ids`           varchar(255)   NOT NULL DEFAULT '' COMMENT '专题页面ID，多个用逗号分隔',
    `status`              tinyint(1)     NOT NULL DEFAULT '0' COMMENT '状态：0-关闭，1-开启',
    `is_del`              tinyint(1)     NOT NULL DEFAULT '0' COMMENT '是否删除：0-否，1-是',
    `add_time`            int            NOT NULL DEFAULT '0' COMMENT '添加时间',
    `update_time`         int            NOT NULL DEFAULT '0' COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_status` (`status`),
    KEY `idx_task_type` (`task_type`),
    KEY `idx_start_end_time` (`start_time`, `end_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='节日有礼活动表';

DROP TABLE IF EXISTS `eb_holiday_gift_push`;
CREATE TABLE `eb_holiday_gift_push`
(
    `id`           int        NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `gift_id`      int        NOT NULL DEFAULT '0' COMMENT '节日有礼活动ID',
    `uid`          int        NOT NULL DEFAULT '0' COMMENT '用户ID',
    `push_time`    int        NOT NULL DEFAULT '0' COMMENT '推送时间',
    `push_type`    tinyint(1) NOT NULL DEFAULT '1' COMMENT '推送类型：1-短信，2-公众号，3-弹框广告',
    `push_content` text COMMENT '推送内容',
    `status`       tinyint(1) NOT NULL DEFAULT '0' COMMENT '状态：0-推送失败，1-推送成功',
    `add_time`     int        NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`),
    KEY `idx_gift_id` (`gift_id`),
    KEY `idx_uid` (`uid`),
    KEY `idx_push_time` (`push_time`),
    KEY `idx_push_type` (`push_type`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='节日有礼推送记录表';


DROP TABLE IF EXISTS `eb_holiday_gift_record`;
CREATE TABLE `eb_holiday_gift_record`
(
    `id`           int           NOT NULL AUTO_INCREMENT COMMENT '自增ID',
    `gift_id`      int           NOT NULL DEFAULT '0' COMMENT '节日有礼活动ID',
    `uid`          int           NOT NULL DEFAULT '0' COMMENT '用户ID',
    `receive_time` int           NOT NULL DEFAULT '0' COMMENT '领取时间',
    `gift_type`    varchar(255)  NOT NULL DEFAULT '' COMMENT '领取的礼物类型，多个用逗号分隔',
    `add_time`     int           NOT NULL DEFAULT '0' COMMENT '添加时间',
    `gift_content` varchar(2000) NOT NULL DEFAULT '' COMMENT '活动详情',
    PRIMARY KEY (`id`),
    KEY `idx_gift_id` (`gift_id`),
    KEY `idx_uid` (`uid`),
    KEY `idx_receive_time` (`receive_time`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='节日有礼领取记录表';


DROP TABLE IF EXISTS `eb_user_extend`;
CREATE TABLE `eb_user_extend`
(
    `id`          int          NOT NULL AUTO_INCREMENT COMMENT '主键ID',
    `uid`         int          NOT NULL DEFAULT '0' COMMENT '用户ID',
    `field_name`  varchar(100) NOT NULL DEFAULT '' COMMENT '字段名称',
    `field_value` text         NOT NULL COMMENT '字段值',
    `add_time`    int          NOT NULL DEFAULT '0' COMMENT '添加时间',
    PRIMARY KEY (`id`) USING BTREE,
    KEY `uid` (`uid`) USING BTREE,
    KEY `field_name` (`field_name`) USING BTREE,
    KEY `uid_field_name` (`uid`, `field_name`) USING BTREE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_0900_ai_ci COMMENT ='用户扩展数据表';


DROP TABLE IF EXISTS `eb_user_label_extend`;
CREATE TABLE `eb_user_label_extend`
(
    `id`                  int            NOT NULL AUTO_INCREMENT,
    `label_id`            int            NOT NULL DEFAULT '0' COMMENT '关联的标签ID',
    `rule_type`           tinyint(1)     NOT NULL DEFAULT '0' COMMENT '规则类型:1=>商品,2=>资产,3=>交易,4=>客户(所有)',
    `sub_type`            tinyint(1)     NOT NULL DEFAULT '0' COMMENT '规则子类型:1=>积分,2=>余额(资产)',
    `balance_type`        tinyint(1)     NOT NULL DEFAULT '0' COMMENT '余额类型:1=>充值,2=>消耗(资产)',
    `operation_type`      tinyint(1)     NOT NULL DEFAULT '0' COMMENT '次数/金额:1=>次数,2=>金额',
    `time_dimension`      tinyint(1)     NOT NULL DEFAULT '0' COMMENT '时间维度:1=>历史,2=>最近,3=>累计(商品/资产/交易)',
    `product_ids`         varchar(255)   NOT NULL DEFAULT '' COMMENT '商品ids',
    `time_value`          int            NOT NULL DEFAULT '0' COMMENT '时间值（天）(商品/资产/交易)',
    `specify_dimension`   tinyint(1)     NOT NULL DEFAULT '0' COMMENT '商品维度: 1=>商品,2=>分类,3=>标签',
    `amount_value_min`    decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '金额/数值最小（金额/积分）',
    `amount_value_max`    decimal(10, 2) NOT NULL DEFAULT '0.00' COMMENT '金额/数值最大（金额/积分）',
    `operation_times_min` int            NOT NULL DEFAULT '0' COMMENT '操作次数最小（充值/消耗）',
    `operation_times_max` int            NOT NULL DEFAULT '0' COMMENT '操作次数最大（充值/消耗）',
    `customer_identity`   tinyint(1)     NOT NULL DEFAULT '0' COMMENT '客户身份:1=>注册时间,2=>访问时间,3=>用户等级, 4=>客户身份',
    `customer_num`        int            NOT NULL DEFAULT '0' COMMENT '客户身份数据(注册时间,访问时间,用户等级,用户身份(1=>等级会员,2=>付费会员,3=>推广员,4=>采购商))',
    `customer_time_start` int            NOT NULL DEFAULT '0' COMMENT '开始时间',
    `customer_time_end`   int            NOT NULL DEFAULT '0' COMMENT '结束时间',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_general_ci COMMENT ='标签规则关联表';

DROP TABLE IF EXISTS `eb_user_label_extend_relation`;
CREATE TABLE `eb_user_label_extend_relation`
(
    `id`       int        NOT NULL AUTO_INCREMENT,
    `type`     tinyint(1) NOT NULL COMMENT '类型:1=>商品,2=>分类,3=>标签',
    `left_id`  int        NOT NULL DEFAULT '0' COMMENT '关联标签',
    `right_id` int        NOT NULL DEFAULT '0' COMMENT '关联 id',
    PRIMARY KEY (`id`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT ='标签规则扩展表';



INSERT INTO `eb_system_config` (`id`, `is_store`, `menu_name`, `type`, `input_type`, `config_tab_id`, `parameter`,
                                `upload_type`, `required`, `width`, `high`, `value`, `info`, `desc`, `sort`, `status`)
VALUES (null, 0, 'brokerage_apply_phone_verify', 'radio', '', 76, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '手机号验证',
        '分销员申请是否需要短信验证手机号', 0, 1),
       (null, 0, 'brokerage_apply_explain', 'textarea', '', 76, '', 1, '', 100, 5, '\"\"', '分销申请说明',
        '分销权限前端申请时候显示的申请说明', 0, 1),
       (null, 0, 'division_apply_phone_verify', 'radio', '', 26, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '申请手机号验证',
        '代理商申请是否需要短信验证手机号', 0, 1),
       (null, 0, 'division_apply_explain', 'textarea', '', 26, '', 1, '', 100, 5, '\"\"', '代理商申请说明',
        '代理商前端申请时候显示的申请说明', 0, 1),
       (null, 0, 'channel_apply_phone_verify', 'radio', '', 27, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '申请手机号验证',
        '采购商申请时是否开启手机号验证', 0, 1),
       (null, 0, 'channel_apply_explain', 'textarea', '', 27, '', 1, '', 100, 5, '\"\"', '采购商申请说明',
        '采购商申请时候显示的申请说明', 0, 1),
       (null, 0, 'supplier_apply_phone_verify', 'radio', '', 27, '1=>开启\n0=>关闭', 1, '', 0, 0, '0', '申请手机号验证',
        '供应商申请时是否开启手机号验证', 0, 1),
       (null, 0, 'supplier_apply_explain', 'textarea', '', 27, '', 1, '', 100, 5, '\"\"', '供应商申请说明',
        '供应商申请时候显示的申请说明', 0, 1);



ALTER TABLE `eb_store_order_status`
    ADD `change_manager` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '操作人' AFTER `change_message`;

ALTER TABLE `eb_store_product_attr_value`
    ADD `sort` INT(11) NOT NULL DEFAULT '0' COMMENT '排序' AFTER `is_show`;


ALTER TABLE `eb_user_label`
    ADD `label_type`   tinyint(1) NOT NULL DEFAULT '1' COMMENT '标签类型,1:手动;2 自动' AFTER `tag_id`,
    ADD `is_product`   tinyint(1) NOT NULL DEFAULT '0' COMMENT '商品条件,1 开启 0 关闭' AFTER `label_type`,
    ADD `is_property`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '资产条件,1 开启 0 关闭' AFTER `is_product`,
    ADD `is_trade`     tinyint(1) NOT NULL DEFAULT '0' COMMENT '交易条件,1 开启0 关闭' AFTER `is_property`,
    ADD `is_customer`  tinyint(1) NOT NULL DEFAULT '0' COMMENT '客户条件,1 开启 0 关闭' AFTER `is_trade`,
    ADD `is_condition` tinyint(1) NOT NULL DEFAULT '1' COMMENT '条件类型,1 条件满足任一,2全部条件满足' AFTER `is_customer`,
    ADD `status`       tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态' AFTER `is_condition`,
    ADD `add_time`     int        NOT NULL DEFAULT '0' AFTER `status`,

-- 修改字段
    ALTER TABLE `eb_user` MODIFY column `birthday` varchar (16) NOT NULL DEFAULT '' COMMENT '生日';
