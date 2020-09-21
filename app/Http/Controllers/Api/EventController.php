<?php

namespace App\Http\Controllers\Api;

use App\Enums\ExceptionTypeEnums;
use App\Jobs\CreateErrorLogJob;
use App\Service\ErrorLogService;
use App\Service\FeishuService;
use Illuminate\Http\Request;

class EventController extends ApiController
{
    protected $service;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->service = new FeishuService();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 事件请求验证
     */
    public function challenge(Request $request){
        $data = $request->all();

        dispatch(new CreateErrorLogJob(
                'EVENT_REQUEST_LOG',
                '事件请求日志',
                $data,
                ExceptionTypeEnums::CUSTOM)
        );

        if(env('FEISHU_EVENT_VERIFICATION_TOKEN') == $data['token']){
            return response()->json([
                'challenge' => $data['challenge'],
            ]);
        }
    }
}
