<?php

namespace App\Providers;

use App\Http\ViewComposers\CategoryTreeComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Monolog\Logger;
use Yansongda\Pay\Pay;
use Elasticsearch\ClientBuilder as ESClientBuilder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('alipay', function (){
            $config = config('pay.alipay');
            $config['notify_url'] = 'http://requestbin.leo108.com/wsu6eaws';
            $config['return_url'] = route('payment.alipay.return');
            // 判断当前环境是否为线上环境
            if ( app()->environment() !== 'production' ) {
                $config['mode'] = 'dev';
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            // 调用 Yansongda\Pay 来创建一个支付宝支付对象
            return Pay::alipay($config);
        });

        $this->app->singleton('wechat_pay', function (){
            $config = config('pay.wechat');
            $config['notify_url'] = '';
            if ( app()->environment() !== 'production' ) {
                $config['log']['level'] = Logger::DEBUG;
            } else {
                $config['log']['level'] = Logger::WARNING;
            }
            // 调用 Yansongda\Pay 来创建一个微信支付对象
            return Pay::wechat($config);
        });

        // 注册一个名为 es 的单例
        $this->app->singleton('es', function (){
            // 从配置文件读取 Elasticsearch 服务列表
            $builder = ESClientBuilder::create()->setHosts(config('database.elasticsearch.hosts'));
            // 如果是开发环境
            if ( app()->environment() === 'local' ) {
                // 配置日志, Elasticsearch 的请求和返回数据将打印到日志文件中, 方便我们调试
                $builder->setLogger(app('log')->driver());
            }

            return $builder->build();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        Schema::defaultStringLength(191);
        // 当 Laravel 渲染 products.index 和 products.show 模板时, 就会使用 CategoryTreeComposer 这个来注入类目树变量
        // 同时 Laravel 还支持通配符, 例如 products.* 即代表当渲染 products 目录下的模板时都执行这个 ViewComposer
        \View::composer(['products.index', 'products.show'], CategoryTreeComposer::class);
    }
}
