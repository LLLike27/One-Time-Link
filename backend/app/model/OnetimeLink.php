<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 一次性链接模型
 */
class OnetimeLink extends Model
{
    // 表名（不含前缀，框架会自动加上 onetime_ 前缀）
    protected $name = 'links';

    // 主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'timestamp';
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 类型转换
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'max_visits' => 'integer',
        'current_visits' => 'integer',
        'expire_time' => 'integer',
        'status' => 'integer',
        'notify_on_visit' => 'integer',
        'metadata' => 'json',
    ];

    // 状态常量
    const STATUS_ACTIVE = 1;    // 有效
    const STATUS_USED = 2;      // 已使用
    const STATUS_EXPIRED = 3;   // 已过期
    const STATUS_REVOKED = 4;   // 已撤销

    // 内容类型常量
    const TYPE_EMAIL_VERIFY = 'email_verify';
    const TYPE_PASSWORD_RESET = 'password_reset';
    const TYPE_MAGIC_LOGIN = 'magic_login';
    const TYPE_SECRET_SHARE = 'secret_share';
    const TYPE_FILE_DOWNLOAD = 'file_download';
    const TYPE_QRCODE_AUTH = 'qrcode_auth';
    const TYPE_INVITE_CODE = 'invite_code';
    const TYPE_CUSTOM = 'custom';

    /**
     * 关联访问日志
     */
    public function logs()
    {
        return $this->hasMany(OnetimeLinkLog::class, 'link_id', 'id');
    }

    /**
     * 关联通知记录
     */
    public function notifications()
    {
        return $this->hasMany(OnetimeLinkNotification::class, 'link_id', 'id');
    }

    /**
     * 是否有效
     */
    public function isActive(): bool
    {
        return (int)$this->status === self::STATUS_ACTIVE;
    }

    /**
     * 是否过期
     */
    public function isExpired(): bool
    {
        if ((int)$this->expire_time === 0) {
            return false;
        }
        return (int)$this->expire_time < time();
    }

    /**
     * 是否达到访问上限
     */
    public function isExhausted(): bool
    {
        return (int)$this->current_visits >= (int)$this->max_visits;
    }

    /**
     * 获取完整链接URL
     */
    public function getLinkUrlAttr($value, $data): string
    {
        $baseUrl = config('app.frontend_url', request()->domain());
        return $baseUrl . '/verify/' . $data['token'];
    }
}
