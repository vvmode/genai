#!/bin/bash
set -e

echo "🚀 Starting TrustChain Deployment..."

# Wait for database to be ready (if using PostgreSQL/MySQL)
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "⏳ Waiting for database connection..."
    sleep 5
fi

# Deploy smart contract if not already deployed
if [ -n "$BLOCKCHAIN_RPC_URL" ] && [ -n "$BLOCKCHAIN_WALLET_PRIVATE_KEY" ] && [ -z "$DOCUMENT_REGISTRY_CONTRACT_ADDRESS" ]; then
    echo "📄 Smart contract not deployed yet. Deploying..."
    
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
        echo "⚠️  Add this to Railway Variables:"
        echo "   DOCUMENT_REGISTRY_CONTRACT_ADDRESS=$CONTRACT_ADDRESS"
        
        # Save to a file for reference
        echo "$CONTRACT_ADDRESS" > /app/storage/.contract_address
    else
        echo "⚠️  Could not extract contract address from deployment"
    fi
else
    if [ -n "$DOCUMENT_REGISTRY_CONTRACT_ADDRESS" ]; then
        echo "✅ Using existing contract: $DOCUMENT_REGISTRY_CONTRACT_ADDRESS"
    else
        echo "⚠️  Blockchain not configured. Set BLOCKCHAIN_RPC_URL and BLOCKCHAIN_WALLET_PRIVATE_KEY"
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
