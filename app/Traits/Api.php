<?php

namespace App\Traits;

use App\Enums\ResponseCodeEnums;
use App\Tools\CustomException;
use Illuminate\Support\Facades\Validator;

trait Api
{
    /**
     * @param $data
     * @param $rule
     * @param array $message
     * @return bool
     * @throws CustomException
     * 验证规则
     */
    protected function validRule($data, $rule, $message = []){
        // 验证器
        $validator = Validator::make($data, $rule, $message);

        // 验证不通过
        if($validator->fails()){
            throw new CustomException([
                'code' => ResponseCodeEnums::UNVALID,
                'message' => $validator->errors()->first(),
            ]);
        }

        return true;
    }

    /**
     * @param $data
     * 调试打印
     */
    protected function debug_dump($data){
        if($this->is_debug()){
            var_dump($data);
        }
    }

    /**
     * @return bool
     * 是否调试
     */
    public function is_debug(){
        return env('APP_DEBUG') == true;
    }
}
