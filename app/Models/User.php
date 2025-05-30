<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Order\Order;
use App\Models\Product\Products;
use Bavix\Wallet\Interfaces\Wallet;
use Bavix\Wallet\Traits\HasWallet;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements Wallet
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;
    use HasWallet;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'phone',
        'store_name',
        'gender',
        'birth_date',
        'photo',
        'otp_register',
        'email_verified_at',
        'password',
        'social_media_provider',
        'social_media_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getApiResponseAttribute()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'photo_url' => $this->photo_url,
            'username' => $this->username,
            'phone' => $this->phone,
            'store_name' => $this->store_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
            'balance' => (float)$this->balance
        ];
    }

    public function getApiResponseAsSellerAttribute()
    {
        $productIds = $this->products()->pluck('id');

        return [
            'username' => $this->username,
            'store_name' => $this->store_name,
            'photo_url' => $this->photo_url,
            'products_count' => $this->products()->count(),
            'rating_count' => \App\Models\Product\Reviews::whereIn('products_id', $productIds)->count(),
            'join_date' => $this->created_at->diffForHumans(),
            'send_from' => optional($this->addresses()->where('is_default', 'true')->first())->getApiResponseAttribute()
        ];
    }

    public function getApiResponseAsBuyerAttribute()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'photo_url' => $this->photo_url,
            'username' => $this->username,
            'phone' => $this->phone,
        ];
    }

    public function getPhotoUrlAttribute()
    {
        if (!is_null($this->photo)) {
            return asset('storage/' . $this->photo);
        }

        return null;
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function products()
    {
        return $this->hasMany(Products::class, 'seller_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    public function orderAsSeller()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'seller_id');
    }
}
