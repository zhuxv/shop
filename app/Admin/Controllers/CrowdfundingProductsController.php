<?php

namespace App\Admin\Controllers;

use App\Models\CrowdfundingProduct;
use App\Models\Product;
use Encore\Admin\Form;
use Encore\Admin\Grid;

class CrowdfundingProductsController extends CommonProductsController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '众筹商品';

    public function getProductType()
    {
        return Product::TYPE_CROWDEFUNDING;
        // TODO: Implement getProductType() method.
    }

    protected function customGrid(Grid $grid)
    {
        // TODO: Implement customGrid() method.
        $grid->id('ID')->sortable();
        $grid->title('商品类型');
        $grid->on_sale('已上架')->display(function ($value){
            return $value ? '是': '否';
        });
        $grid->price('价格');
        $grid->column('crowdfunding.target_amount', '目标金额');
        $grid->column('crowdfunding.end_at', '结束时间');
        $grid->column('crowdfunding.total_amount', '目前金额');
        $grid->column('crowdfunding.status', ' 状态')->display(function ($value) {
            return CrowdfundingProduct::$statusMap[$value];
        });
    }

    protected function customForm(Form $form)
    {
        // TODO: Implement customForm() method.
        // 众筹相关字段
        $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    }

}
