<?php

namespace Tests\Feature;

use App\Jobs\ProcessTransactionJob;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_is_processed_and_updates_balance(): void
    {
        Queue::fake();

        $source = Account::create(['balance' => 1000]);
        $destination = Account::create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 200,
            'type' => 'transfer',
        ]);

        $response->assertStatus(202);

        $transaction = Transaction::first();

        $this->assertEquals('pending', $transaction->status);

        Queue::assertPushed(ProcessTransactionJob::class);

        // Executa o job manualmente (simula o worker)
        (new ProcessTransactionJob($transaction->id))->handle();

        $transaction->refresh();
        $source->refresh();
        $destination->refresh();

        $this->assertEquals('processed', $transaction->status);
        $this->assertEquals(800, $source->balance);
        $this->assertEquals(700, $destination->balance);
    }
}
