<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variations extends Model
{
    use HasFactory;

    protected $fillable = [
        'products_id',
        'name',
        'values',
    ];

    public function products()
    {
        return $this->belongsTo(Products::class);
    }

    public function getApiResponseAttribute()
    {
        return [
            'name' => $this->name,
            'values' => json_decode($this->values),
        ];
    }

    public function setValuesAttribute($value)
    {
        $this->attributes['values'] = json_encode($value);
    }
}
