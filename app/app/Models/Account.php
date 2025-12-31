<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
    ];

    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'source_account_id');
    }

    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'destination_account_id');
    }

}
