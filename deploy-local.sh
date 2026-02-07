#!/bin/bash

echo "🚀 Local Blockchain Deployment"
echo "=============================="
echo ""

# Start local node in background
echo "1️⃣  Starting local Hardhat node..."
npx hardhat node > /tmp/hardhat-node.log 2>&1 &
NODE_PID=$!
echo "   Node PID: $NODE_PID"
sleep 3

# Deploy contract
echo ""
echo "2️⃣  Deploying DocumentRegistry contract..."
npx hardhat run scripts/deploy.js --network localhost

# Check if deployment succeeded
if [ -f "deployments/localhost.json" ]; then
    echo ""
    echo "3️⃣  Reading contract address..."
    CONTRACT_ADDRESS=$(cat deployments/localhost.json | grep -o '"address": "[^"]*"' | head -1 | cut -d'"' -f4)
    
    echo ""
    echo "✅ Deployment successful!"
    echo ""
    echo "📝 Add to your .env file:"
    echo "BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545"
    echo "BLOCKCHAIN_WALLET_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80"
    echo "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=$CONTRACT_ADDRESS"
    echo ""
    echo "⚠️  Node is running in background (PID: $NODE_PID)"
    echo "   To stop: kill $NODE_PID"
else
    echo ""
    echo "❌ Deployment failed"
    kill $NODE_PID
    exit 1
fi
