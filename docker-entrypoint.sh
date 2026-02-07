#!/bin/bash
set -e

echo "🚀 Starting TrustChain Deployment..."

# DEBUG: Print all environment variables related to blockchain
echo "🔍 DEBUG: Environment Variables"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
env | grep -i "blockchain\|sepolia\|private" || echo "No blockchain vars found"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

# Wait for database to be ready (if using PostgreSQL/MySQL)
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
    echo "⏳ Waiting for database connection..."
    sleep 5
fi

# Check multiple possible variable names for blockchain config
# Skip placeholder/invalid values
RPC_URL=""
if [ -n "$BLOCKCHAIN_RPC_URL" ] && [[ "$BLOCKCHAIN_RPC_URL" != *"YOUR_INFURA"* ]]; then
    RPC_URL="$BLOCKCHAIN_RPC_URL"
elif [ -n "$SEPOLIA_RPC_URL" ] && [[ "$SEPOLIA_RPC_URL" != *"YOUR_INFURA"* ]]; then
    RPC_URL="$SEPOLIA_RPC_URL"
fi

PRIVATE_KEY="${BLOCKCHAIN_WALLET_PRIVATE_KEY:-${PRIVATE_KEY}}"
CONTRACT_ADDR="${DOCUMENT_REGISTRY_CONTRACT_ADDRESS}"

echo "🔍 Checking blockchain configuration..."
if [ -n "$RPC_URL" ]; then
    echo "   RPC URL: ${RPC_URL:0:50}..."
else
    echo "   RPC URL: NOT SET"
fi

if [ -n "$PRIVATE_KEY" ]; then
    echo "   Private Key: ${PRIVATE_KEY:0:10}..."
else
    echo "   Private Key: NOT SET"
fi

if [ -n "$CONTRACT_ADDR" ]; then
    echo "   Contract Address: $CONTRACT_ADDR"
else
    echo "   Contract Address: Not set"
fi

# Export variables for Hardhat
export BLOCKCHAIN_RPC_URL="$RPC_URL"
export BLOCKCHAIN_WALLET_PRIVATE_KEY="$PRIVATE_KEY"

# Deploy smart contract if not already deployed
if [ -n "$RPC_URL" ] && [ -n "$PRIVATE_KEY" ] && [ -z "$CONTRACT_ADDR" ]; then
    echo "📄 Attempting smart contract deployment..."
    
    # Compile contracts
    echo "⚙️  Compiling smart contracts..."
    npx hardhat compile || echo "⚠️  Compilation failed"
    
    # Deploy to network with timeout and error handling
    echo "🌐 Deploying to Sepolia..."
    
    if timeout 60 npx hardhat run scripts/deploy.js --network sepolia 2>&1 | tee /tmp/deploy.log; then
        # Extract contract address from deployment output
        CONTRACT_ADDRESS=$(grep -oP "DocumentRegistry deployed to: \K0x[a-fA-F0-9]{40}" /tmp/deploy.log | head -1)
        
        if [ -n "$CONTRACT_ADDRESS" ]; then
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "✅ CONTRACT DEPLOYED SUCCESSFULLY!"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "Contract Address: $CONTRACT_ADDRESS"
            echo ""
            echo "⚠️  Add this to Railway Variables:"
            echo "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=$CONTRACT_ADDRESS"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo ""
            
            # Save to a file for reference
            echo "$CONTRACT_ADDRESS" > /app/storage/.contract_address
        else
            echo "⚠️  Deployment completed but could not extract contract address"
            echo "Check deployment logs above"
        fi
    else
        echo "❌ Deployment failed or timed out"
        echo "Continuing without blockchain contract..."
        echo "You can deploy manually with: npm run deploy:sepolia"
    fi
else
    if [ -n "$CONTRACT_ADDR" ]; then
        echo "✅ Using existing contract: $CONTRACT_ADDR"
    else
        echo "⚠️  Blockchain not configured - skipping deployment"
        [ -z "$RPC_URL" ] && echo "   Add SEPOLIA_RPC_URL or BLOCKCHAIN_RPC_URL to Railway"
        [ -z "$PRIVATE_KEY" ] && echo "   Add PRIVATE_KEY or BLOCKCHAIN_WALLET_PRIVATE_KEY to Railway"
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
