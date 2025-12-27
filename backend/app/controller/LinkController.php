<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\OnetimeLink;
use app\service\TokenService;
use app\validate\LinkValidate;
use think\exception\ValidateException;
use think\Request;

/**
 * 链接管理控制器
 */
class LinkController extends BaseController
{
    protected TokenService $tokenService;

    protected function initialize()
    {
        $this->tokenService = app(TokenService::class);
    }

    /**
     * 创建一次性链接
     */
    public function create(Request $request)
    {
        try {
            validate(LinkValidate::class)->scene('create')->check($request->post());
        } catch (ValidateException $e) {
            return json(['code' => 400, 'message' => $e->getMessage(), 'data' => null]);
        }

        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $params = [
            'user_id' => $request->user_id ?? null, // 从认证中间件获取
            'device_id' => $deviceId, // 设备ID
            'content_type' => $request->post('content_type'),
            'content_data' => $request->post('content_data', ''),
            'max_visits' => (int)$request->post('max_visits', 1),
            'expire_time' => (int)$request->post('expire_time', 0),
            'bind_ip' => $request->post('bind_ip') ? $request->ip() : null,
            'notify_on_visit' => $request->post('notify_on_visit') ? 1 : 0,
            'metadata' => $request->post('metadata'),
        ];

        $link = $this->tokenService->createLink($params);

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => array_merge($link->toArray(), ['link_url' => $link->link_url]),
        ]);
    }

    /**
     * 验证链接（不消费）
     */
    public function verify(Request $request, string $token)
    {
        $result = $this->tokenService->verifyToken($token, $request->ip());

        if ($result['valid']) {
            return json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'valid' => true,
                    'link' => $result['link']->visible(['id', 'token', 'content_type', 'max_visits', 'current_visits', 'expire_time', 'status'])->toArray(),
                ],
            ]);
        }

        return json([
            'code' => 400,
            'message' => $result['error'],
            'data' => ['valid' => false, 'debug' => $result['debug'] ?? null],
        ]);
    }

    /**
     * 访问链接（消费Token）
     */
    public function access(Request $request, string $token)
    {
        $result = $this->tokenService->consumeToken(
            $token,
            $request->ip(),
            $request->header('user-agent', '')
        );

        if ($result['valid']) {
            return json([
                'code' => 200,
                'message' => 'success',
                'data' => [
                    'content_type' => $result['content_type'],
                    'content_data' => $result['content_data'],
                ],
            ]);
        }

        return json([
            'code' => 400,
            'message' => $result['error'],
            'data' => null,
        ]);
    }

    /**
     * 撤销链接
     */
    public function revoke(Request $request)
    {
        $id = (int)$request->post('id');
        $userId = $request->user_id ?? null;
        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $success = $this->tokenService->revokeToken($id, $userId, $deviceId);

        if ($success) {
            return json(['code' => 200, 'message' => 'success', 'data' => null]);
        }

        return json(['code' => 400, 'message' => '链接不存在或无权操作', 'data' => null]);
    }

    /**
     * 延长过期时间
     */
    public function extend(Request $request)
    {
        $id = (int)$request->post('id');
        $extraSeconds = (int)$request->post('extra_seconds', 86400);
        $userId = $request->user_id ?? null;
        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $link = $this->tokenService->extendExpire($id, $extraSeconds, $userId, $deviceId);

        if ($link) {
            return json(['code' => 200, 'message' => 'success', 'data' => $link->toArray()]);
        }

        return json(['code' => 400, 'message' => '链接不存在或无权操作', 'data' => null]);
    }

    /**
     * 获取链接列表
     */
    public function list(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = (int)$request->get('page_size', 20);
        $status = $request->get('status');
        $contentType = $request->get('content_type');
        $keyword = $request->get('keyword');

        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $query = OnetimeLink::order('created_at', 'desc');

        // [OTL] 设备ID过滤（优先级高于用户ID）
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        } elseif ($request->user_id) {
            // 用户过滤（后备方案）
            $query->where('user_id', $request->user_id);
        }

        // 状态过滤
        if ($status !== null && $status !== '') {
            $query->where('status', (int)$status);
        }

        // 类型过滤
        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        // 关键词搜索
        if ($keyword) {
            $query->where('token', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->each(function ($item) {
            $item->link_url = $item->link_url;
            return $item;
        });

        return json([
            'code' => 200,
            'message' => 'success',
            'data' => [
                'list' => $list->toArray(),
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ],
        ]);
    }

    /**
     * 获取链接详情
     */
    public function detail(Request $request, int $id)
    {
        // [OTL] 获取设备ID
        $deviceId = $request->header('X-Device-Id');

        $query = OnetimeLink::where('id', $id);

        // [OTL] 设备ID过滤
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        } elseif ($request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        $link = $query->with(['logs' => function ($q) {
            $q->order('visit_time', 'desc')->limit(50);
        }])->find();

        if (!$link) {
            return json(['code' => 404, 'message' => '链接不存在', 'data' => null]);
        }

        $data = $link->toArray();
        $data['link_url'] = $link->link_url;

        return json(['code' => 200, 'message' => 'success', 'data' => $data]);
    }
}
