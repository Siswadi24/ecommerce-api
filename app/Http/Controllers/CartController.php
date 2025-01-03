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

    public function updateAddress()
    {
        $validator = Validator::make(request()->all(), [
            'uuid' => 'required|exists:addresses,uuid',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $cart = $this->getOrCreateCart();
        $cart->address_id = auth()->user()->addresses()->where('uuid', request()->uuid)->firstOrFail()->id;
        $cart->save();

        return $this->getCard();
    }

    public function getShipping()
    {
        $cart = $this->getOrCreateCart();

        // Validasi courier: jne|pos
        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'courier' => 'required|in:jne,tiki',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        // Validasi item di keranjang belanja
        if ($cart->items->count() == 0) {
            return ResponseFormatter::error(400, null, [
                'Keranjang belanja kosong!'
            ]);
        }

        // Validasi bahwa seller sudah mengisi alamat dia
        $seller = $cart->items->first()->product->seller;
        $sellerAddress = $seller->addresses()->where('is_default', true)->first();
        if (is_null($sellerAddress)) {
            return ResponseFormatter::error(400, null, [
                'Alamat seller belum diisi'
            ]);
        }

        // Validasi address di cart
        if (is_null($cart->address)) {
            return ResponseFormatter::error(400, null, [
                'Alamat tujuan belum diisi'
            ]);
        }

        $weight = $cart->items->sum(function ($item) {
            return $item->qty * $item->product->weight;
        });

        $result = $this->getShippingOptions(
            $sellerAddress->city->external_id,
            $cart->address->city->external_id,
            $weight,
            request()->courier
        );

        return ResponseFormatter::success($result);
    }

    public function updateShippingFee()
    {
        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'courier' => 'required|in:jne,tiki',
            'service' => 'required'
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $cart = $this->getOrCreateCart();

        // Validasi item di keranjang belanja
        if ($cart->items->count() == 0) {
            return ResponseFormatter::error(400, null, [
                'Keranjang belanja kosong!'
            ]);
        }

        // Validasi bahwa seller sudah mengisi alamat dia
        $seller = $cart->items->first()->product->seller;
        $sellerAddress = $seller->addresses()->where('is_default', true)->first();
        if (is_null($sellerAddress)) {
            return ResponseFormatter::error(400, null, [
                'Alamat seller belum diisi'
            ]);
        }

        // Validasi address di cart
        if (is_null($cart->address)) {
            return ResponseFormatter::error(400, null, [
                'Alamat tujuan belum diisi'
            ]);
        }

        $weight = $cart->items->sum(function ($item) {
            return $item->qty * $item->product->weight;
        });

        $result = $this->getShippingOptions(
            $sellerAddress->city->external_id,
            $cart->address->city->external_id,
            $weight,
            request()->courier
        );

        $service = collect($result)->where('service', request()->service)->first();
        if (is_null($service)) {
            return ResponseFormatter::error(400, null, [
                'Service tidak ditemukan'
            ]);
        }

        $cart->courier = request()->courier;
        $cart->courier_type = request()->service;
        $cart->courier_estimation = $service['etd'];
        $cart->courier_price = $service['cost'];
        $cart->save();

        return $this->getCard();
    }

    private function getShippingOptions(string $origin, string $destination, float $weight, string $courier)
    {
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'key' => config('services.rajaongkir.key'),
        ])->asMultipart()->post(config('services.rajaongkir.base_url') . '/domestic-cost', [
            'origin' => $origin,
            'destination' => $destination,
            'weight' => $weight,
            'courier' => $courier,
        ]);


        $data = $response->object()->data;
        $result = collect($data)->map(function ($item) {
            return [
                'name' => $item->name,
                'service' => $item->service,
                'description' => $item->description,
                'cost' => $item->cost,
                'etd' => $item->etd,
            ];
        });

        return $result;
    }

    public function checkout()
    {
        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'payment_method' => 'required|in:qris,bni_va',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(400, $validator->errors());
        }

        $cart = $this->getOrCreateCart();
        if ($cart->items->count() == 0) {
            return ResponseFormatter::error(400, null, [
                'Keranja belanja Anda kosong!'
            ]);
        }

        //Validasi user jika telah memilih kurir
        if (is_null($cart->courier)) {
            return ResponseFormatter::error(400, null, [
                'Anda belum memilih kurir!'
            ]);
        }

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($cart) {
            // Create order
            $order = auth()->user()->orders()->create([
                'seller_id' => $cart->items->first()->product->seller_id,
                'address_id' => $cart->address_id,
                'courier' => $cart->courier,
                'courier_type' => $cart->courier_type,
                'courier_estimation' => $cart->courier_estimation,
                'courier_price' => $cart->courier_price,
                'voucher_id' => $cart->voucher_id,
                'voucher_value' => $cart->voucher_value,
                'voucher_cashback' => $cart->voucher_cashback,
                'service_fee' => $cart->service_fee,
                'total' => $cart->total,
                'pay_with_coin' => $cart->pay_with_coin,
                'payment_method' => request()->payment_method,
                'total_payment' => $cart->total_payment,
                'is_paid' => false,
            ]);

            // Create order item
            foreach ($cart->items as $item) {
                $order->items()->create([
                    'product_id' => $item->product_id,
                    'variations' => $item->variations,
                    'qty' => $item->qty,
                    'note' => $item->note,
                ]);
            }

            // Create order status
            $order->status()->create([
                'status' => 'pending_payment',
                'description' => 'Silahkan selesaikan pembayaran Anda'
            ]);

            // Potong saldo coin
            if ($order->pay_with_coin > 0) {
                $order->user->withdraw($order->pay_with_coin, [
                    'description' => 'Pembayaran pesanan ' . $order->invoice_number
                ]);
            }

            // Generate payment ke midtrans
            $order->refresh();
            $order->generatePayment();

            // Bersihkan cart & cart items
            $cart->items()->delete();
            $cart->delete();

            return $order;
        });

        return ResponseFormatter::success($order->api_response_detail);
    }
}
