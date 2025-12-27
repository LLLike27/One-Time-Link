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

        $query = OnetimeLink::field([
            'COUNT(*) as total_links',
            'SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_links',
            'SUM(CASE WHEN status = 2 THEN 1 ELSE 0 END) as used_links',
            'SUM(CASE WHEN status = 3 THEN 1 ELSE 0 END) as expired_links',
            'SUM(current_visits) as total_visits',
        ]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $stats = $query->find();

        // 计算成功率
        $successVisits = (int)OnetimeLink::hasWhere('logs', function ($q) {
            $q->where('visit_result', 1);
        })->when($userId, function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->sum('current_visits');

        $totalVisits = (int)($stats['total_visits'] ?? 0);
        $successRate = $totalVisits > 0 ? round($successVisits / $totalVisits * 100, 2) : 0;

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'total_links' => (int)($stats['total_links'] ?? 0),
                'active_links' => (int)($stats['active_links'] ?? 0),
                'used_links' => (int)($stats['used_links'] ?? 0),
                'expired_links' => (int)($stats['expired_links'] ?? 0),
                'total_visits' => $totalVisits,
                'success_visits' => $successVisits,
                'success_rate' => $successRate,
            ],
        ]);
    }
}
