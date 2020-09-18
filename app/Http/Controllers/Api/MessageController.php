<?php

namespace App\Http\Controllers\Api;

use App\Service\FeishuService;
use Illuminate\Http\Request;

class MessageController extends ApiController
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

    public function send(Request $request){
        $ret = $this->service->sendMessage($request);
        return $this->ret($ret);
    }
}
