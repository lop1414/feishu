<?php

namespace App\Service;

use App\Enums\ExceptionTypeEnums;
use App\Jobs\CreateErrorLogJob;
use App\Model\CallbackEvent;
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
     * @param $token
     * @return bool
     * 验证 token
     */
    private function checkEventToken($token){
        return $token == env('FEISHU_EVENT_VERIFICATION_TOKEN');
    }

    /**
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     * @throws CustomException
     * 事件订阅
     */
    public function event($request){
        $data = $request->all();

        // 验证 token
        if($this->checkEventToken($data['token'])){

            // 订阅地址校验
            if(isset($data['challenge'])){
                return $this->eventReturn([
                    'challenge' => $data['challenge'],
                ]);
            }

            #TODO:uuid验证
            $callbackEvent = new CallbackEvent();
            $callbackEvent->callback_type = $data['type'] ?? '';
            $callbackEvent->event_type = $data['event']['type'] ?? '';
            $callbackEvent->app_id = $data['event']['app_id'] ?? '';
            $callbackEvent->tenant_key = $data['event']['tenant_key'] ?? '';
            $callbackEvent->extends = $data['event'] ?? [];
            $callbackEvent->save();

            // 回调事件类型
            $callbackType = $data['type'] ?? '';
            if($callbackType == 'event_callback'){
                $this->eventHandle($data['event']);
            }
        }else{
            // 无效token
            dispatch(new CreateErrorLogJob(
                    'EVENT_REQUEST_ERROR_LOG',
                    '事件请求错误日志',
                    $data,
                    ExceptionTypeEnums::CUSTOM)
            );
        }
    }

    /**
     * @param $event
     * @throws CustomException
     * 事件处理
     */
    private function eventHandle($event){
        // 设置 token
        $this->setTenantAccessToken();

        if($event['type'] == 'message'){
            // 消息事件
            $msgType = $event['msg_type'] ?? '';
            switch($msgType){
                case 'text':
                    $this->messageEventText($event);
                    break;
            }
        }else{
            // 其他事件
        }
    }

    /**
     * @param $event
     * @return bool
     * @throws CustomException
     * 文本消息事件
     */
    public function messageEventText($event){
        $keywordList = $this->getKeywordList();

        $text = trim($event['text_without_at_bot']);

        $keywords = array_map(function($value){
            return "#{$value}#";
        }, array_column($keywordList, 'id'));

        if(in_array($text, $keywords)){
            $replyText = $this->keywordHandle(trim($text, '#'));
        }else{
            $replyText = $this->getHelp();
        }

        // 回复
        if($event['chat_type'] == 'private'){
            // 私聊
            $this->sendMessageToOpenId($event['open_id'], '机器人私聊回复', $replyText);
        }elseif($event['chat_type'] == 'group'){
            // 群聊
            $employeeModel = new Employee();
            $employee = $employeeModel->where('open_id', $event['open_id'])->first();
            $replyName = $employee->name ?? '';
            $this->sendMessageToChatId($event['open_chat_id'], '机器人群聊回复', "<at open_id=\"{$event['open_id']}\">@{$replyName}</at> ". $replyText);
        }

        return true;
    }

    /**
     * @return array
     * 获取关键词列表
     */
    public function getKeywordList(){
        return [
            ['id' =>'hi', 'name' => '打招呼'],
            ['id' =>'time', 'name' => '查询当前时间'],
            ['id' =>'chp', 'name' => '彩虹屁'],
        ];
    }

    /**
     * @return string
     * 获取帮助
     */
    public function getHelp(){
        $tmp = [];
        $i = 1;
        foreach($this->getKeywordList() as $keyword){
            $tmp[] = "{$i}.{$keyword['name']}请输入:#{$keyword['id']}#";
            $i++;
        }
        $help = "你说的我不太懂,可以输入下列关键字,执行对应操作哦\n". implode("\n", $tmp);
        return $help;
    }

    /**
     * @param $keyword
     * @return false|string
     * 关键字处理
     */
    public function keywordHandle($keyword){
        if($keyword == 'hi'){
            return '你好呀~';
        }elseif($keyword == 'time'){
            return '现在是北京时间 '. date('Y-m-d H:i:s');
        }elseif($keyword == 'chp'){
            return file_get_contents('https://chp.shadiao.app/api.php');
        }else{
            return "sending {$keyword}...";
        }
    }

    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     * 事件公共返回
     */
    public function eventReturn($data){
        return response()->json($data);
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

        // 所有员工
        $employeeModel = new Employee();
        $employees = $employeeModel->get();

        // 员工名称
        $employeeNames = $employees->pluck('name');

        // 未知员工
        $notFoundNames = [];
        foreach($names as $name){
            if(!in_array($name, $employeeNames->toArray())){
                $notFoundNames[] = $name;
            }
        }
        if(!empty($notFoundNames)){
            throw new CustomException([
                'code' => 'NOT_FOUND_EMPLOYEE_NAMES',
                'message' => "存在未知员工名称",
                'data' => [
                    'not_found_employee_names' => $notFoundNames,
                ],
            ]);
        }

        // 员工映射
        $employeeMap = $employees->keyBy('name');

        foreach($names as $name){
            if(!isset($employeeMap[$name])){
                continue;
            }
            $this->sendMessageToOpenId($employeeMap[$name]->open_id, $title, $content);
        }

        return true;
    }

    /**
     * @param $openId
     * @param $title
     * @param $content
     * @return bool
     * @throws CustomException
     * 私聊发送消息
     */
    private function sendMessageToOpenId($openId, $title, $content){
        return $this->_sendMessage('open_id', $openId, $title, $content);
    }

    /**
     * @param $chatId
     * @param $title
     * @param $content
     * @return bool
     * @throws CustomException
     * 群聊发送消息
     */
    private function sendMessageToChatId($chatId, $title, $content){
        return $this->_sendMessage('chat_id', $chatId, $title, $content);
    }

    /**
     * @param $targetType
     * @param $targetId
     * @param $title
     * @param $content
     * @return bool
     * @throws CustomException
     * 发送消息
     */
    private function _sendMessage($targetType, $targetId, $title, $content){
        // 设置 token
        $this->setTenantAccessToken();

        if($targetType == 'open_id'){
            // 发送消息
            $data = $this->feishu->sendTextToOpenId($targetId, $content);
        }elseif($targetType == 'chat_id'){
            $data = $this->feishu->sendTextToChatId($targetId, $content);
        }else{
            throw new CustomException([
                'code' => 'TARGET_TYPE_ERROR',
                'message' => '目标类型错误',
            ]);
        }

        // 保存
        $messageModel = new Message();
        $messageModel->message_id = $data['message_id'];
        $messageModel->type = 'text';
        $messageModel->title = $title;
        $messageModel->content = $content;
        $messageModel->target_type = $targetType;
        $messageModel->target_id = $targetId;
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
