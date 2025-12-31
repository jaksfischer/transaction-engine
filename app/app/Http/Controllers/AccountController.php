<?php

namespace App\Http\Controllers;

use App\Models\Account;

class AccountController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/accounts/{id}/balance",
     *     summary="Consulta saldo da conta",
     *     tags={"Accounts"},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Saldo da conta",
     *         @OA\JsonContent(
     *             @OA\Property(property="balance", type="number", example=1000.00)
     *         )
     *     ),
     *
     *     @OA\Response(response=404, description="Conta não encontrada")
     * )
     */

    public function balance($id)
    {
        $account = Account::findOrFail($id);

        return response()->json([
            'balance' => $account->balance,
        ]);
    }
}
