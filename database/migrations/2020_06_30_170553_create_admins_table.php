<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdminsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( Schema::hasTable('admins') ) {
            //更新表字段
//            Schema::table('admin', function (Blueprint $table) {
//
//            });
        } else {
            Schema::create('admins', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->string('username', 18)->unique()->comment('用户名');
                $table->string('password', 255)->comment('密码');
                $table->char('mobile', 11)->nullable()->comment('手机号码');
                $table->timestamp('last_login_at')->nullable()->comment('上次登录时间');
                $table->ipAddress('last_login_ip')->nullable()->comment('上次登录ip');
                $table->bigInteger('login_count')->default(0)->nullable()->comment('登陆次数');
                $table->unsignedTinyInteger('type')->default(1)->nullable()->comment('管理员类型(1-普通管理员 2-超级管理员)');
                $table->unsignedTinyInteger('status')->default(0)->nullable()->comment('状态(0-启用 1-禁用)');
                $table->softDeletes('deleted_at');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('admins');
    }
}
