<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BepAddress extends Model
{
    protected $fillable = [
        'address',
        'address_index',
        'is_used',
    ];

    public function usdtPayment()
    {
        return $this->hasOne(UsdtPayment::class, 'address', 'address');
    }

    public static function getNextAvailableIndex()
    {
        $maxIndex = self::max('address_index');
        return $maxIndex ? $maxIndex + 1 : 1; // Start from index 1 (0 is for main wallet)
    }
}
