<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_FAILED    = 'failed';

    protected $fillable = [
        'source_account_id',
        'destination_account_id',
        'amount',
        'type',
        'status',
        'idempotency_key',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}
