<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * 获取计划事件默认使用的时区。
     *
     * @return \DateTimeZone|string|null
     */
    protected function scheduleTimezone()
    {
        return 'Asia/Shanghai';
    }

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        // 每周一 13:00执行
//        $schedule->call(function(){

//        })->weekly()->mondays()->at('13:00')->before(function(){
            // 任务即将开始
//        })->after(function(){
            // 任务完成
//        })->onSuccess(function(){
            // 任务执行成功
//        })->onFailure(function(){
            // 任务执行失败
//        });
        // 限制在工作日 8-17点每小时执行一次
//        $schedule->call(function(){

//        })->weekdays()->hourly()->between('8:00', '17:00')->when(function(){
//            return true;
//        });
        // 每分钟执行一次
        $schedule->command('cron:finish-crowdfunding')->everyMinute();
        // 每天凌晨 00:00 执行
        $schedule->command('cron:calculate-installment-fine')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
