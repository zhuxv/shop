<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, CartService $service)
    {
        $cartItems = $service->get();
        $addresses = $request->user()->addresses()->orderBy('last_used_at', 'desc')->get();

        return view('cart.index', ['cartItems'=>$cartItems, 'addresses'=>$addresses]);
    }

    /**
     * @param AddCartRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function add(AddCartRequest $request, CartService $service)
    {
        $skuId = $request->input('sku_id');
        $amount = $request->input('amount');
        $service->add($skuId, $amount);

        return success();
    }

    /**
     * @param ProductSku $sku
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function remove(ProductSku $sku, CartService $service)
    {
        $service->remove($sku->id);

        return success();
    }

}
