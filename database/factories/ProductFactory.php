<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Product;
use Faker\Generator as Faker;

$factory->define(Product::class, function (Faker $faker) {
    // 从数据库中随机取出一个类目
    $category = \App\Models\Category::query()->where('is_directory', false)->inRandomOrder()->first();

    return [
        'title' => $faker->word,
        'long_title' => $faker->sentence,
        'description' => $faker->sentence,
        'image' => 'images/1.jpg',
        'on_sale' => true,
        'rating' => $faker->numberBetween(0, 5),
        'sold_count' => 0,
        'review_count' => 0,
        'price' => 0,
        // 将取出的类目 ID 赋给 category_id 字段
        // 如果数据库中没有类目则 $category 为null, 同样 category_id 也设为 null
        'category_id' => $category ? $category->id : null
    ];
});
