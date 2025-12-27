<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

// 一次性链接API路由
Route::group('api/onetime', function () {
    // 创建链接
    Route::post('create', 'app\controller\LinkController@create');

    // 验证链接（不消费）- token包含连字符，使用正则匹配
    Route::get('verify/<token>', 'app\controller\LinkController@verify')->pattern(['token' => '[\w-]+']);

    // 访问链接（消费Token）
    Route::get('access/<token>', 'app\controller\LinkController@access')->pattern(['token' => '[\w-]+']);

    // 撤销链接
    Route::post('revoke', 'app\controller\LinkController@revoke');

    // 延长过期时间
    Route::post('extend', 'app\controller\LinkController@extend');

    // 链接列表
    Route::get('list', 'app\controller\LinkController@list');

    // 链接详情
    Route::get('detail/:id', 'app\controller\LinkController@detail');

    // 访问日志
    Route::get('logs', 'app\controller\LogController@list');

    // 统计数据
    Route::get('statistics', 'app\controller\LogController@statistics');
});

// 公开访问路由（简化路径）
Route::get('verify/<token>', 'app\controller\LinkController@access')->pattern(['token' => '[\w-]+']);

// 健康检查
Route::get('health', function () {
    return json(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
});
