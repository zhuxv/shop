<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\TestShipped;
use Illuminate\Http\Request;
use App\Extensions\Other\UploaderHandler;
use Illuminate\Support\Facades\Mail;
use App\Jobs\TestQueue;

class CommonController extends Controller
{

    /**
     * 单图片上传
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function uploaderImage( Request $request, UploaderHandler $uploader )
    {
        if ( ! $request->hasFile('file') )
        {
            return failed('文件不存在');
        }
        $config = config('filesystems');
        $action = 'upload_'.$config['default'];
        $path = $uploader->$action( $request->file );
        return $path?success():failed('上传出错');
    }

    /**
     * 测试发送邮件
     *
     * @return mixed
     */
    public function sendMail()
    {
        Mail::to('1071786204@qq.com')->send(new TestShipped());
    }

    /**
     * 测试队列
     *
     * @return \Illuminate\Support\Facades\Response
     */
    public function queue()
    {
        // 调度任务,并延迟十分钟
        TestQueue::dispatch()
            ->delay(now()->addMinutes(10));
        return accepted();
    }

}
