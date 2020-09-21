<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends Request
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address.province' => 'required',
            'address.city' => 'required',
            'address.district' => 'required',
            'address.address' => 'required',
            'address.zip' => 'required',
            'address.contact_name' => 'required',
            'address.contact_phone' => 'required',
            'sku_id' => [
                'required',
                function($attribute, $value, $fail) {
                    // 从 redis 中读取数据
                    $stock = Redis::get('seckill_sku_'.$value);
                    // 如果是 null 代表这个商品不是秒杀商品
                    if ( is_null($stock) ) {
                        return $fail('该商品不存在');
                    }
                    // 判断库存
                    if ( $stock < 1 ) {
                        return $fail('该商品库存不足');
                    }
                    // 大多数用户在上面的逻辑里就已经被拒绝了
                    // 因此下方的 SQL 查询不会对整体性能有太大影响
                    $sku = ProductSku::find($value);
                    if ( $sku->product->seckill->is_before_start ) {
                        return $fail('秒杀尚未开始');
                    }
                    if ( $sku->product->seckill->is_after_end ) {
                        return $fail('秒杀已经结束');
                    }
                    if ( !$user = \Auth::user() ) {
                        throw new AuthenticationException('请先登录');
                    }
//                    if ( !$user->email_verified_at ) {
//                        throw new AuthenticationException('请先验证邮箱');
//                    }
                    if ( $order = Order::query()
                        // 筛选出当前用户的订单
                        ->where('user_id', $this->user()->id)
                        ->whereHas('items', function ($query)use($value){
                            // 筛选出包含当前 SKU 的订单
                            $query->where('product_sku_id', $value);
                        })
                        ->where(function ($query){
                            // 已支付的订单
                            $query->whereNotNull('paid_at')
                                ->orWhere('closed', false);
                        })
                        ->first() ) {
                        if ( $order->paid_at ) {
                            return $fail('你已经抢购了该商品');
                        }

                        return $fail('你已经下单了该商品, 请到订单页面支付');
                    }
                }
            ]
        ];
    }
}
