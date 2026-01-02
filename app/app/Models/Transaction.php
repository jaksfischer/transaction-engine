<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'source_account_id',
        'destination_account_id',
        'amount',
        'type',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
