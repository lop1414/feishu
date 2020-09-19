<?php

namespace App\Service;

use App\Tools\Api\Feishu;
use App\Tools\CustomException;
use App\Traits\Api;

class BaseService
{
    use Api;

    // 模型
    protected $model;

    /**
     * BaseService constructor.
     */
    public function __construct(){
        //
    }

    /**
     * @param $id
     * @return mixed
     * @throws CustomException
     * 查找目标
     */
    protected function find($id){
        $item = $this->model->find($id);
        if(empty($item)){
            throw new CustomException([
                'code' => 'NOT_FOUND_TARGET',
                'message' => '找不到目标',
            ]);
        }
        return $item;
    }
}
