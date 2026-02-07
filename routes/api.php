<?php

use App\Http\Controllers\Api\BlockchainApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('blockchain')->group(function () {
    Route::get('/balance', [BlockchainApiController::class, 'getBalance']);
    Route::get('/transactions', [BlockchainApiController::class, 'getTransactions']);
    Route::get('/token-transfers', [BlockchainApiController::class, 'getTokenTransfers']);
    Route::get('/gas-price', [BlockchainApiController::class, 'getGasPrice']);
    Route::get('/eth-price', [BlockchainApiController::class, 'getEthPrice']);
    Route::get('/eth-supply', [BlockchainApiController::class, 'getEthSupply']);
    Route::get('/tx/{txHash}', [BlockchainApiController::class, 'getTransaction']);
    Route::get('/block/{blockNumber}', [BlockchainApiController::class, 'getBlock']);
});
