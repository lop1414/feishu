<?php

namespace App\Exceptions;

use App\Service\ErrorLogService;
use App\Tools\CustomException;
use App\Traits\Api;
use App\Traits\ApiResponse;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    use Api;
    use ApiResponse;

    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function render($request, Exception $exception)
    {
        // 自定义异常
        if($exception instanceof CustomException){
            $errorInfo = $exception->getErrorInfo();

            // 日志
            $errorLogService = new ErrorLogService();
            $errorLogService->catch($exception);

            // 失败
            return $this->fail($errorInfo->code, $errorInfo->message);
        }

        // 默认异常
        if($exception instanceof Exception){
            $code = $exception->getCode();
            $message = $exception->getMessage();

            if(!empty($message)){
                // 日志
                $errorLogService = new ErrorLogService();
                $errorLogService->catch($exception);

                if($this->is_debug()){
                    return $this->fail($code, $message);
                }else{
                    // 网络繁忙
                    return $this->networkError();
                }
            }
        }
        return parent::render($request, $exception);
    }
}
