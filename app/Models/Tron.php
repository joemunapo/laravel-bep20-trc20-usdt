<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tron extends Model
{
    protected $fillable = [
        'mnemonic_phrase',
        'address',
        'private_key',
    ];

    protected $hidden = [
        'mnemonic_phrase',
        'private_key',
    ];
}
