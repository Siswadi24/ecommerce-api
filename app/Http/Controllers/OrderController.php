<?php

namespace App\Http\Controllers;

use App\ResponseFormatter;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $query = auth()->user()->orders()->with([
            'seller',
            'address',
            'items',
            'lastStatus',
        ]);

        if (request()->last_status) {
            $query->whereHas('lastStatus', function ($subQuery) {
                $subQuery->where('status', request()->last_status);
            });

            // dd($query->toSql());
        }

        if (request()->search) {
            $query->whereHas('seller', function ($subQuery) {
                $subQuery->where('store_name', 'LIKE', '%' . request()->search . '%');
            })->orWhere('invoice_number', 'LIKE', '%' . request()->search . '%');

            //---- Cara Sederhana -----
            $productIds = \App\Models\Product\Products::where('name', 'LIKE', '%' . request()->search . '%')->pluck('id');
            $query->orWhereHas('items', function ($subQuery) use ($productIds) {
                $subQuery->whereIn('product_id', $productIds);


            //---- Cara Cepat(Mengggunakan HashManyThrough) -----
            // $query->orWhereHas('products', function ($subQuery) use ($productIds) {
            //     $subQuery->whereIn('id', $productIds);
            });
        }

        $orders = $query->paginate(request()->per_page ?? 10);

        return ResponseFormatter::success($orders->through(function ($order) {
            return $order->getApiResponseAttribute();
            // return $order->lastStatus->status;
        }));
    }
}
