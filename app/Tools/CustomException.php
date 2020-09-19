<?php


namespace App\Tools;

use Exception;

class CustomException extends Exception
{
    //重定义构造器使第一个参数 message 变为必须被指定的属性
    public function __construct($error_info, $code=0){
        // 默认值
        $error_info['code'] = $error_info['code'] ?? 'UNKNOWN';
        $error_info['message'] = $error_info['message'] ?? '未知错误';
        $error_info['data'] = $error_info['data'] ?? [];
        $error_info['log'] = $error_info['log'] ?? false;

        // 存储错误信息
        $message = json_encode($error_info);

        //建议同时调用 parent::construct()来检查所有的变量是否已被赋值
        parent::__construct($message, $code);
    }

    /**
     * @return mixed
     * 获取错误信息
     */
    public function getErrorInfo() {
        return json_decode($this->message);
    }

}
