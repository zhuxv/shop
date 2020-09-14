<?php

namespace App\Admin\Controllers;

use App\Models\User;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;

class UsersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '用户';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new User());

        $grid->id('ID');
        $grid->name('用户名');
        $grid->email('邮箱');
        $grid->email_verified_at('验证邮箱')->display(function($value){
            return $value?'是':'否';
        });
        $grid->created_at('注册时间');
        // 不显示新建按钮
        $grid->disableCreateButton();
        // 不显示编辑按钮
        $grid->disableActions();

        $grid->tools(function ($tools){
            // 禁用批量删除按钮
            $tools->batch(function($batch){
                $batch->disableDelete();
            });
        });

        return $grid;
    }

}
