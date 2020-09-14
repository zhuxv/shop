<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{

    const TYPE_NORMAL = 'normal';
    const TYPE_CROWDEFUNDING = 'crowdfunding';
    public static $typeMap = [
        self::TYPE_NORMAL => '普通商品',
        self::TYPE_CROWDEFUNDING => '众筹商品'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'title', 'long_title', 'description', 'image', 'on_sale',
        'rating', 'sold_count', 'review_count', 'price', 'type'
    ];

    /**
     * @var array
     */
    protected $casts = [
        'on_sale' => 'boolean'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function skus()
    {
        return $this->hasMany(ProductSku::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function crowdfunding()
    {
        return $this->hasOne(CrowdfundingProduct::class);
    }

    public function properties()
    {
        return $this->hasMany(ProductProperty::class);
    }

    /**
     * @return mixed
     */
    public function getImageUrlAttribute()
    {
        if ( Str::startsWith($this->attributes['image'], ['http://', 'https://']) ) {
            return $this->attributes['image'];
        }
        return Storage::disk('admin')->url($this->attributes['image']);
    }

    public function getGroupedPropertiesAttribute()
    {
        return $this->properties
            // 按照属性名聚合, 返回的集合的 key 是属性名, value 是包含该属性名的所有属性集合
            ->groupBy('name')
            ->map(function ($properties){
                // 使用 map 方法将属性集合变为属性值集合
                return $properties->pluck('value')->all();
            });
    }

    public function toESArray()
    {
        // 只取出需要的字段
        $arr = Arr::only($this->toArray(), ['id','type','title','category_id','long_title','on_sale','rating','sold_count','review_count','price']);

        // 如果商品有类目, 则 category 字段为类目名数组, 否则为空字符串
        $arr['category'] = $this->category ? explode(' - ', $this->category->full_name) : '';
        // 类目的 path 字段
        $arr['category_path'] = $this->category ? $this->category->path : '';
        // strip_tags 函数可以将 html 标签去除
        $arr['description'] = strip_tags($this->description);
        // 只取出需要的 SKU 字段
        $arr['skus'] = $this->skus->map(function (ProductSku $productSku) {
            return Arr::only($productSku->toArray(), ['title', 'description', 'price']);
        });
        // 只取出需要的商品属性字段
        $arr['properties'] = $this->properties->map(function (ProductProperty $productProperty) {
            // 对应的增加一个 search_value 字段, 用符号 : 将属性名和属性值拼接起来
            return array_merge(Arr::only($productProperty->toArray(), ['name', 'value']), [
                'search_value' => $productProperty->name.':'.$productProperty->value
            ]);
        });

        return $arr;
    }

    public function scopeByIds($query, $ids)
    {
        return $query->whereIn('id', $ids)->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $ids)));
    }

}
