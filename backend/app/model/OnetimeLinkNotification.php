<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 通知记录模型
 */
class OnetimeLinkNotification extends Model
{
    // 表名
    protected $name = 'link_notifications';

    // 主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = 'timestamp';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    // 类型转换
    protected $type = [
        'id' => 'integer',
        'link_id' => 'integer',
        'log_id' => 'integer',
        'user_id' => 'integer',
        'send_status' => 'integer',
    ];

    // 通知类型
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_PUSH = 'push';
    const TYPE_WEBHOOK = 'webhook';

    // 发送状态
    const STATUS_PENDING = 0;   // 待发送
    const STATUS_SENDING = 1;   // 发送中
    const STATUS_SUCCESS = 2;   // 成功
    const STATUS_FAILED = 3;    // 失败

    /**
     * 关联链接
     */
    public function link()
    {
        return $this->belongsTo(OnetimeLink::class, 'link_id', 'id');
    }

    /**
     * 关联日志
     */
    public function log()
    {
        return $this->belongsTo(OnetimeLinkLog::class, 'log_id', 'id');
    }
}
