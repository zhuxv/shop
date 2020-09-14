<?php

use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建30个商品
        $products = factory(\App\Models\Product::class, 30)->create();
        foreach ( $products as $product ) {
            // 创建3个SKU, 并且每个SKU的 product_id 字段都设为当前循环的商品 id
            $skus = factory(\App\Models\ProductSku::class, 3)->create(['product_id' => $product->id]);
            // 找出最低的SKU价格, 把商品设置为该价格
            $product->update(['price'=>$skus->min('price')]);
        }
    }
}
