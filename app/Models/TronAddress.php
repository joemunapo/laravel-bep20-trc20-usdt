<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TronAddress extends Model
{
    protected $fillable = [
        'address',
        'private_key',
        'index',
        'user_id',
        'payment_id',
        'status',
        'expires_at',
    ];

    protected $hidden = [
        'private_key',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function usdtPayment()
    {
        return $this->belongsTo(UsdtPayment::class, 'payment_id');
    }
}
