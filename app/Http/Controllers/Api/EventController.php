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
     * 事件订阅
     */
    public function challenge(Request $request){
        $ret = $this->service->event($request);
        return $ret;
    }
}
