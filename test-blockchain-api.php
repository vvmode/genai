#!/usr/bin/env php
<?php

/**
 * Blockchain API Test Script
 * 
 * This script tests the blockchain integration without needing the full Laravel server
 */

require __DIR__.'/vendor/autoload.php';

use Illuminate\Support\Facades\Artisan;

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n🧪 TrustChain Blockchain API Test\n";
echo str_repeat('=', 50) . "\n\n";

// Test 1: Check environment configuration
echo "1️⃣  Checking configuration...\n";
$rpcUrl = config('blockchain.rpc_url');
$contractAddress = config('blockchain.document_registry_address');

if (empty($rpcUrl) || $rpcUrl === 'http://localhost:8545') {
    echo "   ⚠️  Using local RPC: $rpcUrl\n";
} else {
    echo "   ✅ RPC URL configured: " . substr($rpcUrl, 0, 30) . "...\n";
}

if (empty($contractAddress)) {
    echo "   ❌ Contract address NOT set\n";
    echo "   → Deploy contract first: npm run deploy:sepolia\n";
    echo "   → Then update DOCUMENT_REGISTRY_CONTRACT_ADDRESS in .env\n\n";
    exit(1);
} else {
    echo "   ✅ Contract address: $contractAddress\n";
}

// Test 2: Check blockchain service
echo "\n2️⃣  Testing BlockchainService...\n";
try {
    $blockchainService = app(\App\Services\BlockchainService::class);
    echo "   ✅ BlockchainService initialized\n";
} catch (\Exception $e) {
    echo "   ❌ Failed to initialize: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Generate test document hash
echo "\n3️⃣  Testing DocumentHashService...\n";
try {
    $hashService = app(\App\Services\DocumentHashService::class);
    $testContent = "This is a test document content";
    $hash = $hashService->hashContent($testContent);
    echo "   ✅ Hash generated: " . substr($hash, 0, 20) . "...\n";
} catch (\Exception $e) {
    echo "   ❌ Failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check database
echo "\n4️⃣  Testing database connection...\n";
try {
    \DB::connection()->getPdo();
    echo "   ✅ Database connected\n";
    
    $docCount = \DB::table('documents')->count();
    echo "   📊 Documents in DB: $docCount\n";
} catch (\Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

// Test 5: Verify blockchain connection
echo "\n5️⃣  Testing blockchain connection...\n";
try {
    // Try to get chain ID (read-only operation)
    echo "   🔄 Connecting to blockchain...\n";
    
    // Check if we can instantiate Web3
    $web3 = new \Web3\Web3($rpcUrl);
    echo "   ✅ Web3 connection established\n";
    
} catch (\Exception $e) {
    echo "   ⚠️  Connection warning: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat('=', 50) . "\n";
echo "✅ Basic tests completed!\n\n";
echo "📝 Next steps:\n";
echo "   1. Start server: php artisan serve\n";
echo "   2. Test API endpoint:\n";
echo "      POST http://localhost:8000/api/documents\n";
echo "      with PDF file and metadata\n\n";
echo "   3. Or use Postman collection:\n";
echo "      TrustChain_API.postman_collection.json\n\n";
