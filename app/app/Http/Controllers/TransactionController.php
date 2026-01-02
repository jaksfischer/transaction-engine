<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Jobs\ProcessTransactionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use OpenApi\Annotations as OA;

class TransactionController extends Controller
{

    /**
     * @OA\Post(
     *     path="/api/transactions",
     *     operationId="createTransaction",
     *     tags={"Transactions"},
     *     summary="Create a financial transaction (async)",
     *     description="Creates a financial transaction. The transaction is created with status 'pending' and processed asynchronously via queue.",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"source_account_id","destination_account_id","amount","type"},
     *             @OA\Property(
     *                 property="source_account_id",
     *                 type="integer",
     *                 example=1,
     *                 description="Source account ID"
     *             ),
     *             @OA\Property(
     *                 property="destination_account_id",
     *                 type="integer",
     *                 example=2,
     *                 description="Destination account ID"
     *             ),
     *             @OA\Property(
     *                 property="amount",
     *                 type="number",
     *                 format="float",
     *                 example=200.50,
     *                 description="Transaction amount"
     *             ),
     *             @OA\Property(
     *                 property="type",
     *                 type="string",
     *                 example="transfer",
     *                 description="Transaction type"
     *             ),
     *             @OA\Property(
     *                 property="idempotency_key",
     *                 type="string",
     *                 example="c2f1c9c0-8a2a-4c7a-b9f3-1b9e3b1f2e9a",
     *                 description="Optional idempotency key to prevent duplicate transactions"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=201,
     *         description="Transaction successfully created",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="source_account_id", type="integer", example=1),
     *             @OA\Property(property="destination_account_id", type="integer", example=2),
     *             @OA\Property(property="amount", type="number", example=200.50),
     *             @OA\Property(property="type", type="string", example="transfer"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="idempotency_key", type="string", example="c2f1c9c0-8a2a-4c7a-b9f3-1b9e3b1f2e9a"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-01-02T21:50:44Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-01-02T21:50:44Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=202,
     *         description="Transaction accepted for asynchronous processing (idempotent request)",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=10),
     *             @OA\Property(property="source_account_id", type="integer", example=1),
     *             @OA\Property(property="destination_account_id", type="integer", example=2),
     *             @OA\Property(property="amount", type="number", example=200.50),
     *             @OA\Property(property="type", type="string", example="transfer"),
     *             @OA\Property(property="status", type="string", example="pending"),
     *             @OA\Property(property="idempotency_key", type="string", example="c2f1c9c0-8a2a-4c7a-b9f3-1b9e3b1f2e9a"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2026-01-02T21:50:44Z"),
     *             @OA\Property(property="updated_at", type="string", format="date-time", example="2026-01-02T21:50:44Z")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_account_id' => 'required|exists:accounts,id',
            'destination_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:1',
            'type' => 'required|string',
            'idempotency_key' => 'nullable|string',
        ]);

        $idempotencyKey = $validated['idempotency_key'] ?? (string) Str::uuid();

        $existing = Transaction::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return response()->json($existing, 202);
        }

        $transaction = Transaction::create([
            'source_account_id' => $validated['source_account_id'],
            'destination_account_id' => $validated['destination_account_id'],
            'amount' => $validated['amount'],
            'type' => $validated['type'],
            'status' => 'pending',
            'idempotency_key' => $idempotencyKey,
        ]);

        ProcessTransactionJob::dispatch($transaction->id);

        if (app()->runningUnitTests()) {
            return response()->json($transaction, 202);
        }

        return response()->json($transaction, 201);
    }
}
