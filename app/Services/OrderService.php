<?php
namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

Class OrderService {

    public function store(User $user, UserAddress $userAddress, $remark, $items, CouponCode $coupon = null)
    {
        // 如果传入了优惠券, 则先检查是否可用
        if ( $coupon ) {
            // 但此时我们还没有算出订单总金额, 因此先不校验
            $coupon->checkAvailable($user);
        }
        // 开启一个数据库事务
        $order = DB::transaction(function ()use($user, $userAddress, $remark, $items, $coupon){
            // 更新此地址的最后使用时间
            $userAddress->update(['last_used_at'=>Carbon::now()]);
            // 创建一个订单
            $order = new Order([
                'address' => [ // 将地址信息放入订单中
                    'address' => $userAddress->full_address,
                    'zip' => $userAddress->zip,
                    'contact_name' => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone
                ],
                'remark' => $remark,
                'total_amount' => 0,
                'type' => Order::TYPE_NORMAL
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            // 遍历用户提交的SKU
            foreach ( $items as $data ) {
                $sku = ProductSku::find($data['sku_id']);
                // 创建一个 OrderItem 并直接与当前的订单关联
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ( $sku->decreaseStock($data['amount']) <= 0 ) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }
            if ( $coupon ) {
                // 总金额已经计算出来, 检查是否符合优惠券规则
                $coupon->checkAvailable($user, $totalAmount);
                // 把订单金额修改为优惠后的金额
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                // 讲优惠券与订单关联
                $order->couponCode()->associate($coupon);
                // 增加优惠券的用量, 需要判断返回值
                if ( $coupon->changeUsed() <= 0 ) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑换完');
                }
            }
            // 更新订单总金额
            $order->update(['total_amount'=>$totalAmount]);
            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        // 这里我们直接使用 dispatch 函数
        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;

    }

    public function crowdfunding(User $user, UserAddress $userAddress, ProductSku $productSku, $amount)
    {
        // 开启事务
        $order = \DB::transaction(function()use($amount, $productSku, $user, $userAddress){
            // 更新地址最后使用时间
            $userAddress->update(['last_used_at'=>Carbon::now()]);
            // 创建一个订单
            $order = new Order([
                'address' => [ // 将地址信息放入订单中
                    'address' => $userAddress->full_address,
                    'zip' => $userAddress->zip,
                    'contact_name' => $userAddress->contact_name,
                    'contact_phone' => $userAddress->contact_phone
                ],
                'remark' => '',
                'total_amount' => $productSku->price * $amount,
                'type' => Order::TYPE_CROWDFUNDING
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();
            // 创建一个新订单并与 SKU 关联
            $item = $order->items()->make([
                'amount' => $amount,
                'price' => $productSku->price
            ]);
            $item->product()->associate($productSku->product_id);
            $item->productSku()->associate($productSku);
            $item->save();
            // 扣减对应的 SKU 库存
            if ( $productSku->decreaseStock($amount) <= 0 ) {
                throw new InvalidRequestException('该商品库存不足');
            }

            return $order;
        });

        // 众筹结束时间减去当前剩余秒数
        $crowdfundingTtl = $productSku->product->crowdfunding->end_at->getTimestamp() - time();
        // 剩余秒数与默认订单关闭时间取较小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'), $crowdfundingTtl)));

        return $order;
    }

    /**
     * 订单退款
     * @param Order $order
     * @throws InternalException
     */
    public function refundOrder(Order $order)
    {
        // 判断该订单的支付方式
        switch ($order->payment_method) {
            case 'wechat':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_fee' => $order->total_amount * 100,
                    'refund_fee' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url' => ''
                ]);
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING
                ]);
                break;
            case 'alipay':
                $refundNo = Order::getAvailableRefundNo();
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo
                ]);
                if ( $ret->sub_code ) {
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra
                    ]);
                } else {
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS
                    ]);
                }
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(), //生成退款订单号
                    'refund_status' => Order::REFUND_STATUS_PROCESSING // 将退款状态改为退款中
                ]);
                // 出发异步退款任务
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$order->payment_method);
                break;
        }
    }

    public function seckill(User $user, array $addressData, ProductSku $productSku)
    {
        $order = \DB::transaction(function ()use($user, $addressData, $productSku){
            // 扣减对应 SKU 库存
            if ( $productSku->decreaseStock(1) <= 0 ) {
                throw new InvalidRequestException('该商品库存不足');
            }
            // 创建一个订单
            $order = new Order([
                'address' => [
                    'address' => $addressData['province'].$addressData['city'].$addressData['district'].$addressData['address'],
                    'zip' => $addressData['zip'],
                    'contact_name' => $addressData['contact_name'],
                    'contact_phone' => $addressData['contact_phone']
                ],
                'remark' => '',
                'total_amount' => $productSku->price,
                'type' => Order::TYPE_SECKILL
            ]);
            // 订单关联到用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();
            // 创建一个新的订单并与 SKU 关联
            $item = $order->items()->make([
                'amount' => 1, // 秒杀商品只能一份
                'price' => $productSku->price
            ]);
            $item->product()->associate($productSku->product_id);
            $item->productSku()->associate($productSku);
            $item->save();

            Redis::decr('seckill_sku_'.$productSku->id);

            return $order;
        });

        // 秒杀订单的自动关闭时间与普通订单不同
        dispatch(new CloseOrder($order, config('app.seckill_order_ttl')));

        return $order;
    }

}