<?php

namespace App\Admin\Controllers;

use App\Models\Product;

class ProductsController extends CommonProductsController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '商品';

    /**
     * 定义商品类型
     * @return string
     */
    public function getProductType()
    {
        return Product::TYPE_NORMAL;
        // TODO: Implement getProductType() method.
    }

    protected function customGrid($grid)
    {
        // TODO: Implement customGrid() method.
        $grid->model()->with(['category']);
        $grid->id('ID')->sortable();
        $grid->title('商品名称');
        $grid->column('category.name', '类目');
        $grid->on_sale('已上架')->display(function ($value){
            return $value ? '是' : '否';
        });
        $grid->price('价格');
        $grid->rating('评分');
        $grid->sold_count('销量');
        $grid->review_count('评论数');
    }

    protected function customForm($form)
    {
        // TODO: Implement customForm() method.
    }

}
