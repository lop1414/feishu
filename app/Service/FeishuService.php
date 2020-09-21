<?php

namespace App\Service;

use App\Model\Employee;
use App\Model\Message;
use App\Model\TenantAccessToken;
use App\Tools\Api\Feishu;
use App\Tools\CustomException;
use Illuminate\Support\Facades\DB;

class FeishuService extends BaseService
{
    // 飞书
    protected $feishu;

    /**
     * constructor.
     */
    public function __construct(){
        parent::__construct();

        $this->feishu = new Feishu(env('FEISHU_APP_ID'), env('FEISHU_APP_SECRET'));
    }

    /**
     * @param $request
     * @return bool
     * @throws CustomException
     * 批量发送消息
     */
    public function sendMessage($request){
        // 验证规则
        $this->validRule($request->post(), [
            'names' => 'required|array',
            'title' => 'required',
            'content' => 'required',
        ]);

        $names = $request->post('names');
        $title = $request->post('title');
        $content = $request->post('content');

        foreach($names as $name){
            $this->_sendMessage($name, $title, $content);
        }

        return true;
    }

    /**
     * @param $name
     * @param $title
     * @param $content
     * @return bool
     * @throws CustomException
     * 发送消息
     */
    private function _sendMessage($name, $title, $content){
        $employeeModel = new Employee();
        $employee = $employeeModel->where('name', $name)
            ->first();

        if(empty($employee)){
            throw new CustomException([
                'code' => 'NOT_FOUND_EMPLOYEE',
                'message' => "找不到员工{{$name}}",
            ]);
        }

        // 设置 token
        $this->setTenantAccessToken();

        // 发送消息
        $data = $this->feishu->sendTextToOpenid($employee->open_id, $content);

        // 保存
        $messageModel = new Message();
        $messageModel->message_id = $data['message_id'];
        $messageModel->type = 'text';
        $messageModel->title = $title;
        $messageModel->content = $content;
        $messageModel->employee_id = $employee->employee_id;
        $messageModel->save();

        return true;
    }

    /**
     * @return mixed
     * @throws CustomException
     * 设置 token
     */
    public function setTenantAccessToken(){
        $appId = $this->feishu->getAppId();
        $time = date('Y-m-d H:i:s', TIMESTAMP);

        $model = new TenantAccessToken();
        // 数据库获取
        $item = $model->where('app_id', $appId)
            ->where('expired_at', '>', $time)
            ->orderBy('created_at', 'desc')
            ->first();

        if(empty($item->tenant_access_token)){
            // api获取
            $data = $this->feishu->getTenantAccessToken();

            $tenant_access_token = $data['tenant_access_token'];

            // 保存
            $model->app_id = $appId;
            $model->tenant_access_token = $tenant_access_token;
            $model->expired_at = date('Y-m-d H:i:s', TIMESTAMP + $data['expire'] - 10);
            $model->save();
        }else{
            $tenant_access_token = $item->tenant_access_token;
        }

        $this->feishu->setTenantAccessToken($tenant_access_token);

        return $tenant_access_token;
    }

    /**
     * @return array
     * @throws CustomException
     * 同步员工列表
     */
    public function syncEmployees(){
        // 设置 token
        $this->setTenantAccessToken();

        // 获取通讯录
        $contacts = $this->feishu->getContacts();

        // 获取员工列表
        $data = $this->feishu->getEmployees($contacts['authed_employee_ids']);

        $employees = [];
        foreach($data['user_infos'] as $employee){
            $time = date('Y-m-d H:i:s', TIMESTAMP);
            $employee['created_at'] = $time;
            $employee['updated_at'] = $time;
            $employee['extends'] = [];
            $employee['extends'] = json_encode($employee['extends']);
            $employees[] = $employee;
        }

        // 批量插入
        $employeeModel = new Employee();
        $employeeModel->batchInsertOrUpdate($employees);

        return $employees;
    }
}
