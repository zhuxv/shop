<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // 使用Gate::guessPolicyNamesUsing 方法来自定义策略文件寻找逻辑
        Gate::guessPolicyNamesUsing(function ($class){
            // class_basename 可以获取类的简短名称
            // 列如传入 \App\Models\User 会返回User
            return '\\App\\Policies\\'.class_basename($class).'Policy';
        });
    }
}
