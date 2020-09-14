<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CouponCodesController extends Controller
{
    public function show($code, Request $request)
    {
        $record = CouponCode::where('code', $code)->first();
        // 判断优惠券是否存在和启用
        if ( !$record ) {
            abort(404);
        }

        $record->checkAvailable($request->user());

        return $record;
    }
}
