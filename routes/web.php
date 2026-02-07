<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => config('app.name'),
        'version' => '1.0.0',
        'description' => 'Ethereum Blockchain API powered by Etherscan',
        'endpoints' => [
            'GET /api/blockchain/balance?address={address}' => 'Get ETH balance',
            'GET /api/blockchain/transactions?address={address}&page={page}&limit={limit}' => 'Get transactions',
            'GET /api/blockchain/token-transfers?address={address}' => 'Get ERC-20 token transfers',
            'GET /api/blockchain/gas-price' => 'Get current gas prices',
            'GET /api/blockchain/eth-price' => 'Get current ETH/USD price',
            'GET /api/blockchain/eth-supply' => 'Get total ETH supply',
            'GET /api/blockchain/tx/{hash}' => 'Get transaction by hash',
            'GET /api/blockchain/block/{number}' => 'Get block by number',
        ],
    ]);
});
