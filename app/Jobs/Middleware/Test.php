<?php

namespace App\Jobs\Middleware;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Test
{
    /**
     * 处理队列任务
     *
     * @param mixed job
     * @param callable $next
     * @return mixed
     */
    public function handle( $job, $next )
    {
        //
        $next($job);
    }
}
