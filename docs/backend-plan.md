# ThinkPHP 6 后端开发计划

## 📋 项目概述

基于 ThinkPHP 6 框架实现一次性链接功能的后端服务，提供安全的 Token 生成、验证、访问控制等核心能力。

### 技术栈
- **框架**: ThinkPHP 6.x
- **PHP版本**: 7.2+（推荐 8.0+）
- **数据库**: MySQL 5.7+
- **缓存**: Redis 5.0+
- **队列**: ThinkPHP Queue（可选）

---

## 🎯 开发任务清单

### 阶段一：环境搭建与基础配置 ⏱️ 预计2小时

#### 1.1 项目初始化
- [ ] 安装 ThinkPHP 6 框架（如已有项目则跳过）
  ```bash
  composer create-project topthink/think onetime-link
  ```
- [ ] 配置数据库连接 `config/database.php`
- [ ] 配置 Redis 缓存 `config/cache.php`
- [ ] 配置路由中间件 `config/middleware.php`

#### 1.2 数据库迁移
- [ ] 创建迁移文件
  ```bash
  php think make:migration CreateOnetimeLinksTable
  php think make:migration CreateOnetimeLinkLogsTable
  php think make:migration CreateOnetimeLinkNotificationsTable
  ```
- [ ] 编写迁移脚本（参考 `database-design.md`）
- [ ] 执行迁移
  ```bash
  php think migrate:run
  ```

#### 1.3 安装依赖包
- [ ] 安装必要的扩展包
  ```bash
  # UUID生成
  composer require ramsey/uuid

  # 加密扩展（如果需要高级加密）
  composer require defuse/php-encryption

  # IP地址解析（可选）
  composer require geoip2/geoip2

  # 队列组件（异步任务）
  composer require topthink/think-queue
  ```

---

### 阶段二：数据模型与基础服务 ⏱️ 预计4小时

#### 2.1 创建模型类

##### 2.1.1 OnetimeLink 模型
```bash
php think make:model OnetimeLink
```

**文件**: `app/model/OnetimeLink.php`

- [ ] 定义字段映射和类型转换
- [ ] 定义状态常量（STATUS_ACTIVE, STATUS_USED等）
- [ ] 定义内容类型常量（TYPE_EMAIL_VERIFY, TYPE_PASSWORD_RESET等）
- [ ] 实现获取器和修改器
  - [ ] `content_data` 自动加密/解密
  - [ ] `expire_time` 时间格式化
- [ ] 定义模型事件
  - [ ] `beforeInsert`: 生成Token、设置默认值
  - [ ] `afterUpdate`: 清除缓存
- [ ] 实现关联关系
  - [ ] `hasMany` 关联 `OnetimeLinkLog`
  - [ ] `hasMany` 关联 `OnetimeLinkNotification`

##### 2.1.2 OnetimeLinkLog 模型
```bash
php think make:model OnetimeLinkLog
```

**文件**: `app/model/OnetimeLinkLog.php`

- [ ] 定义字段映射
- [ ] 定义访问结果常量（RESULT_SUCCESS, RESULT_INVALID等）
- [ ] 实现关联关系
  - [ ] `belongsTo` 关联 `OnetimeLink`

##### 2.1.3 OnetimeLinkNotification 模型
```bash
php think make:model OnetimeLinkNotification
```

**文件**: `app/model/OnetimeLinkNotification.php`

- [ ] 定义字段映射
- [ ] 定义通知类型和发送状态常量
- [ ] 实现关联关系
  - [ ] `belongsTo` 关联 `OnetimeLink`
  - [ ] `belongsTo` 关联 `OnetimeLinkLog`

#### 2.2 创建服务类

##### 2.2.1 TokenService（Token管理服务）

**文件**: `app/service/TokenService.php`

- [ ] `generateToken()`: 生成安全的随机Token
  - [ ] 使用 UUID v4 或 `random_bytes(32)`
  - [ ] Base64 URL-safe 编码
  - [ ] 保证唯一性（查重）

- [ ] `createLink($params)`: 创建一次性链接
  - [ ] 验证参数
  - [ ] 生成Token
  - [ ] 加密敏感数据
  - [ ] 写入数据库
  - [ ] 写入Redis缓存
  - [ ] 返回完整链接URL

- [ ] `verifyToken($token, $ipAddress)`: 验证Token
  - [ ] 从Redis缓存获取（优先）
  - [ ] 未命中则查询数据库并缓存
  - [ ] 检查有效性（状态、过期时间、访问次数）
  - [ ] IP绑定验证（如果启用）
  - [ ] 返回验证结果

- [ ] `consumeToken($token, $ipAddress, $userAgent)`: 消费Token
  - [ ] 验证Token
  - [ ] 事务处理：
    - [ ] 增加访问次数 `current_visits + 1`
    - [ ] 达到上限则更新状态为已使用
    - [ ] 写入访问日志
  - [ ] 触发通知（如果启用）
  - [ ] 更新Redis缓存
  - [ ] 返回业务数据

- [ ] `revokeToken($token)`: 撤销Token
  - [ ] 更新状态为已撤销
  - [ ] 删除Redis缓存

- [ ] `extendExpire($token, $extraSeconds)`: 延长过期时间
  - [ ] 更新过期时间
  - [ ] 更新Redis缓存TTL

##### 2.2.2 EncryptService（加密服务）

**文件**: `app/service/EncryptService.php`

- [ ] `encrypt($data)`: 加密数据
  - [ ] 使用 AES-256-GCM 加密
  - [ ] 返回加密后的Base64字符串
  - [ ] 包含 IV 和 TAG

- [ ] `decrypt($encryptedData)`: 解密数据
  - [ ] 解析 IV 和 TAG
  - [ ] 解密并返回原始数据

- [ ] `encryptJson($array)`: 加密JSON数据
- [ ] `decryptJson($encryptedJson)`: 解密JSON数据

##### 2.2.3 LogService（日志服务）

**文件**: `app/service/LogService.php`

- [ ] `logAccess($linkId, $token, $result, $request)`: 记录访问日志
  - [ ] 提取IP、User-Agent、Referer
  - [ ] 解析地理位置（可选）
  - [ ] 写入数据库（可异步）

- [ ] `getLogsByToken($token)`: 获取Token的访问历史
- [ ] `getLogsByLinkId($linkId)`: 获取链接的访问历史
- [ ] `getAnomalousLogs($criteria)`: 获取异常访问日志
  - [ ] IP频繁失败
  - [ ] 短时间大量请求

##### 2.2.4 NotifyService（通知服务）

**文件**: `app/service/NotifyService.php`

- [ ] `sendNotification($linkId, $logId, $notifyConfig)`: 发送通知
  - [ ] 支持邮件通知
  - [ ] 支持短信通知（可选）
  - [ ] 支持Webhook通知（可选）
  - [ ] 异步队列处理

- [ ] `sendEmail($to, $subject, $content)`: 发送邮件
- [ ] `sendSms($phone, $content)`: 发送短信
- [ ] `callWebhook($url, $data)`: 调用Webhook

---

### 阶段三：控制器与路由 ⏱️ 预计3小时

#### 3.1 创建控制器

##### 3.1.1 LinkController（链接管理）

**文件**: `app/controller/LinkController.php`

- [ ] `create(Request $request)`: 创建一次性链接
  - [ ] 参数验证
  - [ ] 调用 `TokenService::createLink()`
  - [ ] 返回链接信息（JSON）

- [ ] `verify(Request $request)`: 验证链接（不消费）
  - [ ] 提取Token参数
  - [ ] 调用 `TokenService::verifyToken()`
  - [ ] 返回验证结果

- [ ] `access(Request $request, $token)`: 访问链接（消费Token）
  - [ ] 调用 `TokenService::consumeToken()`
  - [ ] 根据业务类型跳转或返回数据
  - [ ] 记录访问日志
  - [ ] 触发通知

- [ ] `revoke(Request $request)`: 撤销链接
  - [ ] 权限验证（只能撤销自己的链接）
  - [ ] 调用 `TokenService::revokeToken()`

- [ ] `list(Request $request)`: 获取用户的链接列表
  - [ ] 分页查询
  - [ ] 支持筛选（状态、类型）

- [ ] `detail(Request $request, $id)`: 获取链接详情
  - [ ] 包含访问日志

##### 3.1.2 LogController（日志查询）

**文件**: `app/controller/LogController.php`

- [ ] `list(Request $request)`: 访问日志列表
  - [ ] 分页查询
  - [ ] 支持按Token、时间范围筛选

- [ ] `statistics(Request $request)`: 统计数据
  - [ ] 按类型统计
  - [ ] 访问成功率
  - [ ] 时间分布

##### 3.1.3 WebhookController（Webhook回调）

**文件**: `app/controller/WebhookController.php`

- [ ] `handle(Request $request)`: 处理第三方回调
  - [ ] 验证签名
  - [ ] 处理业务逻辑

#### 3.2 配置路由

**文件**: `route/app.php`

```php
use think\facade\Route;

// 一次性链接相关路由
Route::group('onetime', function () {
    // 创建链接（需要认证）
    Route::post('create', 'LinkController@create');

    // 验证链接（不消费）
    Route::get('verify/:token', 'LinkController@verify');

    // 访问链接（消费Token）
    Route::get('access/:token', 'LinkController@access');

    // 撤销链接（需要认证）
    Route::post('revoke', 'LinkController@revoke');

    // 链接列表（需要认证）
    Route::get('list', 'LinkController@list');

    // 链接详情（需要认证）
    Route::get('detail/:id', 'LinkController@detail');

    // 访问日志（需要认证）
    Route::get('logs', 'LogController@list');

    // 统计数据（需要认证）
    Route::get('statistics', 'LogController@statistics');
})->middleware(['auth']); // 应用认证中间件

// 公开访问路由（无需认证）
Route::get('verify/:token', 'LinkController@access');
```

---

### 阶段四：中间件与验证器 ⏱️ 预计2小时

#### 4.1 中间件

##### 4.1.1 RateLimitMiddleware（访问限流）

**文件**: `app/middleware/RateLimitMiddleware.php`

- [ ] 基于IP的限流（防止暴力破解）
- [ ] 使用Redis计数器
- [ ] 可配置限流规则（如每分钟10次）

##### 4.1.2 LogMiddleware（请求日志）

**文件**: `app/middleware/LogMiddleware.php`

- [ ] 记录所有API请求
- [ ] 记录响应时间、状态码

#### 4.2 验证器

##### 4.2.1 LinkValidate（链接创建验证）

**文件**: `app/validate/LinkValidate.php`

```php
protected $rule = [
    'content_type'  => 'require|in:email_verify,password_reset,magic_login,secret_share,file_download,custom',
    'content_data'  => 'require',
    'max_visits'    => 'number|gt:0',
    'expire_time'   => 'number|egt:0',
    'bind_ip'       => 'ip',
    'notify_on_visit' => 'boolean',
];
```

- [ ] 定义验证规则
- [ ] 定义错误消息
- [ ] 定义验证场景

---

### 阶段五：任务调度与队列 ⏱️ 预计2小时

#### 5.1 定时任务

##### 5.1.1 清理过期数据任务

**文件**: `app/command/CleanExpiredLinks.php`

```bash
php think make:command CleanExpiredLinks
```

- [ ] 清理30天前已使用/已过期的链接
- [ ] 清理90天前的访问日志
- [ ] 清理已发送成功的通知记录

**配置定时任务**: `config/console.php`
```php
'commands' => [
    'clean:expired' => 'app\command\CleanExpiredLinks',
],
```

**Crontab配置**:
```bash
# 每天凌晨3点执行
0 3 * * * cd /path/to/project && php think clean:expired
```

##### 5.1.2 更新过期状态任务

**文件**: `app/command/UpdateExpiredStatus.php`

- [ ] 将已过期但状态未更新的链接标记为过期

**Crontab配置**:
```bash
# 每小时执行
0 * * * * cd /path/to/project && php think update:expired
```

#### 5.2 异步队列

##### 5.2.1 发送通知队列

**文件**: `app/job/SendNotification.php`

- [ ] 实现队列任务类
- [ ] 处理邮件发送
- [ ] 处理短信发送
- [ ] 失败重试机制

**启动队列消费者**:
```bash
php think queue:listen
```

##### 5.2.2 记录访问日志队列（可选）

**文件**: `app/job/LogAccess.php`

- [ ] 异步写入访问日志
- [ ] 减少主流程耗时

---

### 阶段六：缓存策略实现 ⏱️ 预计1.5小时

#### 6.1 Redis缓存设计

##### 6.1.1 Token数据缓存

```php
// 缓存Key设计
$cacheKey = 'onetime:token:' . $token;

// 写入缓存
cache($cacheKey, $linkData, $ttl);

// 读取缓存
$linkData = cache($cacheKey);

// 删除缓存
cache($cacheKey, null);
```

- [ ] 实现缓存读写逻辑
- [ ] TTL与expire_time保持一致
- [ ] 数据变更时同步更新缓存

##### 6.1.2 访问频率限制缓存

```php
// 限流Key设计
$rateLimitKey = 'onetime:ratelimit:' . $ipAddress;

// 计数器
$count = cache($rateLimitKey) ?: 0;
cache($rateLimitKey, $count + 1, 60); // 1分钟窗口
```

- [ ] 实现访问计数器
- [ ] 超过阈值返回429错误

#### 6.2 缓存预热与更新

- [ ] 热点Token提前加载到缓存
- [ ] 数据库更新时同步更新缓存
- [ ] 缓存失效时的降级处理

---

### 阶段七：安全加固 ⏱️ 预计2小时

#### 7.1 数据加密

- [ ] 实现 `content_data` 字段的AES加密
- [ ] 密钥管理（环境变量或配置文件）
- [ ] 加密算法: AES-256-GCM

#### 7.2 输入验证

- [ ] 所有用户输入进行严格验证
- [ ] 防止SQL注入（使用ORM参数绑定）
- [ ] 防止XSS攻击（输出转义）

#### 7.3 CSRF保护

- [ ] 启用ThinkPHP CSRF中间件
- [ ] 配置CSRF Token验证

#### 7.4 日志审计

- [ ] 记录敏感操作日志
- [ ] 记录异常访问尝试
- [ ] 日志定期归档

---

### 阶段八：单元测试 ⏱️ 预计3小时

#### 8.1 服务类测试

##### 8.1.1 TokenService测试

**文件**: `tests/service/TokenServiceTest.php`

- [ ] 测试Token生成的唯一性
- [ ] 测试链接创建功能
- [ ] 测试Token验证逻辑
- [ ] 测试Token消费流程
- [ ] 测试过期时间处理
- [ ] 测试访问次数限制
- [ ] 测试IP绑定验证

##### 8.1.2 EncryptService测试

**文件**: `tests/service/EncryptServiceTest.php`

- [ ] 测试加密/解密的正确性
- [ ] 测试加密后的数据安全性

#### 8.2 控制器测试

##### 8.2.1 LinkController测试

**文件**: `tests/controller/LinkControllerTest.php`

- [ ] 测试创建接口
- [ ] 测试访问接口
- [ ] 测试撤销接口
- [ ] 测试各种错误场景

#### 8.3 运行测试

```bash
# 运行所有测试
./vendor/bin/phpunit

# 运行单个测试文件
./vendor/bin/phpunit tests/service/TokenServiceTest.php

# 生成代码覆盖率报告
./vendor/bin/phpunit --coverage-html coverage
```

---

### 阶段九：API文档编写 ⏱️ 预计2小时

#### 9.1 接口文档

**文件**: `docs/api.md`

- [ ] 创建链接接口文档
  - 请求参数
  - 响应示例
  - 错误码说明

- [ ] 验证链接接口文档
- [ ] 访问链接接口文档
- [ ] 撤销链接接口文档
- [ ] 查询接口文档

#### 9.2 使用Apidoc或Swagger

- [ ] 安装接口文档生成工具
  ```bash
  composer require --dev zircote/swagger-php
  ```

- [ ] 在控制器方法上添加注解
- [ ] 生成接口文档
  ```bash
  php think swagger:generate
  ```

---

### 阶段十：部署与优化 ⏱️ 预计2小时

#### 10.1 生产环境配置

- [ ] 配置环境变量（`.env`）
  ```ini
  APP_DEBUG = false
  DATABASE_HOSTNAME = 127.0.0.1
  DATABASE_DATABASE = onetime_links
  DATABASE_USERNAME = root
  DATABASE_PASSWORD = your_password
  REDIS_HOST = 127.0.0.1
  REDIS_PORT = 6379
  ENCRYPTION_KEY = your_secret_key_32chars
  ```

- [ ] 开启OPcache加速
- [ ] 配置日志轮转

#### 10.2 性能优化

- [ ] 数据库索引优化
- [ ] 开启查询缓存
- [ ] 启用Redis持久化
- [ ] 配置Nginx反向代理
  ```nginx
  location /onetime/ {
      proxy_pass http://127.0.0.1:8080;
      proxy_set_header Host $host;
      proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
  }
  ```

#### 10.3 监控告警

- [ ] 配置日志监控（ELK Stack）
- [ ] 配置性能监控（New Relic / DataDog）
- [ ] 配置异常告警（钉钉/企业微信Webhook）

---

## 📂 项目目录结构

```
app/
├── controller/
│   ├── LinkController.php          # 链接管理控制器
│   ├── LogController.php           # 日志查询控制器
│   └── WebhookController.php       # Webhook控制器
├── model/
│   ├── OnetimeLink.php             # 链接模型
│   ├── OnetimeLinkLog.php          # 日志模型
│   └── OnetimeLinkNotification.php # 通知模型
├── service/
│   ├── TokenService.php            # Token管理服务
│   ├── EncryptService.php          # 加密服务
│   ├── LogService.php              # 日志服务
│   └── NotifyService.php           # 通知服务
├── middleware/
│   ├── RateLimitMiddleware.php     # 限流中间件
│   └── LogMiddleware.php           # 日志中间件
├── validate/
│   └── LinkValidate.php            # 链接验证器
├── command/
│   ├── CleanExpiredLinks.php       # 清理过期数据命令
│   └── UpdateExpiredStatus.php     # 更新过期状态命令
└── job/
    ├── SendNotification.php        # 发送通知队列
    └── LogAccess.php               # 记录访问日志队列

config/
├── database.php                    # 数据库配置
├── cache.php                       # 缓存配置
├── queue.php                       # 队列配置
└── console.php                     # 命令行配置

route/
└── app.php                         # 路由定义

database/
└── migrations/                     # 数据库迁移文件

tests/
├── service/                        # 服务类测试
└── controller/                     # 控制器测试

docs/
└── api.md                          # API文档
```

---

## 🔧 开发规范

### 1. 代码规范
- 遵循 PSR-12 编码规范
- 使用类型提示（PHP 7.4+）
- 使用严格模式 `declare(strict_types=1);`

### 2. 命名规范
- 控制器：`XxxController`
- 模型：驼峰命名，如 `OnetimeLink`
- 服务：`XxxService`
- 中间件：`XxxMiddleware`

### 3. 注释规范
- 所有公开方法添加PHPDoc注释
- 复杂逻辑添加行内注释
- 类文件添加文件头注释

### 4. 异常处理
- 使用自定义异常类
- 统一异常处理（ExceptionHandle）
- 记录异常日志

---

## ✅ 验收标准

### 功能验收
- [ ] 所有接口正常响应
- [ ] Token生成唯一且安全
- [ ] 验证逻辑准确无误
- [ ] 访问次数限制生效
- [ ] 过期时间准确控制
- [ ] 日志记录完整
- [ ] 通知功能正常

### 性能验收
- [ ] 接口响应时间 < 200ms（95百分位）
- [ ] 支持并发1000 QPS
- [ ] 数据库查询优化（无慢查询）
- [ ] Redis缓存命中率 > 90%

### 安全验收
- [ ] 无SQL注入漏洞
- [ ] 无XSS漏洞
- [ ] CSRF保护生效
- [ ] 敏感数据加密存储
- [ ] 访问日志完整

### 测试验收
- [ ] 单元测试覆盖率 > 80%
- [ ] 集成测试通过
- [ ] 压力测试通过

---

## 📚 参考资源

- [ThinkPHP 6 官方文档](https://www.kancloud.cn/manual/thinkphp6_0/1037479)
- [PHP Best Practices](https://phptherightway.com/)
- [PSR-12 编码规范](https://www.php-fig.org/psr/psr-12/)
- [Redis 最佳实践](https://redis.io/topics/best-practices)

---

## 🔗 相关文档

- [README.md](./README.md) - 核心功能设计
- [database-design.md](./database-design.md) - 数据库设计
- [frontend-plan.md](./frontend-plan.md) - 前端开发计划

---

## 📝 开发日志

| 日期 | 阶段 | 完成情况 | 备注 |
|------|------|---------|------|
| - | - | - | - |
