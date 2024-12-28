<?php

namespace App\Http\Controllers;

use App\Models\Cart\Cart;
use App\ResponseFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Optional;

class CartController extends Controller
{
    private function getOrCreateCart()
    {
        $cart = Cart::with(['items', 'address'])->where('user_id', auth()->user()->id)->first();
        if (is_null($cart)) {
            $cart = Cart::create([
                'user_id' => auth()->user()->id,
                'address_id' => Optional(auth()->user()->addresses()->where('is_default', 1)->first())->id,
                'courier' => null,
                'courier_type' => null,
                'courier_estimation' => null,
                'courier_price' => 0,
                'voucher_id' => null,
                'voucher_value' => 0,
                'voucher_cashback' => 0,
                'service_fee' => 0,
                'total' => 0,
                'pay_with_coin' => 0,
                'payment_method' => null,
                'total_payment' => 0,
            ]);
            $cart->refresh();
        }

        //Calculate Voucher
        if ($cart->voucher != null) {
            $voucher = $cart->voucher;
            if ($voucher->voucher_type == 'discount') {
                $cart->voucher_value = $voucher->discount_cashback_type == 'percentage' ? $cart->items->sum('total') * $voucher->discount_cashback_value / 100 : $voucher->discount_cashback_value;
                if (!is_null($voucher->discount_cashback_max) && $cart->voucher_value > $voucher->discount_cashback_max) {
                    $cart->voucher_value = $voucher->discount_cashback_max;
                }
            } elseif ($voucher->voucher_type == 'cashback') {
                $cart->voucher_cashback = $voucher->discount_cashback_type == 'percentage' ? $cart->items->sum('total') * $voucher->discount_cashback_value / 100 : $voucher->discount_cashback_value;
                if (!is_null($voucher->discount_cashback_max) && $cart->voucher_cashback > $voucher->discount_cashback_max) {
                    $cart->voucher_cashback = $voucher->discount_cashback_max;
                }
            }
        }

        //Recalculate Total
        $cart->total = ($cart->items->sum('total')) + $cart->courier_price + $cart->service_fee - $cart->voucher_value;
        if ($cart->total < 0) {
            $cart->total = 0;
        }
        $cart->total_payment = $cart->total - $cart->pay_with_coin;
        $cart->save();

        return $cart;
    }

    public function getCard()
    {
        $cart = $this->getOrCreateCart();

        return ResponseFormatter::success([
            'cart' => $cart->api_response,
            'item' => $cart->items->pluck('api_response'),
        ]);
    }
    public function addToCart()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'product_id' => 'required|exists:products,uuid',
                'qty' => 'required|numeric|min:1',
                'note' => 'nullable|string',
                'variations' => 'nullable|array',
                'variations.*.label' => 'required|exists:variations,name',
                'variations.*.value' => 'required',
            ]
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $cart = $this->getOrCreateCart();
        $product = \App\Models\Product\Products::where('uuid', request()->product_id)->firstOrFail();

        if ($product->stock < request()->qty) {
            return ResponseFormatter::error(400, null, [
                'Stok tidak mencukupi!',
            ]);
        }

        if ($cart->items->isNotEmpty() && $cart->items->first()->product->seller_id != $product->seller_id) {
            return ResponseFormatter::error(400, null, [
                'Keranjang hanya boleh di isi produk dari satu penjual yang sama!',
            ]);
        }

        $cart->items()->create([
            'product_id' => $product->id,
            'variations' => request()->variations,
            'qty' => request()->qty,
            'note' => request()->note,
        ]);

        return $this->getCard();
    }

    public function removeItemFromCart(string $uuid)
    {
        $cart = $this->getOrCreateCart();
        $item = $cart->items()->where('uuid', $uuid)->firstOrFail();
        $item->delete();

        return $this->getCard();
    }

    public function updateItemFromCart(string $uuid)
    {
        $validator = Validator::make(
            request()->all(),
            [
                'qty' => 'required|numeric|min:1',
                'note' => 'nullable|string',
                'variations' => 'nullable|array',
                'variations.*.label' => 'required|exists:variations,name',
                'variations.*.value' => 'required',
            ]
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $cart = $this->getOrCreateCart();
        $cartItem = $cart->items()->where('uuid', $uuid)->firstOrFail();
        $product = $cartItem->product;

        if ($product->stock < request()->qty) {
            return ResponseFormatter::error(400, null, [
                'Stok tidak mencukupi!',
            ]);
        }

        $cartItem->update([
            'variations' => request()->variations,
            'qty' => request()->qty,
            'note' => request()->note,
        ]);

        return $this->getCard();
    }

    public function getVoucher()
    {
        $vouchers = \App\Models\Voucher::public()->active()->get();

        return ResponseFormatter::success($vouchers->pluck('api_response'));
    }
    public function applyVoucher()
    {
        $validator = Validator::make(
            request()->all(),
            [
                'voucher_code' => 'required|exists:vouchers,code',
            ]
        );

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $voucher = \App\Models\Voucher::where('code', request()->voucher_code)->firstOrFail();
        if ($voucher->start_date > now() || $voucher->end_date < now()) {
            return ResponseFormatter::error(400, null, [
                'Voucher tidak bisa digunakan!'
            ]);
        }

        $cart = $this->getOrCreateCart();
        if (!is_null($voucher->seller_id) && $cart->items->count() > 0) {
            $sellerId = $cart->items->first()->product->seller_id;
            if ($sellerId != $voucher->seller_id) {
                return ResponseFormatter::error(400, null, [
                    'Voucher tidak bisa digunakan oleh penjual yang ada di keranjang belanja!'
                ]);
            }
        }

        $cart->voucher_id = $voucher->id;
        $cart->voucher_value = null;
        $cart->voucher_cashback = null;
        $cart->save();

        return $this->getCard();
    }
    public function removeVoucher()
    {
        $cart = $this->getOrCreateCart();
        $cart->voucher_id = null;
        $cart->voucher_value = null;
        $cart->voucher_cashback = null;
        $cart->save();

        return $this->getCard();
    }
}
