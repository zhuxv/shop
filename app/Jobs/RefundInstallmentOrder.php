<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\Installment;
use App\Models\InstallmentItem;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 如果商品订单支付方式不是分期付款, 订单未支付, 订单退款状态不是退款中, 则不执行下面的逻辑
        if ( $this->order->payment_method !== 'installment' || !$this->order->paid_at || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING ) {
            return;
        }
        // 找不到对应的分期付款, 原则上不可能出现, 这里的判断只是增加代码的健壮性
        if ( !$installment = Installment::query()->where('order_id', $this->order->id)->first() ) {
            return;
        }
        // 遍历分期付款的所有还款计划
        foreach ( $installment->items as $item ) {
            // 如果还款计划未支付, 或者退款状态为退款成功或退款中, 则跳过
            if ( !$item->paid_at || in_array($item->refund_status, [
                InstallmentItem::REFUND_STATUS_PROCESSING,
                InstallmentItem::REFUND_STATUS_SUCCESS
                ]) ) {
                continue;
            }
            // 调用具体逻辑
            try{
                $this->refundInstallmentItem($item);
            } catch (\Exception $exception) {
                \Log::warning('分期退款失败: '.$exception->getMessage(), [
                    'installment_item_id' => $item->id
                ]);
                // 假如某个还款计划退款报错了, 则暂时跳过, 继续处理下一个还款计划的退款
                continue;
            }
        }
        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        // todo
        // 退款单号使用商品订单的退款号与当前的还款计划的序号拼接而成
        $refundNo = $this->order->refund_no.'_'.$item->sequence;
        // 根据还款计划的支付方式执行对应的退款逻辑
        switch ($item->payment_method) {
            case 'wechat':
                // todo 因为微信支付目前没有做,所以先不填写
                break;
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no' => $item->payment_no, // 使用支付宝交易号来退款
                    'refund_amount' => $item->base, // 退款金额, 单位元, 只退回本金
                    'out_request_no' => $refundNo, // 退款订单号
                ]);
                // 根据支付宝文档,如果返回值里面有 sub_code 字段说明退款失败
                if ( $ret->sub_code ) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED
                    ]);
                } else {
                    // 将订单的退款状态标记为退款成功并保存退款订单号
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS
                    ]);
                }
                break;
            default:
                // 原则上不可能出现, 这个只是为了代码的健壮性
                throw new InternalException('未知订单支付方式: '.$item->payment_method);
                break;
        }
    }

}
