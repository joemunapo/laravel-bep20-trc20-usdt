<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UsdtPayment extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'address',
        'tx_id',
        'status',
        'expires_at',
        'funds_moved',
        'network' // toimplement
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tronAddress()
    {
        return $this->hasOne(TronAddress::class, 'payment_id');
    }

    public function bepAddress()
    {
        return $this->belongsTo(BepAddress::class, 'address', 'address');
    }
}
