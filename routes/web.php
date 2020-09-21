<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return 'hello';
});

// 后台公共权限接口
$router->group([
    // 路由前缀
    'prefix' => 'api',
    // 路由中间件
    'middleware' => ['api_auth']
], function () use ($router) {
    $router->group(['prefix' => 'message'], function () use ($router) {
        $router->post('send', 'Api\MessageController@send');
    });
});

$router->post('api/event/challenge', 'Api\EventController@challenge');
