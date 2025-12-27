<?php
declare(strict_types=1);

namespace app\service;

use app\model\OnetimeLinkLog;

/**
 * 日志服务
 */
class LogService
{
    /**
     * 记录访问日志
     */
    public function logAccess(int $linkId, string $token, int $result, array $request): int
    {
        $log = new OnetimeLinkLog();
        $log->link_id = $linkId;
        $log->token = $token;
        $log->visit_time = date('Y-m-d H:i:s');
        $log->ip_address = $request['ip'] ?? '';
        $log->user_agent = substr($request['user_agent'] ?? '', 0, 512);
        $log->device_fingerprint = $request['fingerprint'] ?? null;
        $log->visit_result = $result;
        $log->error_message = $request['error'] ?? null;
        $log->referer = $request['referer'] ?? null;
        $log->extra_data = $request['extra'] ?? null;
        $log->save();

        return (int)$log->id;
    }

    /**
     * 获取Token的访问历史
     */
    public function getLogsByToken(string $token, int $page = 1, int $pageSize = 20): array
    {
        $query = OnetimeLinkLog::where('token', $token)
            ->order('visit_time', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取链接的访问历史
     */
    public function getLogsByLinkId(int $linkId, int $page = 1, int $pageSize = 20): array
    {
        $query = OnetimeLinkLog::where('link_id', $linkId)
            ->order('visit_time', 'desc');

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 获取异常访问日志
     */
    public function getAnomalousLogs(array $criteria = []): array
    {
        $query = OnetimeLinkLog::where('visit_result', '<>', OnetimeLinkLog::RESULT_SUCCESS);

        if (!empty($criteria['ip'])) {
            $query->where('ip_address', $criteria['ip']);
        }
        if (!empty($criteria['start_time'])) {
            $query->where('visit_time', '>=', $criteria['start_time']);
        }
        if (!empty($criteria['end_time'])) {
            $query->where('visit_time', '<=', $criteria['end_time']);
        }

        return $query->order('visit_time', 'desc')
            ->limit($criteria['limit'] ?? 100)
            ->select()
            ->toArray();
    }
}
