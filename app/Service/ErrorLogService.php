<?php

namespace App\Service;

use App\Enums\ExceptionTypeEnums;
use App\Jobs\CreateErrorLogJob;
use App\Model\ErrorLog;
use App\Tools\CustomException;
use Exception;

class ErrorLogService extends BaseService
{
    /**
     * constructor.
     */
    public function __construct(){
        parent::__construct();

        $this->model = new ErrorLog();

    }

    /**
     * @param $exception
     * 捕获异常
     */
    public function catch($exception){
        if($exception instanceof CustomException) {
            // 自定义异常
            $errorInfo = $exception->getErrorInfo();
            if(!empty($errorInfo->log)){
                dispatch(new CreateErrorLogJob($errorInfo->code, $errorInfo->message, $errorInfo->data, ExceptionTypeEnums::CUSTOM));
            }
        }elseif($exception instanceof Exception){
            // 默认异常
            dispatch(new CreateErrorLogJob($exception->getCode(), $exception->getMessage(), [], ExceptionTypeEnums::DEFAULT));
        }
    }

    /**
     * @param $code
     * @param $message
     * @param $data
     * @param string $exception
     * @return bool
     * 创建
     */
    public function create($code, $message, $data, $exception){
        $this->model->exception = $exception;
        $this->model->code = $code;
        $this->model->message = substr($message, 0, 504);
        $this->model->data = $data;
        $this->model->created_at = date('Y-m-d H:i:s', TIMESTAMP);
        $ret = $this->model->save();

        return $ret;
    }
}
