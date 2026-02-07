<?php

return [
    'etherscan' => [
        'api_key' => env('ETHERSCAN_API_KEY', ''),
        'base_url' => env('ETHERSCAN_BASE_URL', 'https://api-sepolia.etherscan.io/api'),
    ],

    'network' => [
        'name' => env('BLOCKCHAIN_NETWORK', 'sepolia'),
        'chain_id' => (int) env('BLOCKCHAIN_CHAIN_ID', 11155111),
        'rpc_url' => env('BLOCKCHAIN_RPC_URL', ''),
        'explorer_url' => env('BLOCKCHAIN_EXPLORER_URL', 'https://sepolia.etherscan.io'),
    ],

    'wallet' => [
        'address' => env('BLOCKCHAIN_WALLET_ADDRESS', ''),
        'private_key' => env('BLOCKCHAIN_WALLET_PRIVATE_KEY', ''),
    ],

    'contracts' => [
        'issuer_registry' => [
            'address' => env('ISSUER_REGISTRY_CONTRACT_ADDRESS', ''),
            'abi_path' => storage_path('app/contracts/IssuerRegistry.json'),
        ],
        'document_registry' => [
            'address' => env('DOCUMENT_REGISTRY_CONTRACT_ADDRESS', ''),
            'abi_path' => storage_path('app/contracts/DocumentRegistry.json'),
        ],
    ],

    'gas' => [
        'limit' => (int) env('BLOCKCHAIN_GAS_LIMIT', 300000),
        'price_gwei' => env('BLOCKCHAIN_GAS_PRICE_GWEI', '20'),
    ],
];
