<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Jobs\Middleware\Test;
use Exception;

class TestQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 应该处理任务的队列连接
     *
     * @var string
     */
//    public $connection = 'redis';

    /**
     * 任务可尝试的次数
     *
     * @var int
     */
//    public $tries = 2;

    /**
     * 重试任务前等待的秒数
     *
     * @var int
     */
//    public $retryAfter = 1;

    /**
     * 如果任务的模型不再存在，则删除该任务
     *
     * @var bool
     */
//    public $deleteWhenMissingModels = true;

    /**
     * 确定作业应该超时的时间
     *
     * @return \DateTime
     */
//    public function retryUntil()
//    {
//        return now()->addSeconds(30);
//    }

    /**
     * 定义任务中间件
     *
     * @return array
     */
//    public function middleware()
//    {
//        return [new Test];
//    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::warning('hhhh');
        echo 111;
    }

    /**
     * 任务未能处理
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // 给用户发送失败通知, 等等...
    }

}
