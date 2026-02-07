<?php

use App\Http\Controllers\Api\BlockchainApiController;
use App\Http\Controllers\Api\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Health Check Routes
require __DIR__.'/api-health.php';

// Single JSON endpoint for document processing
Route::post('/document', [App\Http\Controllers\Api\DocumentApiController::class, 'processDocument']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Blockchain Information Routes (Public)
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

// Document Management Routes
Route::prefix('documents')->group(function () {
    // Public verification endpoint (no authentication required)
    Route::post('/verify', [DocumentController::class, 'verify']);
    
    // Protected routes (require authentication)
    Route::middleware('auth:sanctum')->group(function () {
        // Register new document
        Route::post('/', [DocumentController::class, 'store']);
        
        // List all documents for authenticated user
        Route::get('/', [DocumentController::class, 'index']);
        
        // Get specific document details
        Route::get('/{uuid}', [DocumentController::class, 'show']);
        
        // Revoke a document
        Route::post('/{uuid}/revoke', [DocumentController::class, 'revoke']);
        
        // Check blockchain transaction status
        Route::get('/{uuid}/status', [DocumentController::class, 'checkTransactionStatus']);
    });
});
