<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 访问日志模型
 */
class OnetimeLinkLog extends Model
{
    // 表名
    protected $name = 'link_logs';

    // 主键
    protected $pk = 'id';

    // 自动时间戳
    protected $autoWriteTimestamp = false;

    // 类型转换
    protected $type = [
        'id' => 'integer',
        'link_id' => 'integer',
        'visit_result' => 'integer',
        'extra_data' => 'json',
    ];

    // 访问结果常量
    const RESULT_SUCCESS = 1;          // 成功
    const RESULT_INVALID = 2;          // Token无效
    const RESULT_EXPIRED = 3;          // 已过期
    const RESULT_EXHAUSTED = 4;        // 次数超限
    const RESULT_IP_MISMATCH = 5;      // IP不匹配
    const RESULT_DEVICE_MISMATCH = 6;  // 设备不匹配
    const RESULT_OTHER_ERROR = 7;      // 其他错误

    /**
     * 关联链接
     */
    public function link()
    {
        return $this->belongsTo(OnetimeLink::class, 'link_id', 'id');
    }
}
