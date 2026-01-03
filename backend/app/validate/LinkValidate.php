<?php
declare(strict_types=1);

namespace app\validate;

use think\Validate;

/**
 * 链接验证器
 */
class LinkValidate extends Validate
{
    protected $rule = [
        'content_type' => 'require|in:email_verify,password_reset,magic_login,secret_share,file_download,qrcode_auth,invite_code,custom',
        'content_data' => 'require',
        'max_visits' => 'number|gt:0|max:1000',
        'expire_time' => 'number|egt:0',
        'bind_ip' => 'boolean',
        'notify_on_visit' => 'boolean',
    ];

    protected $message = [
        'content_type.require' => '请选择业务类型',
        'content_type.in' => '业务类型不正确',
        'content_data.require' => '请填写业务数据',
        'max_visits.number' => '最大访问次数必须是数字',
        'max_visits.gt' => '最大访问次数必须大于0',
        'max_visits.max' => '最大访问次数不能超过999',
        'expire_time.number' => '过期时间格式不正确',
        'expire_time.egt' => '过期时间不能为负数',
    ];

    protected $scene = [
        'create' => ['content_type', 'content_data', 'max_visits', 'expire_time', 'bind_ip', 'notify_on_visit'],
    ];
}
