<?php

namespace App\Jobs;

use App\Service\ErrorLogService;

class CreateErrorLogJob extends Job
{
    protected $code;
    protected $message;
    protected $data;
    protected $exception;

    /**
     * CreateErrorLogJob constructor.
     * @param $code
     * @param $message
     * @param $data
     * @param $exception
     */
    public function __construct($code, $message, $data, $exception)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->exception = $exception;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $errorLogService = new ErrorLogService();
        $errorLogService->create($this->code, $this->message, $this->data, $this->exception);
    }
}
