# 数据库设计文档

## 📊 数据库表结构设计

### 技术栈
- **数据库**: MySQL 5.7+
- **存储引擎**: InnoDB
- **字符集**: utf8mb4
- **排序规则**: utf8mb4_unicode_ci

---

## 📋 表结构设计

### 1. 一次性链接主表 `onetime_links`

用于存储一次性链接的核心信息。

```sql
CREATE TABLE `onetime_links` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `token` varchar(64) NOT NULL COMMENT '唯一Token标识',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT '创建用户ID（可选）',
  `content_type` varchar(32) NOT NULL COMMENT '内容类型：email_verify/password_reset/magic_login/secret_share/file_download/custom',
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
  KEY `idx_content_type` (`content_type`),
  KEY `idx_status_expire` (`status`, `expire_time`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一次性链接主表';
```

**字段说明**:
- `token`: 使用UUID v4或加密安全的随机字符串，长度建议32-64字符
- `content_type`: 预定义业务类型，便于分类管理
- `content_data`: 存储业务相关数据，JSON格式，敏感信息使用AES加密
- `max_visits`: 支持多次访问的场景（如3次、5次）
- `bind_ip`: 可选的IP绑定，增强安全性
- `status`: 状态机设计，便于链接生命周期管理
- `metadata`: 扩展字段，存储自定义业务数据

---

### 2. 访问日志表 `onetime_link_logs`

记录每次访问的详细信息，用于审计和安全分析。

```sql
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
  `visit_result` tinyint(4) NOT NULL COMMENT '访问结果：1=成功，2=Token无效，3=已过期，4=次数超限，5=IP不匹配',
  `error_message` varchar(255) DEFAULT NULL COMMENT '错误信息',
  `referer` varchar(512) DEFAULT NULL COMMENT '来源页面',
  `extra_data` json DEFAULT NULL COMMENT '额外数据（JSON格式）',
  PRIMARY KEY (`id`),
  KEY `idx_link_id` (`link_id`),
  KEY `idx_token` (`token`),
  KEY `idx_visit_time` (`visit_time`),
  KEY `idx_ip_address` (`ip_address`),
  CONSTRAINT `fk_link_logs_link_id` FOREIGN KEY (`link_id`) REFERENCES `onetime_links` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='一次性链接访问日志表';
```

**字段说明**:
- `link_id`: 外键关联到主表，级联删除
- `token`: 冗余存储，即使主表Token被删除也能保留审计记录
- `ip_address`: 支持IPv4和IPv6（最大45字符）
- `device_fingerprint`: 可选的设备识别，用于高级安全验证
- `geo_*`: 地理位置信息，可通过IP解析获得
- `visit_result`: 枚举类型，记录访问结果状态
- `extra_data`: 扩展字段，存储业务自定义日志数据

---

### 3. 用户通知记录表 `onetime_link_notifications`

当链接被访问时，记录发送的通知信息。

```sql
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
```

**字段说明**:
- `notify_type`: 支持多种通知方式
- `notify_to`: 根据通知类型存储不同的目标地址
- `send_status`: 状态机设计，便于重试和监控
- `log_id`: 关联访问日志，记录是哪次访问触发的通知

---

## 🔍 索引设计说明

### 主表 `onetime_links` 索引
1. `uk_token` - 唯一索引，保证Token唯一性，访问验证时高效查询
2. `idx_user_id` - 用户维度查询（如用户管理自己的链接）
3. `idx_content_type` - 业务类型分类查询
4. `idx_status_expire` - 复合索引，用于定时清理过期链接
5. `idx_created_at` - 时间范围查询（如统计报表）

### 日志表 `onetime_link_logs` 索引
1. `idx_link_id` - 关联查询主表
2. `idx_token` - Token维度的日志查询
3. `idx_visit_time` - 时间范围查询
4. `idx_ip_address` - IP维度的安全分析

### 通知表 `onetime_link_notifications` 索引
1. `idx_link_id` - 关联查询主表
2. `idx_log_id` - 关联查询日志
3. `idx_user_id` - 用户维度查询
4. `idx_send_status` - 失败重试队列查询

---

## 📈 数据字典

### content_type 类型枚举

| 值 | 说明 | 典型场景 |
|---|------|---------|
| `email_verify` | 邮箱验证 | 用户注册、邮箱更换 |
| `password_reset` | 密码重置 | 忘记密码 |
| `magic_login` | 魔法登录 | 免密登录 |
| `secret_share` | 密文分享 | 敏感信息一次性查看 |
| `file_download` | 文件下载 | 临时文件下载授权 |
| `qrcode_auth` | 二维码验证 | 扫码登录、设备授权 |
| `invite_code` | 邀请码 | 注册邀请、内测资格 |
| `custom` | 自定义 | 其他业务场景 |

### status 状态枚举

| 值 | 说明 | 说明 |
|---|------|------|
| `1` | 有效 | 链接可正常使用 |
| `2` | 已使用 | 达到访问次数上限 |
| `3` | 已过期 | 超过过期时间 |
| `4` | 已撤销 | 手动撤销或系统强制失效 |

### visit_result 访问结果枚举

| 值 | 说明 |
|---|------|
| `1` | 成功 |
| `2` | Token无效（不存在） |
| `3` | 已过期 |
| `4` | 访问次数超限 |
| `5` | IP地址不匹配 |
| `6` | 设备指纹不匹配 |
| `7` | 其他错误 |

---

## 🛡️ 安全设计

### 1. 数据加密
```sql
-- content_data 中的敏感数据加密示例（应用层处理）
-- 使用 AES-256-GCM 加密
{
  "encrypted": true,
  "algorithm": "AES-256-GCM",
  "data": "encrypted_base64_string",
  "iv": "initialization_vector",
  "tag": "authentication_tag"
}
```

### 2. Token 生成规范
- 使用 UUID v4 或 `openssl_random_pseudo_bytes(32)`
- 编码为 Base64 URL-safe 格式
- 长度: 32-64 字符
- 示例: `a3d5f8b2-4c9e-4b7a-9f1e-2d3c4e5f6a7b`

### 3. 索引选择性
- `token` 字段的选择性极高（接近100%），使用唯一索引
- `status` 字段选择性较低，但与 `expire_time` 组合后提高效率
- 定期运行 `ANALYZE TABLE` 更新统计信息

### 4. 分区策略（可选，大数据量场景）
```sql
-- 按创建时间分区（月度分区）
ALTER TABLE onetime_link_logs
PARTITION BY RANGE (UNIX_TIMESTAMP(visit_time)) (
  PARTITION p202501 VALUES LESS THAN (UNIX_TIMESTAMP('2025-02-01')),
  PARTITION p202502 VALUES LESS THAN (UNIX_TIMESTAMP('2025-03-01')),
  -- ...
  PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## 🔧 维护脚本

### 1. 定时清理过期数据（建议每日执行）
```sql
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
```

### 2. 更新过期状态（建议每小时执行）
```sql
-- 将已过期但状态未更新的链接标记为过期
UPDATE onetime_links
SET status = 3
WHERE status = 1
  AND expire_time > 0
  AND expire_time < UNIX_TIMESTAMP();
```

### 3. 统计查询示例
```sql
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
```

---

## 📊 性能优化建议

### 1. 缓存策略
- 使用 Redis 缓存活跃的 Token 数据（TTL与expire_time一致）
- 缓存Key设计: `onetime:token:{token}`
- 访问流程: Redis查询 → 命中则直接验证 → 未命中查询MySQL并写入缓存

### 2. 读写分离
- 查询（验证Token）走主库，确保强一致性
- 日志写入可异步批量插入
- 统计查询走从库

### 3. 分表策略
- 日志表按月分表: `onetime_link_logs_202501`
- 保留最近3个月数据在主表，历史数据归档

### 4. 连接池优化
- 设置合理的连接池大小（根据业务并发量）
- 使用持久连接减少握手开销

---

## 🔄 数据迁移

### 初始化脚本
```bash
# 执行建表SQL
mysql -u root -p your_database < schema.sql

# 创建索引（如果分离）
mysql -u root -p your_database < indexes.sql

# 初始化测试数据（可选）
mysql -u root -p your_database < test_data.sql
```

### 版本管理
建议使用数据库迁移工具（如 Phinx、ThinkPHP迁移组件）管理表结构变更。

---

## 📝 备注

1. **字符集统一使用 `utf8mb4`**，支持完整的 Unicode 字符（包括 Emoji）
2. **时间字段使用 `timestamp`**，自动处理时区转换
3. **外键约束谨慎使用**，日志表使用外键便于级联删除，但高并发场景可考虑应用层控制
4. **定期备份**，建议每日全量备份 + 增量日志备份
5. **监控告警**，监控表大小、慢查询、锁等待等指标

---

## 🔗 相关文档

- [README.md](./README.md) - 核心功能设计文档
- [后端开发计划](./backend-plan.md) - ThinkPHP6 实现计划
- [前端开发计划](./frontend-plan.md) - Vue3 实现计划
