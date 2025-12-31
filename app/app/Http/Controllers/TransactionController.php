<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Jobs\ProcessTransactionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * @OA\Info(
 *     title="Transaction Engine API",
 *     version="1.0.0",
 *     description="API para processamento assíncrono de transações financeiras"
 * )
 */

class TransactionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     summary="Cria uma nova transação",
     *     tags={"Transactions"},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"source_account_id","amount","type"},
     *             @OA\Property(property="source_account_id", type="integer", example=1),
     *             @OA\Property(property="destination_account_id", type="integer", example=2),
     *             @OA\Property(property="amount", type="number", format="float", example=100.50),
     *             @OA\Property(property="type", type="string", enum={"credit","debit","transfer"})
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=202,
     *         description="Transação criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="transaction_id", type="integer"),
     *             @OA\Property(property="status", type="string", example="PENDING")
     *         )
     *     ),
     *
     *     @OA\Response(response=422, description="Erro de validação")
     * )
     */

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
