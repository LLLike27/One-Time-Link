<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\OnetimeLink;
use app\service\LogService;
use think\Request;

/**
 * 日志查询控制器
 */
class LogController extends BaseController
{
    protected LogService $logService;

    protected function initialize()
    {
        $this->logService = app(LogService::class);
    }

    /**
     * 访问日志列表
     */
    public function list(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = (int)$request->get('page_size', 20);
        $linkId = $request->get('link_id');
        $token = $request->get('token');

        if ($linkId) {
            $data = $this->logService->getLogsByLinkId((int)$linkId, $page, $pageSize);
        } elseif ($token) {
            $data = $this->logService->getLogsByToken($token, $page, $pageSize);
        } else {
            return json(['code' => 400, 'message' => '请提供link_id或token参数', 'data' => null]);
        }

        return json(['code' => 200, 'message' => 'success', 'data' => $data]);
    }

    /**
     * 统计数据
     */
    public function statistics(Request $request)
    {
        $userId = $request->user_id ?? null;
        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $query = OnetimeLink::field([
            'COUNT(*) as total_links',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_links',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as used_links',
            'SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as expired_links',
            'SUM(current_visits) as total_visits',
        ]);

        // [OTL] 设备ID优先
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        } elseif ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = $query->find();

        // 计算成功率（简化版，直接用current_visits作为成功访问数）
        $totalVisits = (int)($stats['total_visits'] ?? 0);
        $successRate = $totalVisits > 0 ? 100 : 0;

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_links' => (int)($stats['total_links'] ?? 0),
                'active_links' => (int)($stats['active_links'] ?? 0),
                'used_links' => (int)($stats['used_links'] ?? 0),
                'expired_links' => (int)($stats['expired_links'] ?? 0),
                'total_visits' => $totalVisits,
                'success_rate' => $successRate,
            ],
        ]);
    }
}
