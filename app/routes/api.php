<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\AccountController;

Route::post('/transactions', [TransactionController::class, 'store']);
Route::get('/accounts/{id}/balance', [AccountController::class, 'balance']);
