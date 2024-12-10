<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $fillable = [
        'province_id',
        'external_id',
        'name'
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'province' => $this->province->only(['uuid', 'name']),
            'external_id' => $this->external_id,
            'name' => $this->name,
        ];
    }
}
