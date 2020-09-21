<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\Redis;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( Order $order, $delay )
    {
        $this->order = $order;
        // 设置延迟时间, delay() 表示多少秒之后可以执行
        \Log::warning('QUEUE IMPLEMENT');
        $this->delay($delay);
    }

    /**
     * 定义这个任务类具体的逻辑
     * 当队列处理器从队列中取出任务时, 会调用handle()方法
     *
     * @return void
     */
    public function handle()
    {
        // 判断对应的订单是否被支付
        // 如果已经被支付则不需要关闭订单, 直接退出
        \Log::warning('QUEUE HANDLE');
        if ( $this->order->paid_at ) {
            \Log::warning('QUEUE RETURN');
            return;
        }
        \Log::warning('QUEUE 30 AFTER');
        // 通过事务执行sql
        \DB::transaction(function (){
            // 将订单的 closed 字段标记为true, 即为关闭订单
            $this->order->update(['closed'=>true]);
            // 循环遍历订单中的商品 SKU, 将订单中的数量加回到SKU库存中去
            foreach ( $this->order->items as $item ) {
                $item->productSku->addStock($item->amount);
                // 当前订单类型是秒杀订单, 并且对应商品是上架且尚未到截止时间
                if ( $item->order->type === Order::TYPE_SECKILL
                    && $item->product->on_sale
                    && !$item->product->seckill->is_after_end) {
                    // 将 Redis 中的库存 +1
                    Redis::incr('seckill_sku_'.$item->productSku->id);
                }
            }
            // 关闭订单时, 如果有使用优惠券则将该优惠券的用量减少
            if ($this->order->couponCode) {
                $this->order->couponCode->changeUsed(false);
            }
        });
    }

    /**
     * 任务未能处理
     *
     * @param  Exception  $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        // 给用户发送失败通知, 等等...
        \Log::warning('QUEUE FAILED:'. $exception->getMessage());
    }

}
