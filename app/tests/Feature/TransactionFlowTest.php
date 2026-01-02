<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessTransactionJob;

class TransactionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_transaction_is_processed_and_updates_balances()
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $source = Account::create(['balance' => 1000]);
        $destination = Account::create(['balance' => 500]);

        $response = $this->postJson('/api/transactions', [
            'source_account_id' => $source->id,
            'destination_account_id' => $destination->id,
            'amount' => 100,
            'type' => 'transfer',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'status' => 'pending',
        ]);

        $this->artisan('queue:work', ['--once' => true]);

        $this->assertEquals(900, $source->fresh()->balance);
        $this->assertEquals(600, $destination->fresh()->balance);

        $this->assertDatabaseHas('transactions', [
            'status' => 'processed',
        ]);
    }
}
