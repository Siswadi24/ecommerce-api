<?php

namespace App\Models\Order;

use App\Models\Product\Products;
use App\Models\Product\Reviews;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variations',
        'qty',
        'note',
        'price',
        'total',
        'weight',
    ];

    protected $casts = [
        'variations' => 'array',
        'price' => 'float',
        'total' => 'float',
        'weight' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }

    public function review()
    {
        return $this->hasOne(Reviews::class, 'order_item_id');
    }

    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'product' => $this->product->api_response_excerpt,
            'variations' => $this->variations,
            'qty' => $this->qty,
            'note' => $this->note,
            'price' => $this->price,
            'total' => $this->total,
            'weight' => $this->weight,
            'is_reviewed' => $this->review()->count() > 0 ? true : false,
        ];
    }

    //Ini digunakan untuk melakukan get data dari database orderItem
    public static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            $orderItem->price = $orderItem->product->price_sale ?? $orderItem->product->price;
            $orderItem->weight = $orderItem->product->weight;
            $orderItem->total = $orderItem->price * $orderItem->qty;
        });
    }
}
