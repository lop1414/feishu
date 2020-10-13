/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50724
Source Host           : localhost:3306
Source Database       : feishu

Target Server Type    : MYSQL
Target Server Version : 50724
File Encoding         : 65001

Date: 2020-10-13 11:15:23
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for callback_events
-- ----------------------------
DROP TABLE IF EXISTS `callback_events`;
CREATE TABLE `callback_events` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `callback_type` varchar(50) NOT NULL DEFAULT '' COMMENT '回调类型',
  `event_type` varchar(50) NOT NULL DEFAULT '' COMMENT '事件类型',
  `app_id` varchar(255) NOT NULL DEFAULT '' COMMENT '应用id',
  `tenant_key` varchar(255) NOT NULL DEFAULT '' COMMENT '企业标识',
  `extends` text COMMENT '扩展字段',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='回调事件表';

-- ----------------------------
-- Table structure for employees
-- ----------------------------
DROP TABLE IF EXISTS `employees`;
CREATE TABLE `employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(100) NOT NULL DEFAULT '' COMMENT '员工id',
  `avatar_url` varchar(512) NOT NULL DEFAULT '' COMMENT '头像地址',
  `email` varchar(100) NOT NULL DEFAULT '' COMMENT '邮箱',
  `employee_no` varchar(100) NOT NULL DEFAULT '' COMMENT '员工编号',
  `employee_type` varchar(50) NOT NULL DEFAULT '' COMMENT '员工类型',
  `en_name` varchar(50) NOT NULL DEFAULT '' COMMENT '英文名称',
  `is_tenant_manager` tinyint(4) NOT NULL DEFAULT '0' COMMENT '是否管理员',
  `mobile` varchar(20) NOT NULL DEFAULT '' COMMENT '手机号码',
  `name` varchar(50) NOT NULL DEFAULT '' COMMENT '名称',
  `name_py` varchar(50) NOT NULL DEFAULT '' COMMENT '名称拼音',
  `open_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'open_id',
  `status` varchar(50) NOT NULL DEFAULT '' COMMENT '状态',
  `union_id` varchar(128) NOT NULL DEFAULT '' COMMENT 'union_id',
  `update_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间戳',
  `extends` text COMMENT '扩展字段',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COMMENT='员工表';

-- ----------------------------
-- Table structure for error_logs
-- ----------------------------
DROP TABLE IF EXISTS `error_logs`;
CREATE TABLE `error_logs` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `exception` varchar(50) NOT NULL DEFAULT '' COMMENT '异常名称',
  `code` varchar(50) NOT NULL DEFAULT '' COMMENT '错误码',
  `message` varchar(512) NOT NULL DEFAULT '' COMMENT '错误提示',
  `data` text NOT NULL COMMENT '错误数据',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`) USING BTREE,
  KEY `code` (`code`) USING BTREE,
  KEY `exception` (`exception`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='错误日志表';

-- ----------------------------
-- Table structure for messages
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `message_id` varchar(255) NOT NULL DEFAULT '' COMMENT '飞书消息id',
  `type` varchar(50) NOT NULL DEFAULT '' COMMENT '类型',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '标题',
  `content` text COMMENT '内容',
  `target_type` varchar(50) NOT NULL DEFAULT '' COMMENT '目标类型',
  `target_id` varchar(255) NOT NULL DEFAULT '' COMMENT '目标id',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8 COMMENT='消息表';

-- ----------------------------
-- Table structure for tenant_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `tenant_access_tokens`;
CREATE TABLE `tenant_access_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `app_id` varchar(255) NOT NULL DEFAULT '' COMMENT 'appid',
  `tenant_access_token` varchar(255) NOT NULL DEFAULT '' COMMENT 'tenant_access_token',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  `expired_at` timestamp NULL DEFAULT NULL COMMENT '过期时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 COMMENT='tenant_access_token表';
