<?php

namespace App\Jobs;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public int $transactionId
    ) {}

    public function handle(): void
    {
        DB::transaction(function () {

            $transaction = Transaction::lockForUpdate()->findOrFail($this->transactionId);

            if ($transaction->status !== 'pending') {
                return;
            }

            $source = Account::lockForUpdate()->find($transaction->source_account_id);
            $destination = $transaction->destination_account_id
                ? Account::lockForUpdate()->find($transaction->destination_account_id)
                : null;

            match ($transaction->type) {
                'credit' => $this->credit($source, $transaction),
                'debit' => $this->debit($source, $transaction),
                'transfer' => $this->transfer($source, $destination, $transaction),
            };

            $transaction->update(['status' => 'processed']);
        });
    }

    protected function debit(Account $source, Transaction $transaction): void
    {
        if ($source->balance < $transaction->amount) {
            throw new \RuntimeException('Insufficient balance');
        }

        $source->decrement('balance', $transaction->amount);
    }

    protected function credit(Account $source, Transaction $transaction): void
    {
        $source->increment('balance', $transaction->amount);
    }

    protected function transfer(Account $source, ?Account $destination, Transaction $transaction): void
    {
        if (!$destination) {
            throw new \RuntimeException('Destination account required');
        }

        if ($source->balance < $transaction->amount) {
            throw new \RuntimeException('Insufficient balance');
        }

        $source->decrement('balance', $transaction->amount);
        $destination->increment('balance', $transaction->amount);
    }

    public function failed(Throwable $exception): void
    {
        Transaction::where('id', $this->transactionId)
            ->update(['status' => 'failed']);
    }
}
