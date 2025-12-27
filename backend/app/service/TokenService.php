<?php
declare(strict_types=1);

namespace app\service;

use app\model\OnetimeLink;
use app\model\OnetimeLinkLog;
use Ramsey\Uuid\Uuid;
use think\facade\Cache;
use think\facade\Db;

/**
 * Token管理服务
 */
class TokenService
{
    // 缓存前缀
    const CACHE_PREFIX = 'onetime:token:';

    /**
     * 生成安全的随机Token
     */
    public function generateToken(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * 创建一次性链接
     */
    public function createLink(array $params): OnetimeLink
    {
        $token = $this->generateToken();

        // 确保Token唯一
        while (OnetimeLink::where('token', $token)->find()) {
            $token = $this->generateToken();
        }

        $link = new OnetimeLink();
        $link->token = $token;
        $link->user_id = $params['user_id'] ?? null;
        $link->content_type = $params['content_type'];
        $link->content_data = $this->encryptData($params['content_data'] ?? '');
        $link->max_visits = $params['max_visits'] ?? 1;
        $link->current_visits = 0;
        $link->expire_time = $params['expire_time'] ?? 0;
        $link->bind_ip = $params['bind_ip'] ?? null;
        $link->status = OnetimeLink::STATUS_ACTIVE;
        $link->notify_on_visit = $params['notify_on_visit'] ?? 0;
        $link->metadata = $params['metadata'] ?? null;
        $link->save();

        // 写入缓存
        $this->cacheToken($link);

        return $link;
    }

    /**
     * 验证Token
     */
    public function verifyToken(string $token, ?string $ipAddress = null): array
    {
        // 先从缓存获取
        $link = $this->getFromCache($token);

        if (!$link) {
            $link = OnetimeLink::where('token', $token)->find();

            // [OTL] 调试：如果找不到，检查数据库连接
            if (!$link) {
                $debug = [
                    'search_token' => $token,
                    'total_count' => OnetimeLink::count(),
                    'all_tokens' => OnetimeLink::column('token'),
                    'table_name' => (new OnetimeLink())->getTable(),
                ];
                return ['valid' => false, 'error' => 'Token不存在', 'code' => OnetimeLinkLog::RESULT_INVALID, 'debug' => $debug];
            }

            if ($link) {
                $this->cacheToken($link);
            }
        }

        if (!$link) {
            return ['valid' => false, 'error' => 'Token不存在', 'code' => OnetimeLinkLog::RESULT_INVALID];
        }

        if (!$link->isActive()) {
            return ['valid' => false, 'error' => '链接已失效', 'code' => OnetimeLinkLog::RESULT_INVALID];
        }

        if ($link->isExpired()) {
            return ['valid' => false, 'error' => '链接已过期', 'code' => OnetimeLinkLog::RESULT_EXPIRED];
        }

        if ($link->isExhausted()) {
            return ['valid' => false, 'error' => '访问次数已用尽', 'code' => OnetimeLinkLog::RESULT_EXHAUSTED];
        }

        // IP绑定验证
        if ($link->bind_ip && $ipAddress && $link->bind_ip !== $ipAddress) {
            return ['valid' => false, 'error' => 'IP地址不匹配', 'code' => OnetimeLinkLog::RESULT_IP_MISMATCH];
        }

        return ['valid' => true, 'link' => $link];
    }

    /**
     * 消费Token
     */
    public function consumeToken(string $token, string $ipAddress, string $userAgent = ''): array
    {
        $verify = $this->verifyToken($token, $ipAddress);

        if (!$verify['valid']) {
            // 记录失败日志
            $this->logAccess($token, null, $verify['code'], $ipAddress, $userAgent, $verify['error']);
            return $verify;
        }

        $link = $verify['link'];

        // 事务处理
        Db::startTrans();
        try {
            // 更新访问次数
            $link->current_visits += 1;

            // 检查是否达到上限
            if ($link->current_visits >= $link->max_visits) {
                $link->status = OnetimeLink::STATUS_USED;
            }

            // 首次访问绑定IP
            if (!$link->bind_ip && isset($link->metadata['bind_ip_on_first_visit']) && $link->metadata['bind_ip_on_first_visit']) {
                $link->bind_ip = $ipAddress;
            }

            $link->save();

            // 记录成功日志
            $logId = $this->logAccess($token, $link->id, OnetimeLinkLog::RESULT_SUCCESS, $ipAddress, $userAgent);

            // 触发通知
            if ($link->notify_on_visit) {
                $this->triggerNotification($link, $logId);
            }

            // 更新缓存
            $this->cacheToken($link);

            Db::commit();

            return [
                'valid' => true,
                'content_type' => $link->content_type,
                'content_data' => $this->decryptData($link->content_data),
            ];
        } catch (\Exception $e) {
            Db::rollback();
            return ['valid' => false, 'error' => '系统错误', 'code' => OnetimeLinkLog::RESULT_OTHER_ERROR];
        }
    }

    /**
     * 撤销Token
     */
    public function revokeToken(int $id, ?int $userId = null): bool
    {
        $query = OnetimeLink::where('id', $id);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $link = $query->find();
        if (!$link) {
            return false;
        }

        $link->status = OnetimeLink::STATUS_REVOKED;
        $link->save();

        // 删除缓存
        $this->deleteFromCache($link->token);

        return true;
    }

    /**
     * 延长过期时间
     */
    public function extendExpire(int $id, int $extraSeconds, ?int $userId = null): ?OnetimeLink
    {
        $query = OnetimeLink::where('id', $id);
        if ($userId) {
            $query->where('user_id', $userId);
        }

        $link = $query->find();
        if (!$link) {
            return null;
        }

        if ($link->expire_time > 0) {
            $link->expire_time += $extraSeconds;
        } else {
            $link->expire_time = time() + $extraSeconds;
        }
        $link->save();

        // 更新缓存
        $this->cacheToken($link);

        return $link;
    }

    /**
     * 记录访问日志
     */
    protected function logAccess(string $token, ?int $linkId, int $result, string $ip, string $userAgent, ?string $error = null): int
    {
        // [OTL] 如果没有有效的link_id，不记录日志（避免外键约束错误）
        if (!$linkId) {
            return 0;
        }

        $log = new OnetimeLinkLog();
        $log->link_id = $linkId;
        $log->token = $token;
        $log->visit_time = date('Y-m-d H:i:s');
        $log->ip_address = $ip;
        $log->user_agent = substr($userAgent, 0, 512);
        $log->visit_result = $result;
        $log->error_message = $error;
        $log->referer = request()->header('referer', '');
        $log->save();

        return (int)$log->id;
    }

    /**
     * 触发通知
     */
    protected function triggerNotification(OnetimeLink $link, int $logId): void
    {
        // TODO: 实现通知逻辑，可通过队列异步发送
    }

    /**
     * 加密数据
     */
    protected function encryptData(string $data): string
    {
        $key = config('app.encryption_key', 'default_encryption_key_32chars');
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * 解密数据
     */
    protected function decryptData(string $data): string
    {
        if (empty($data)) {
            return '';
        }
        $key = config('app.encryption_key', 'default_encryption_key_32chars');
        $data = base64_decode($data);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv) ?: '';
    }

    /**
     * 写入缓存
     */
    protected function cacheToken(OnetimeLink $link): void
    {
        $ttl = 86400; // 默认24小时
        if ($link->expire_time > 0) {
            $ttl = max($link->expire_time - time(), 60);
        }
        Cache::set(self::CACHE_PREFIX . $link->token, $link->toArray(), $ttl);
    }

    /**
     * 从缓存获取
     */
    protected function getFromCache(string $token): ?OnetimeLink
    {
        $data = Cache::get(self::CACHE_PREFIX . $token);
        if ($data) {
            $link = new OnetimeLink();
            $link->data($data, true);
            $link->exists(true);
            return $link;
        }
        return null;
    }

    /**
     * 删除缓存
     */
    protected function deleteFromCache(string $token): void
    {
        Cache::delete(self::CACHE_PREFIX . $token);
    }
}
