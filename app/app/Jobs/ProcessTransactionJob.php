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

    private int $transactionId;

    public function __construct(int $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    public function handle(): void
    {
        DB::transaction(function () {

            $transaction = Transaction::lockForUpdate()->find($this->transactionId);

            if (!$transaction) {
                return;
            }

            if ($transaction->status !== 'PENDING') {
                return;
            }

            if (in_array($transaction->type, ['debit', 'transfer'])) {
                $sourceAccount = Account::lockForUpdate()
                    ->find($transaction->source_account_id);

                if (!$sourceAccount || $sourceAccount->balance < $transaction->amount) {
                    $transaction->update([
                        'status' => 'FAILED',
                    ]);
                    return;
                }

                $sourceAccount->decrement('balance', $transaction->amount);
            }

            if (in_array($transaction->type, ['credit', 'transfer'])) {
                $destinationAccount = Account::lockForUpdate()
                    ->find($transaction->destination_account_id);

                if (!$destinationAccount) {
                    $transaction->update([
                        'status' => 'FAILED',
                    ]);
                    return;
                }

                $destinationAccount->increment('balance', $transaction->amount);
            }

            $transaction->update([
                'status' => 'PROCESSED',
            ]);
        });
    }
}
