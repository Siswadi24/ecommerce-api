<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'slug',
        'name',
        'icons',
        'description'
    ];

    public function childs()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }


    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Products::class);
    }

    public function getApiResponseAttribute()
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'icons' => asset($this->icons),
            'childs' => $this->childs->pluck('api_response_child'),
            'description' => $this->description
        ];
    }

    public function getApiResponseChildAttribute()
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description
        ];
    }

    public function getApiResponseWithParentAttribute()
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'description' => $this->description,
            'parent' => optional($this->parent)->api_response_child
        ];
    }
}
