<?php


namespace App\Traits;

use App\Enums\ResponseCodeEnums;

trait ApiResponse
{
    /**
     * @param null $data
     * @param array $header
     * @param string $message
     * @return mixed
     * 成功响应
     */
    public function success($data = null, $header = [], $message = '成功'){
        return $this->_response(ResponseCodeEnums::SUCCESS, $message, $data, $header);
    }

    /**
     * @param $errno
     * @param $message
     * @param null $data
     * @param array $header
     * @return mixed
     * 失败响应
     */
    public function fail($code, $message, $data = null, $header = []){
        return $this->_response($code, $message, $data, $header);
    }

    /**
     * @param null $data
     * @param array $header
     * @return mixed
     * 未登录响应
     */
    public function unlogin($data = null, $header = []){
        return $this->_response(ResponseCodeEnums::UNLOGIN, '尚未登录', $data, $header);
    }

    /**
     * @return mixed
     * 未授权响应
     */
    public function forbidden($data = null, $header = []){
        return $this->_response(ResponseCodeEnums::FORBIDDEN, '接口尚未授权', $data, $header);
    }

    /**
     * @param null $data
     * @param array $header
     * @return mixed
     * 余额不足
     */
    public function needPay($data = null, $header = []){
        return $this->_response(ResponseCodeEnums::NEED_PAY, '余额不足, 请前往充值', $data, $header);
    }

    /**
     * @param null $data
     * @param array $header
     * @return mixed
     * 网络繁忙
     */
    public function networkError($data = null, $header = []){
        return $this->_response(ResponseCodeEnums::NETWORK_ERROR, '网络繁忙，请稍后再试', $data, $header);
    }

    /**
     * @param $ret
     * @return mixed
     * 按返回值响应
     */
    public function ret($ret, $data = null, $header = []){
        if($ret){
            return $this->success($data, $header);
        }else{
            return $this->fail(ResponseCodeEnums::FAIL, '失败');
        }
    }

    /**
     * @param $errno
     * @param $message
     * @param null $data
     * @param array $header
     * @return mixed
     * 公共响应
     */
    public function _response($code, $message, $data = null, $header = []){
        $content = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];

        // json
        return response()->json($content, 200, $header);
    }
}
