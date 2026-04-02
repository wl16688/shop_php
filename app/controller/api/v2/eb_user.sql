/*
 Navicat Premium Data Transfer

 Source Server         : localhost
 Source Server Type    : MySQL
 Source Server Version : 80041
 Source Host           : localhost:3306
 Source Schema         : ttt

 Target Server Type    : MySQL
 Target Server Version : 80041
 File Encoding         : 65001

 Date: 23/10/2025 21:03:35
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for eb_user
-- ----------------------------
DROP TABLE IF EXISTS `eb_user`;
CREATE TABLE `eb_user`  (
  `uid` int unsigned NOT NULL COMMENT '用户id',
  `account` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户账号',
  `pwd` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户密码',
  `real_name` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '真实姓名',
  `birthday` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '生日',
  `card_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '身份证号码',
  `mark` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户备注',
  `partner_id` int(0) NOT NULL DEFAULT 0 COMMENT '合伙人id',
  `group_id` int(0) NOT NULL DEFAULT 0 COMMENT '用户分组id',
  `nickname` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户昵称',
  `avatar` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户头像',
  `phone` char(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '手机号码',
  `add_time` int unsigned NOT NULL COMMENT '添加时间',
  `add_ip` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '添加ip',
  `last_time` int unsigned NOT NULL COMMENT '最后一次登录时间',
  `last_ip` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '最后一次登录ip',
  `now_money` decimal(12, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '用户余额',
  `brokerage_price` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '佣金金额',
  `integral` int unsigned NOT NULL COMMENT '用户剩余积分',
  `integral_lock` int(0) NOT NULL DEFAULT 0 COMMENT '用户锁定积分',
  `coin_num` decimal(20, 4) NOT NULL DEFAULT 0.0000 COMMENT '用户通证余额',
  `exp` decimal(12, 2) NOT NULL DEFAULT 0.00 COMMENT '会员经验',
  `sign_num` int(0) NOT NULL DEFAULT 0 COMMENT '连续签到天数',
  `sign_remind` tinyint(1) NOT NULL DEFAULT 0 COMMENT '签到提醒状态',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1为正常，0为禁止',
  `level` int(0) NOT NULL DEFAULT 0 COMMENT '等级',
  `agent_level` int(0) NOT NULL DEFAULT 0 COMMENT '分销等级',
  `spread_open` tinyint(1) NOT NULL DEFAULT 1 COMMENT '是否有推广资格',
  `spread_uid` int unsigned NOT NULL COMMENT '推广元id',
  `spread_time` int unsigned NOT NULL COMMENT '推广员关联时间',
  `spread_lottery` int(0) NOT NULL DEFAULT 1 COMMENT '推广获取抽奖次数',
  `work_uid` int(0) NOT NULL DEFAULT 0 COMMENT '绑定企业微信成员uid',
  `work_userid` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '绑定企业微信成员uid',
  `user_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户类型',
  `is_promoter` tinyint unsigned NOT NULL COMMENT '是否为推广员',
  `pay_count` int unsigned NOT NULL COMMENT '用户购买次数',
  `spread_count` int(0) NOT NULL DEFAULT 0 COMMENT '下级人数',
  `clean_time` int(0) NOT NULL DEFAULT 0 COMMENT '清理会员时间',
  `addres` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '详细地址',
  `adminid` int unsigned NOT NULL COMMENT '管理员编号 ',
  `login_type` varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '用户登陆类型，h5,wechat,routine',
  `login_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '登录城市',
  `record_phone` varchar(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '记录临时电话',
  `is_money_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT '会员来源  0: 购买商品升级   1：花钱购买的会员2: 会员卡领取',
  `is_ever_level` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否永久性会员  0: 非永久会员  1：永久会员',
  `overdue_time` int(0) NOT NULL DEFAULT 0 COMMENT '会员到期时间',
  `uniqid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `bar_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '条形码值',
  `rand_code` int(0) NOT NULL DEFAULT 0 COMMENT '随机code，用于确认余额支付',
  `sex` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0:其他,1:男,2:女',
  `provincials` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '省市区',
  `province` int(0) NOT NULL DEFAULT 0 COMMENT '省ID',
  `city` int(0) NOT NULL DEFAULT 0 COMMENT '市ID',
  `area` int(0) NOT NULL DEFAULT 0 COMMENT '	区ID',
  `street` int(0) NOT NULL DEFAULT 0 COMMENT '街道ID',
  `is_del` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否删除',
  `delete_time` timestamp(0) NULL DEFAULT NULL COMMENT '删除时间',
  `extend_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '用户补充信息',
  `level_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '用户等级是否激活',
  `level_extend_info` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL COMMENT '激活会员卡补充信息',
  `is_first_order` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否购买首单优惠：0：未购买1已购买',
  `is_newcomer` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否购买新人专享：0：未购买1已购买',
  `replace_order_num` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '事业部/代理商名称',
  `division_name` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '事业部/代理商名称',
  `division_type` int(0) NOT NULL DEFAULT 0 COMMENT '代理类型：0普通，1事业部，2代理，3员工',
  `division_status` int(0) NOT NULL DEFAULT 0 COMMENT '代理状态',
  `division_id` int(0) NOT NULL DEFAULT 0 COMMENT '事业部id',
  `agent_id` int(0) NOT NULL DEFAULT 0 COMMENT '代理商id',
  `staff_id` int(0) NOT NULL DEFAULT 0 COMMENT '员工id',
  `division_percent` int(0) NOT NULL DEFAULT 0 COMMENT '分佣比例',
  `division_end_time` int(0) NOT NULL DEFAULT 0 COMMENT '事业部/代理/员工修改时间',
  `division_change_time` int(0) NOT NULL DEFAULT 0 COMMENT '事业部/代理/员工结束时间',
  `division_invite` int(0) NOT NULL DEFAULT 0 COMMENT '代理商邀请码',
  `identity` tinyint(1) NOT NULL DEFAULT 0 COMMENT '显示身份;0:普通用户,1 渠道商,2 管理员',
  `is_channel` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否是采购商',
  PRIMARY KEY (`uid`) USING BTREE,
  INDEX `account`(`account`) USING BTREE,
  INDEX `spreaduid`(`spread_uid`) USING BTREE,
  INDEX `level`(`level`) USING BTREE,
  INDEX `status`(`status`) USING BTREE,
  INDEX `work_uid`(`work_uid`) USING BTREE,
  INDEX `is_promoter`(`is_promoter`, `phone`) USING BTREE,
  INDEX `phone`(`phone`) USING BTREE,
  INDEX `index_0`(`delete_time`) USING BTREE,
  INDEX `add_time_delete_sex`(`add_time`, `delete_time`, `sex`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for eb_user_coin_apply
-- ----------------------------
DROP TABLE IF EXISTS `eb_user_coin_apply`;
CREATE TABLE `eb_user_coin_apply`  (
  `id` int unsigned NOT NULL COMMENT 'ID',
  `uid` int unsigned NOT NULL COMMENT '用户ID',
  `phone` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '手机号',
  `mark` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '备注',
  `status` tinyint(0) NOT NULL DEFAULT 0 COMMENT '处理状态0未处理，1通过 ，2未通过',
  `fail_msg` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '' COMMENT '未通过原因',
  `is_del` tinyint(0) NOT NULL DEFAULT 0 COMMENT '删除状态 1删除 ，0未删除',
  `status_time` int(0) NOT NULL DEFAULT 0 COMMENT '审核时间',
  `add_time` int(0) NOT NULL DEFAULT 0 COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb3 COLLATE = utf8mb3_general_ci COMMENT = '用户通证申请表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for eb_user_coin_bill
-- ----------------------------
DROP TABLE IF EXISTS `eb_user_coin_bill`;
CREATE TABLE `eb_user_coin_bill`  (
  `id` int unsigned NOT NULL COMMENT '用户账单id',
  `uid` int unsigned NOT NULL COMMENT '用户uid',
  `link_id` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '0' COMMENT '关联id',
  `pm` tinyint unsigned NOT NULL COMMENT '0 = 支出 1 = 获得',
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '账单标题',
  `order_to` int(0) NOT NULL DEFAULT 0 COMMENT '交易对手,默认0表示系统,与其他用户发生的交易则为用户id',
  `type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '明细类型:1积分2通证',
  `number` decimal(12, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '明细数字',
  `balance` decimal(12, 2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '剩余',
  `mark` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '' COMMENT '备注',
  `add_time` int unsigned NOT NULL COMMENT '添加时间',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `openid`(`uid`) USING BTREE,
  INDEX `add_time`(`add_time`) USING BTREE,
  INDEX `pm`(`pm`) USING BTREE,
  INDEX `type`(`order_to`, `type`, `link_id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci COMMENT = '用户积分通证明细表' ROW_FORMAT = Dynamic;

SET FOREIGN_KEY_CHECKS = 1;
