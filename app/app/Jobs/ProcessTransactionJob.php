<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $transactionId
    ) {}

    public function handle()
    {
        DB::transaction(function () {
            $transaction = Transaction::lockForUpdate()->findOrFail($this->transactionId);

            if ($transaction->status !== 'pending') {
                return;
            }

            $source = Account::lockForUpdate()->find($transaction->source_account_id);
            $destination = Account::lockForUpdate()->find($transaction->destination_account_id);

            $source->balance -= $transaction->amount;
            $destination->balance += $transaction->amount;

            $source->save();
            $destination->save();

            $transaction->status = 'processed';
            $transaction->save();
        });
    }
}
