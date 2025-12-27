<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 访问限流中间件
 */
class RateLimitMiddleware
{
    // 限流规则：每分钟最大请求次数
    protected int $maxRequests = 60;

    // 时间窗口（秒）
    protected int $window = 60;

    public function handle(Request $request, \Closure $next): Response
    {
        $ip = $request->ip();
        $key = 'ratelimit:' . $ip;

        $count = (int)Cache::get($key, 0);

        if ($count >= $this->maxRequests) {
            return json([
                'code' => 429,
                'message' => '访问频率过高，请稍后再试',
                'data' => null,
            ])->header(['Retry-After' => $this->window]);
        }

        Cache::set($key, $count + 1, $this->window);

        return $next($request);
    }
}
