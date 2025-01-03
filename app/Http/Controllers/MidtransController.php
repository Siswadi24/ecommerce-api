<?php

namespace App\Http\Controllers;

use App\Models\Order\Order;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function callback()
    {
        // Generate midtrans transaction
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('app.env') == 'production';

        $notification = new \Midtrans\Notification();
        $transaction = $notification->transaction_status;
        $orderId = $notification->order_id;

        $order = Order::where('uuid', $orderId)->first();

        if ($order) {
            if ($transaction == 'capture' || $transaction == 'settlement') {
                $order->status()->create([
                    'status' => 'paid',
                    'description' => 'Pembayaran berhasil, menunggu proses pengiriman',
                ]);

                $order->update([
                    'is_paid' => true,
                    'payment_expired_at' => null,
                ]);

                foreach ($order->items as $item) {
                    $item->product->decrement('stock', $item->qty);
                }
            } elseif ($transaction == 'pending') {
                $order->status()->create([
                    'status' => 'pending',
                    'description' => 'Pembayaran sedang dalam proses',
                ]);

                $order->update([
                    'is_paid' => false,
                    'payment_expired_at' => now()->addHour(),
                ]);
            } elseif ($transaction == 'deny') {
                $order->status()->create([
                    'status' => 'failed',
                    'description' => 'Pembayaran dibatalkan',
                ]);

                $order->update([
                    'is_paid' => false,
                    'payment_expired_at' => now()->addHour(),
                ]);
            } elseif ($transaction == 'expire') {
                $order->status()->create([
                    'status' => 'failed',
                    'description' => 'Pembayaran kadaluarsa',
                ]);

                $order->update([
                    'is_paid' => false,
                    'payment_expired_at' => now()->addHour(),
                ]);
            } elseif ($transaction == 'cancel') {
                $order->status()->create([
                    'status' => 'failed',
                    'description' => 'Pembayaran dicancel',
                ]);

                $order->update([
                    'is_paid' => false,
                    'payment_expired_at' => now()->addHour(),
                ]);
            }
        }
    }
}
