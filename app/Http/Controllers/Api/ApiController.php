<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Tools\CustomException;
use App\Traits\Api;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    use Api;
    use ApiResponse;

    // 服务
    protected $service;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }
}
