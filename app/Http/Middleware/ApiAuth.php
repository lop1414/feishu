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
            //return $this->fail('TIME_EXPIRED', '请求已失效');
        }

        // 内容串
        $reqSign = $req['sign'];
        unset($req['sign']);
        $query = http_build_query($req);

        // 签名
        $sign = md5($query . $req['time'] . env('API_SECRET'));
        if($sign != $reqSign){
            return $this->fail('SIGN_ERROR', '签名错误');
        }

        return $next($request);
    }
}
