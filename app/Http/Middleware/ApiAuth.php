<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use Closure;

class ApiAuth
{
    use ApiResponse;

    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next){
        $req = $request->all();

        if(empty($req['time']) || empty($req['sign'])){
            return $this->fail('PARAM_MISSING', '参数缺失');
        }

        if(TIMESTAMP - $req['time'] > 60){
            return $this->fail('TIME_EXPIRED', '请求已失效');
        }

        // 签名
        $sign = $this->makeSign($req, env('API_SECRET'));
        if($sign != $req['sign']){
            return $this->fail('SIGN_ERROR', '签名错误');
        }

        return $next($request);
    }

    /**
     * @param $param
     * @param $key
     * @return string
     * 构造签名
     */
    public function makeSign($param, $key){
        // sign字段不参与签名
        unset($param['sign']);

        // 按参数名字典排序
        ksort($param);

        // 参数拼接字符串
        $query = http_build_query($param);

        // 签名
        $sign = md5($query . $param['time'] . $key);

        return $sign;
    }
}
