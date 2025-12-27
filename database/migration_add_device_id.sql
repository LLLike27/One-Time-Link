-- =====================================================
-- 增量迁移脚本：添加 device_id 字段
-- 用于设备级别的数据隔离
-- =====================================================

-- 添加 device_id 字段到 onetime_links 表
ALTER TABLE `onetime_links`
ADD COLUMN `device_id` varchar(64) DEFAULT NULL COMMENT '创建设备ID（用于设备级数据隔离）' AFTER `user_id`,
ADD INDEX `idx_device_id` (`device_id`);
