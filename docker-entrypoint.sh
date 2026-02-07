#!/bin/bash
set -e

echo "🚀 Starting TrustChain Deployment..."

# Wait for database to be ready (if using PostgreSQL/MySQL)
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "⏳ Waiting for database connection..."
    sleep 5
fi

# Check multiple possible variable names for blockchain config
RPC_URL="${BLOCKCHAIN_RPC_URL:-${SEPOLIA_RPC_URL}}"
PRIVATE_KEY="${BLOCKCHAIN_WALLET_PRIVATE_KEY:-${PRIVATE_KEY}}"
CONTRACT_ADDR="${DOCUMENT_REGISTRY_CONTRACT_ADDRESS}"

echo "🔍 Checking blockchain configuration..."
echo "   RPC URL: ${RPC_URL:0:40}..."
echo "   Private Key: ${PRIVATE_KEY:0:10}..."
echo "   Contract Address: ${CONTRACT_ADDR:-Not set}"

# Deploy smart contract if not already deployed
if [ -n "$RPC_URL" ] && [ -n "$PRIVATE_KEY" ] && [ -z "$CONTRACT_ADDR" ]; then
    echo "📄 Smart contract not deployed yet. Deploying now..."
    
    # Compile contracts
    echo "⚙️  Compiling smart contracts..."
    npx hardhat compile
    
    # Deploy to network
    echo "🌐 Deploying to Sepolia..."
    DEPLOYMENT_OUTPUT=$(npx hardhat run scripts/deploy.js --network sepolia 2>&1)
    echo "$DEPLOYMENT_OUTPUT"
    
    # Extract contract address from deployment output
    CONTRACT_ADDRESS=$(echo "$DEPLOYMENT_OUTPUT" | grep -oP "DocumentRegistry deployed to: \K0x[a-fA-F0-9]{40}" | head -1)
    
    if [ -n "$CONTRACT_ADDRESS" ]; then
        echo "✅ Contract deployed to: $CONTRACT_ADDRESS"
        echo ""
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "⚠️  IMPORTANT: Add this to Railway Variables:"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=$CONTRACT_ADDRESS"
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo ""
        
        # Save to a file for reference
        echo "$CONTRACT_ADDRESS" > /app/storage/.contract_address
    else
        echo "⚠️  Could not extract contract address from deployment"
        echo "Check the deployment output above for errors"
    fi
else
    if [ -n "$CONTRACT_ADDR" ]; then
        echo "✅ Using existing contract: $CONTRACT_ADDR"
    else
        echo "⚠️  Blockchain not fully configured"
        [ -z "$RPC_URL" ] && echo "   Missing: RPC_URL"
        [ -z "$PRIVATE_KEY" ] && echo "   Missing: PRIVATE_KEY"
    fi
fi

# Cache config/routes at runtime when env vars are available
echo "📦 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (skip if database not available)
echo "🗄️  Running migrations..."
php artisan migrate --force || echo "⚠️  Migration failed, continuing..."

echo "🎉 Startup complete! Starting server..."

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
