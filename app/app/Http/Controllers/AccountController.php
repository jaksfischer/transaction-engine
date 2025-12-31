<?php

namespace App\Http\Controllers;

use App\Models\Account;

class AccountController extends Controller
{
    public function balance($id)
    {
        $account = Account::findOrFail($id);

        return response()->json([
            'balance' => $account->balance,
        ]);
    }
}
