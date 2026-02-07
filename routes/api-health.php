<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| Simple health check endpoints for monitoring the API and blockchain status
|
*/

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'service' => 'TrustChain API',
        'version' => '1.0.0'
    ]);
});

Route::get('/health/blockchain', function () {
    try {
        $rpcUrl = config('blockchain.network.rpc_url');
        $contractAddress = config('blockchain.contracts.document_registry.address');
        
        $status = [
            'status' => 'ok',
            'blockchain' => [
                'rpc_configured' => !empty($rpcUrl),
                'contract_deployed' => !empty($contractAddress),
                'network' => config('blockchain.network.name', 'unknown')
            ]
        ];

        // Check if blockchain service can be instantiated
        if (!empty($contractAddress)) {
            try {
                $service = app(\App\Services\BlockchainService::class);
                $status['blockchain']['service_ready'] = true;
            } catch (\Exception $e) {
                $status['blockchain']['service_ready'] = false;
                $status['blockchain']['error'] = $e->getMessage();
            }
        }

        return response()->json($status);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/health/database', function () {
    try {
        \DB::connection()->getPdo();
        $documentCount = \DB::table('documents')->count();
        
        return response()->json([
            'status' => 'ok',
            'database' => [
                'connected' => true,
                'documents_count' => $documentCount
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'database' => [
                'connected' => false,
                'error' => $e->getMessage()
            ]
        ], 500);
    }
});
