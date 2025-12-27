-- =====================================================
-- 一次性链接系统 - 维护脚本
-- =====================================================

-- ---------------------------------------------------
-- 1. 定时清理过期数据（建议每日执行）
-- ---------------------------------------------------

-- 清理30天前已使用或已过期的链接
DELETE FROM onetime_links
WHERE status IN (2, 3)
  AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- 清理90天前的访问日志
DELETE FROM onetime_link_logs
WHERE visit_time < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- 清理已发送成功的通知记录（保留失败记录用于排查）
DELETE FROM onetime_link_notifications
WHERE send_status = 2
  AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);


-- ---------------------------------------------------
-- 2. 更新过期状态（建议每小时执行）
-- ---------------------------------------------------

-- 将已过期但状态未更新的链接标记为过期
UPDATE onetime_links
SET status = 3
WHERE status = 1
  AND expire_time > 0
  AND expire_time < UNIX_TIMESTAMP();


-- ---------------------------------------------------
-- 3. 统计查询示例
-- ---------------------------------------------------

-- 按类型统计链接使用情况
SELECT
  content_type,
  COUNT(*) as total,
  SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active,
  SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as used,
  SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as expired,
  AVG(current_visits) as avg_visits
FROM onetime_links
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY content_type;

-- 访问成功率统计
SELECT
  DATE(visit_time) as date,
  COUNT(*) as total_visits,
  SUM(CASE WHEN visit_result = 1 THEN 1 ELSE 0 END) as success,
  ROUND(SUM(CASE WHEN visit_result = 1 THEN 1 ELSE 0 END) / COUNT(*) * 100, 2) as success_rate
FROM onetime_link_logs
WHERE visit_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(visit_time)
ORDER BY date DESC;

-- 异常访问IP统计
SELECT
  ip_address,
  COUNT(*) as failed_attempts,
  MAX(visit_time) as last_attempt
FROM onetime_link_logs
WHERE visit_result != 1
  AND visit_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY ip_address
HAVING failed_attempts >= 10
ORDER BY failed_attempts DESC;
