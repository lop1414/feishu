<?php

namespace App\Console\Commands;

use App\Service\ErrorLogService;
use App\Service\FeishuService;
use App\Tools\CustomException;
use Illuminate\Console\Command;

class SyncEmployeeCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'sync_employee';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '同步员工列表';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * 示例 :
     */
    public function handle(){
        try{
            $feishuService = new FeishuService();
            $employees = $feishuService->syncEmployees();
            $count = count($employees);
            $this->info("sync success({$count})! \n");
        }catch(CustomException $e){
            $errorInfo = $e->getErrorInfo();
            var_dump($errorInfo);
            $errorLogService = new ErrorLogService();
            $errorLogService->catch($e);
        }
    }
}
