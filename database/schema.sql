-- =====================================================
-- 一次性链接系统数据库表结构
-- 数据库: MySQL 5.7+
-- 字符集: utf8mb4
-- 排序规则: utf8mb4_unicode_ci
-- =====================================================

-- 设置字符集
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------
-- 1. 一次性链接主表 `onetime_links`
-- ---------------------------------------------------
DROP TABLE IF EXISTS `onetime_links`;
CREATE TABLE `onetime_links` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `token` varchar(64) NOT NULL COMMENT '唯一Token标识',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '创建用户ID（可选）',
  `device_id` varchar(64) DEFAULT NULL COMMENT '创建设备ID（用于设备级数据隔离）',
  `content_type` varchar(32) NOT NULL COMMENT '内容类型：email_verify/password_reset/magic_login/secret_share/file_download/qrcode_auth/invite_code/custom',
  `content_data` text COMMENT '业务数据（JSON格式，敏感信息需加密）',
  `max_visits` int(11) NOT NULL DEFAULT 1 COMMENT '最大访问次数',
  `current_visits` int(11) NOT NULL DEFAULT 0 COMMENT '当前已访问次数',
  `expire_time` int(11) NOT NULL DEFAULT 0 COMMENT '过期时间（Unix时间戳，0表示永不过期）',
  `bind_ip` varchar(45) DEFAULT NULL COMMENT '绑定的IP地址（可选）',
  `status` tinyint(4) NOT NULL DEFAULT 1 COMMENT '状态：1=有效，2=已使用，3=已过期，4=已撤销',
  `notify_on_visit` tinyint(1) NOT NULL DEFAULT 0 COMMENT '访问时是否通知：0=否，1=是',
  `metadata` json DEFAULT NULL COMMENT '扩展元数据（JSON格式）',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_content_type` (`content_type`),
  KEY `idx_status_expire` (`status`, `expire_time`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一次性链接主表';

-- ---------------------------------------------------
-- 2. 访问日志表 `onetime_link_logs`
-- ---------------------------------------------------
DROP TABLE IF EXISTS `onetime_link_logs`;
CREATE TABLE `onetime_link_logs` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `link_id` bigint(20) UNSIGNED NOT NULL COMMENT '关联的链接ID',
  `token` varchar(64) NOT NULL COMMENT 'Token（冗余字段，便于查询）',
  `visit_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '访问时间',
  `ip_address` varchar(45) NOT NULL COMMENT '访问IP地址',
  `user_agent` varchar(512) DEFAULT NULL COMMENT '用户代理字符串',
  `device_fingerprint` varchar(128) DEFAULT NULL COMMENT '设备指纹（可选）',
  `geo_country` varchar(64) DEFAULT NULL COMMENT '国家',
  `geo_region` varchar(64) DEFAULT NULL COMMENT '省份/州',
  `geo_city` varchar(64) DEFAULT NULL COMMENT '城市',
  `visit_result` tinyint(4) NOT NULL COMMENT '访问结果：1=成功，2=Token无效，3=已过期，4=次数超限，5=IP不匹配，6=设备不匹配，7=其他错误',
  `error_message` varchar(255) DEFAULT NULL COMMENT '错误信息',
  `referer` varchar(512) DEFAULT NULL COMMENT '来源页面',
  `extra_data` json DEFAULT NULL COMMENT '额外数据（JSON格式）',
  PRIMARY KEY (`id`),
  KEY `idx_link_id` (`link_id`),
  KEY `idx_token` (`token`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_visit_result` (`visit_result`),
  CONSTRAINT `fk_link_logs_link_id` FOREIGN KEY (`link_id`) REFERENCES `onetime_links` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一次性链接访问日志表';

-- ---------------------------------------------------
-- 3. 用户通知记录表 `onetime_link_notifications`
-- ---------------------------------------------------
DROP TABLE IF EXISTS `onetime_link_notifications`;
CREATE TABLE `onetime_link_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `link_id` bigint(20) UNSIGNED NOT NULL COMMENT '关联的链接ID',
  `log_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '关联的访问日志ID',
  `user_id` bigint(20) UNSIGNED NOT NULL COMMENT '接收通知的用户ID',
  `notify_type` varchar(32) NOT NULL COMMENT '通知类型：email/sms/push/webhook',
  `notify_to` varchar(255) NOT NULL COMMENT '通知目标（邮箱/手机号/设备ID/Webhook URL）',
  `notify_content` text COMMENT '通知内容',
  `send_status` tinyint(4) NOT NULL DEFAULT 0 COMMENT '发送状态：0=待发送，1=发送中，2=成功，3=失败',
  `send_time` timestamp NULL DEFAULT NULL COMMENT '发送时间',
  `error_message` varchar(255) DEFAULT NULL COMMENT '错误信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_link_id` (`link_id`),
  KEY `idx_log_id` (`log_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_send_status` (`send_status`),
  CONSTRAINT `fk_notify_link_id` FOREIGN KEY (`link_id`) REFERENCES `onetime_links` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notify_log_id` FOREIGN KEY (`log_id`) REFERENCES `onetime_link_logs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一次性链接通知记录表';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- 数据字典
-- =====================================================
--
-- content_type 类型枚举:
-- | 值               | 说明         | 典型场景                       |
-- |------------------|--------------|-------------------------------|
-- | email_verify     | 邮箱验证     | 用户注册、邮箱更换             |
-- | password_reset   | 密码重置     | 忘记密码                       |
-- | magic_login      | 魔法登录     | 免密登录                       |
-- | secret_share     | 密文分享     | 敏感信息一次性查看             |
-- | file_download    | 文件下载     | 临时文件下载授权               |
-- | qrcode_auth      | 二维码验证   | 扫码登录、设备授权             |
-- | invite_code      | 邀请码       | 注册邀请、内测资格             |
-- | custom           | 自定义       | 其他业务场景                   |
--
-- status 状态枚举:
-- | 值 | 说明     |
-- |----|----------|
-- | 1  | 有效     |
-- | 2  | 已使用   |
-- | 3  | 已过期   |
-- | 4  | 已撤销   |
--
-- visit_result 访问结果枚举:
-- | 值 | 说明             |
-- |----|------------------|
-- | 1  | 成功             |
-- | 2  | Token无效        |
-- | 3  | 已过期           |
-- | 4  | 访问次数超限     |
-- | 5  | IP地址不匹配     |
-- | 6  | 设备指纹不匹配   |
-- | 7  | 其他错误         |
-- =====================================================
