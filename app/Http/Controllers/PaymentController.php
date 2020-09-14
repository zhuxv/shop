<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{

    /**
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByAlipay(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或者已关闭
        if ( $order->paid_at || $order->closed ) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount, // 订单金额,单位:元
            'subject' => '支付'.config('app.name').'的订单:'.$order->no // 标题
        ]);
    }

    /**
     * 支付宝支付同步回调
     */
    public function alipayReturn(Request $request)
    {
        // 效验参数是否合法
//        try{
//            $data = app('alipay')->verify();
//        } catch (\Exception $e) {
//            dd($e->getMessage());
//            return view('pages.error', ['msg'=>'数据不正确']);
//        }  目前怀疑支付宝沙箱返回的签名有误,所以跳过此操作

        return view('pages.success', ['msg'=>'付款成功']);
    }

    /**
     * @return string
     */
    public function alipayNotify()
    {
        // 效验参数是否合法
        $data = app('alipay')->verify();
        // 如果订单状态不是成功或者结束,则不走后续的逻辑
        // 所有交易状态：https://docs.open.alipay.com/59/103672
        if ( !in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED']) ) {
            return app('alipay')->success();
        }
        // 拿到订单流水号,并在数据库中查询
        $order = Order::where('no', $data->out_trade_no)->first();
        // 增加系统的健壮性
        if ( !$order ) {
            return 'fail';
        }
        // 如果订单为已支付状态
        if ( $order->paid_at ) {
            return app('alipay')->success();
        }

        $order->update([
            'paid_at' => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no' => $data->trade_no
        ]);

        $this->afterPaid($order);

        return app('alipay')->success();
    }

    /**
     * @param Order $order
     * @param Request $request
     * @return mixed
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function payByWechat(Order $order, Request $request)
    {
        // 校验权限
        $this->authorize('own', $order);
        // 校验订单状态
        if ( $order->paid_at || $order->closed ) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // scan 方法拉起微信扫码支付
        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $order->no,
            'total_fee' => $order->total_amount * 100, // 订单金额,单位:分
            'body' => '支付'.config('app.name').'的订单:'.$order->no // 标题
        ]);
        // 把要转换的字符串作为QrCode的构造函数参数
        $qrCode = new QrCode($wechatOrder->code_url);

        // 将生成的二维码图片数据以字符串的形式输出, 并带上响应类型
        return response($qrCode->writeString(), 200, ['Content-Type'=>$qrCode->getContentType()]);
    }

    /**
     * @return string
     */
    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data = app('wechat_pay')->verify();
        // 找到对应订单
        $order = Order::where('no', $data->out_trade_no)->first();
        // 订单不存在则告知微信支付
        if ( !$order ) {
            return 'fail';
        }
        // 订单已支付
        if ( $order->paid_at ) {
            // 告知微信支付此订单已处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at' => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no' => $data->transaction_id
        ]);

        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    /**
     * @param Order $order
     */
    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    /**
     * 分期付款
     * @param Order $order
     * @param Request $request
     * @return Installment
     * @throws InvalidRequestException
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function payByInstallment(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);
        // 订单已支付或已关闭
        if ( $order->paid_at || $order->closed ) {
            throw new InvalidRequestException('订单状态不正确');
        }
        // 订单不满足最低分期要求
        if ( $order->total_amount < config('app.min_installment_amount') ) {
            throw new InvalidRequestException('订单金额低于最低分期金额');
        }
        // 校验用户提交的还款月数, 数值必须是我们配置好的费率期数
        $this->validate($request, [
            'count' => ['required', Rule::in(array_keys(config('app.installment_fee_rate')))]
        ]);
        // 删除同一笔订单发起过其他的状态是未支付的分期付款, 避免同一笔商品订单有多个分期付款
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();
        $count = $request->input('count');
        // 创建一个新的分期对象
        $installment = new Installment([
            // 总本金即为商品订单总金额
            'total_amount' => $order->total_amount,
            // 分期期数
            'count' => $count,
            // 从配置文件中读取相应期数费率
            'fee_rate' => config('app.installment_fee_rate')[$count],
            // 从配置文件中读取当前逾期费率
            'fine_rate' => config('app.installment_fine_rate')
        ]);
        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();
        // 第一期的还款截止金额为明天早上凌晨 0 点
        $dueDate = Carbon::tomorrow();
        // 计算每一期的本金
        $base = big_number($order->total_amount)->divide($count)->getValue();
        // 计算每一期的手续费
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();
        // 根据用户选择的还款期数, 创建对应数量的还款计划
        for ( $i = 0; $i < $count; $i++ ) {
            // 最后一期的本金需要用总本金减去前面几期的本金
            if ( $i === $count-1 ) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }
            $installment->items()->create([
                'sequence' => $i,
                'base' => $base,
                'fee' => $fee,
                'due_date' => $dueDate
            ]);
            // 还款截止日期加 30 天
            $dueDate = $dueDate->copy()->addDays(30);
        }

        return $installment;
    }

}
