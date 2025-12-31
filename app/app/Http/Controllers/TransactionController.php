<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Jobs\ProcessTransactionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TransactionController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'source_account_id'      => 'required|exists:accounts,id',
            'destination_account_id' => 'nullable|exists:accounts,id',
            'amount'                 => 'required|numeric|min:0.01',
            'type'                   => 'required|in:credit,debit,transfer',
        ]);

        $transaction = Transaction::create([
            'idempotency_key'        => (string) Str::uuid(),
            'source_account_id'      => $data['source_account_id'],
            'destination_account_id' => $data['destination_account_id'] ?? null,
            'amount'                 => $data['amount'],
            'type'                   => $data['type'],
            'status'                 => 'pending',
        ]);

        ProcessTransactionJob::dispatch($transaction->id);

        return response()->json([
            'transaction_id' => $transaction->id,
            'status' => 'PENDING'
        ], Response::HTTP_ACCEPTED);
    }
}
