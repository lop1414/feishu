<?php

namespace App\Tools\Api;

use App\Tools\CustomException;

class Feishu
{
    /**
     * 公共接口地址
     */
    const BASE_URL = 'https://open.feishu.cn/open-apis';

    /**
     * @var
     * 应用id
     */
    protected $appId;

    /**
     * @var
     * 应用秘钥
     */
    protected $appSecret;

    /**
     * @var
     * token
     */
    protected $tenantAccessToken;

    /**
     * Feishu constructor.
     * @param $appId
     * @param $appSecret
     */
    public function __construct($appId, $appSecret){
        $this->setApp($appId, $appSecret);
    }

    /**
     * @param $appId
     * @param $appSecret
     * @return bool
     * 设置应用信息
     */
    public function setApp($appId, $appSecret){
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        return true;
    }

    /**
     * @return 应用id
     * 获取应用id
     */
    public function getAppId(){
        return $this->appId;
    }

    /**
     * @param $tenantAccessToken
     * @return bool
     * 设置 token
     */
    public function setTenantAccessToken($tenantAccessToken){
        $this->tenantAccessToken = $tenantAccessToken;
        return true;
    }

    /**
     * @param $api
     * @return string
     * @throws CustomException
     * 获取接口地址
     */
    public function getApiUrl($api){
        $apiUriMap = [
            'get_tenant_access_token' => 'auth/v3/tenant_access_token/internal/',
            'get_scope_contact' => 'contact/v1/scope/get',
            'user_batch_get' => 'contact/v1/user/batch_get',
            'message_send' => 'message/v4/send/',
        ];

        if(!isset($apiUriMap[$api])){
            throw new CustomException([
                'code' => 'NOT_FOUND_API_URL',
                'message' => '找不到接口地址',
            ]);
        }

        $apiuri = self::BASE_URL .'/'. $apiUriMap[$api];
        return $apiuri;
    }

    /**
     * @return mixed
     * @throws CustomException
     * 获取 token
     */
    public function getTenantAccessToken(){
        $url = $this->getApiUrl('get_tenant_access_token');

        $param = [
            'app_id' => $this->appId,
            'app_secret' => $this->appSecret,
        ];

        $ret = $this->publicRequest($url, $param);

        return $ret;
    }

    /**
     * @return mixed
     * @throws CustomException
     * 获取通讯录
     */
    public function getContacts(){
        $url = $this->getApiUrl('get_scope_contact');

        $ret = $this->authRequest($url);

        return $ret;
    }

    /**
     * @param $employee_ids
     * @return mixed
     * @throws CustomException
     * 获取员工列表
     */
    public function getEmployees($employee_ids){
        $url = $this->getApiUrl('user_batch_get');

        $query = [];
        foreach($employee_ids as $employee_id){
            $query[] = 'employee_ids='. $employee_id;
        }

        $url .= '?'. implode("&", $query);

        $ret = $this->authRequest($url);

        return $ret;
    }

    /**
     * @param $openid
     * @param $content
     * @return mixed
     * @throws CustomException
     * 发送文本至openid
     */
    public function sendTextToOpenid($openid, $content){
        return $this->sendText('open_id', $openid, $content);
    }

    /**
     * @param $target
     * @param $targetId
     * @param $content
     * @return mixed
     * @throws CustomException
     * 发送文本
     */
    public function sendText($target, $targetId, $content){
        $url = $this->getApiUrl('message_send');

        $param = [
            $target => $targetId,
            "msg_type" => 'text',
            "content" => [
                'text' => $content,
            ],
        ];

        $ret = $this->authRequest($url, $param, 'POST');

        return $ret;
    }

    /**
     * @param $url
     * @param $param
     * @param string $method
     * @param array $header
     * @return mixed
     * @throws CustomException
     * 认证请求
     */
    public function authRequest($url, $param = [], $method = 'GET', $header = []){
        // header 添加 Authorization
        $header = array_merge([
            'Authorization:Bearer '. $this->tenantAccessToken
        ], $header);

        $ret = $this->publicRequest($url, $param, $method, $header);

        return $ret['data'];
    }

    /**
     * @param $url
     * @param $param
     * @param string $method
     * @param array $header
     * @return mixed
     * @throws CustomException
     * 公共请求
     */
    private function publicRequest($url, $param = [], $method = 'GET', $header = []){
        $param = json_encode($param);

        $header = array_merge([
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($param)
        ], $header);

        $ret = $this->curlRequest($url, $param, $method, $header);

        $result = json_decode($ret, true);

        if(empty($result) || $result['code'] != 0){
            // 错误提示
            $errorMessage = $result['msg'] ?? '公共请求错误';

            throw new CustomException([
                'code' => 'PUBLIC_REQUEST_ERROR',
                'message' => $errorMessage,
                'log' => true,
                'data' => [
                    'url' => $url,
                    'header' => $header,
                    'param' => $param,
                    'result' => $result,
                ],
            ]);
        }

        return $result;
    }

    /**
     * @param $url
     * @param $param
     * @param string $method
     * @param array $header
     * @return bool|string
     * CURL请求
     */
    private function curlRequest($url, $param = [], $method = 'GET', $header = []){
        $method = strtoupper($method);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $header = array_merge($header, ['Connection: close']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        if(stripos($url, 'https://') === 0){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , false);
        }

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if($method == 'POST'){
            curl_setopt($ch, CURLOPT_POST, true);
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }
}
