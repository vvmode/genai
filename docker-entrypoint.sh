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

# Deploy V1 smart contract if not already deployed
if [ -n "$RPC_URL" ] && [ -n "$PRIVATE_KEY" ] && [ -z "$CONTRACT_ADDR" ]; then
    echo "📄 Attempting V1 smart contract deployment..."
    
    # Compile contracts
    echo "⚙️  Compiling smart contracts..."
    npx hardhat compile || echo "⚠️  Compilation failed"
    
    # Deploy to network with timeout and error handling
    echo "🌐 Deploying V1 to Sepolia..."
    
    if timeout 60 npx hardhat run scripts/deploy.js --network sepolia 2>&1 | tee /tmp/deploy.log; then
        # Extract contract address from deployment output
        CONTRACT_ADDRESS=$(grep -oP "DocumentRegistry deployed to: \K0x[a-fA-F0-9]{40}" /tmp/deploy.log | head -1)
        
        if [ -n "$CONTRACT_ADDRESS" ]; then
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "✅ V1 CONTRACT DEPLOYED SUCCESSFULLY!"
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
            echo "⚠️  V1 Deployment completed but could not extract contract address"
            echo "Check deployment logs above"
        fi
    else
        echo "❌ V1 Deployment failed or timed out"
        echo "Continuing without blockchain contract..."
    fi
else
    if [ -n "$CONTRACT_ADDR" ]; then
        echo "✅ Using existing V1 contract: $CONTRACT_ADDR"
    else
        echo "⚠️  Blockchain not configured - skipping V1 deployment"
        [ -z "$RPC_URL" ] && echo "   Add SEPOLIA_RPC_URL or BLOCKCHAIN_RPC_URL to Railway"
        [ -z "$PRIVATE_KEY" ] && echo "   Add PRIVATE_KEY or BLOCKCHAIN_WALLET_PRIVATE_KEY to Railway"
    fi
fi

# Deploy V2 Contract (DocumentRegistryV2) - independent of V1
CONTRACT_V2_ADDR="${DOCUMENT_REGISTRY_V2_ADDRESS}"
if [ -n "$RPC_URL" ] && [ -n "$PRIVATE_KEY" ] && [ -z "$CONTRACT_V2_ADDR" ]; then
    echo ""
    echo "📄 Deploying V2 Contract (DocumentRegistryV2)..."
    
    # Ensure compilation is done
    if [ ! -d "artifacts/contracts/DocumentRegistryV2.sol" ]; then
        echo "⚙️  Compiling contracts..."
        npx hardhat compile || echo "⚠️  Compilation failed"
    fi
    
    if timeout 90 npx hardhat run scripts/deploy-v2.js --network sepolia 2>&1 | tee /tmp/deploy-v2.log; then
        # Extract V2 contract address from deployment output
        CONTRACT_V2_ADDRESS=$(grep -oP "DocumentRegistryV2 deployed to: \K0x[a-fA-F0-9]{40}" /tmp/deploy-v2.log | head -1)
        
        if [ -n "$CONTRACT_V2_ADDRESS" ]; then
            echo ""
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "✅ V2 CONTRACT DEPLOYED SUCCESSFULLY!"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo "Contract Address: $CONTRACT_V2_ADDRESS"
            echo ""
            echo "⚠️  Add this to Railway Variables:"
            echo "DOCUMENT_REGISTRY_V2_ADDRESS=$CONTRACT_V2_ADDRESS"
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
            echo ""
            
            # Save to a file for reference
            echo "$CONTRACT_V2_ADDRESS" > /app/storage/.contract_v2_address
        else
            echo "⚠️  V2 Deployment completed but could not extract contract address"
            echo "Check deployment logs above"
        fi
    else
        echo "❌ V2 Deployment failed or timed out"
        echo "Continuing without V2 contract..."
        echo "You can deploy manually with: npx hardhat run scripts/deploy-v2.js --network sepolia"
    fi
else
    if [ -n "$CONTRACT_V2_ADDR" ]; then
        echo "✅ Using existing V2 contract: $CONTRACT_V2_ADDR"
    else
        echo "⚠️  V2 contract not configured - skipping V2 deployment"
        [ -z "$RPC_URL" ] && echo "   Add SEPOLIA_RPC_URL or BLOCKCHAIN_RPC_URL to Railway"
        [ -z "$PRIVATE_KEY" ] && echo "   Add PRIVATE_KEY or BLOCKCHAIN_WALLET_PRIVATE_KEY to Railway"
    fi
fi

# Ensure ABI files are available for Laravel
echo ""
echo "📄 Setting up contract ABI files..."

# Create contracts directory if it doesn't exist
mkdir -p storage/app/contracts

# Copy ABI files if they exist (from compilation)
if [ -f "artifacts/contracts/DocumentRegistry.sol/DocumentRegistry.json" ]; then
    cp artifacts/contracts/DocumentRegistry.sol/DocumentRegistry.json storage/app/contracts/
    echo "✅ Copied DocumentRegistry ABI"
fi

if [ -f "artifacts/contracts/DocumentRegistryV2.sol/DocumentRegistryV2.json" ]; then
    cp artifacts/contracts/DocumentRegistryV2.sol/DocumentRegistryV2.json storage/app/contracts/
    echo "✅ Copied DocumentRegistryV2 ABI"
fi

# If ABI files don't exist yet, compile contracts to generate them
if [ ! -f "storage/app/contracts/DocumentRegistryV2.json" ] && [ -n "$RPC_URL" ]; then
    echo "⚙️  Compiling contracts to generate ABI files..."
    npx hardhat compile || echo "⚠️  Compilation failed"
    
    # Try copying again after compilation
    if [ -f "artifacts/contracts/DocumentRegistryV2.sol/DocumentRegistryV2.json" ]; then
        cp artifacts/contracts/DocumentRegistryV2.sol/DocumentRegistryV2.json storage/app/contracts/
        echo "✅ Copied DocumentRegistryV2 ABI after compilation"
    fi
fi

# Generate application key if not set (Railway uses env vars, not .env file)
if [ -z "$APP_KEY" ]; then
    echo ""
    echo "🔑 Generating application key..."
    export APP_KEY="base64:$(openssl rand -base64 32)"
    echo "✅ Application key generated"
fi

# Cache config/routes at runtime when env vars are available
echo ""
echo "📦 Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (skip if database not available)
echo "🗄️  Running migrations..."
php artisan migrate --force || echo "⚠️  Migration failed, continuing..."

# Setup fraud detection organizations (if table exists and is empty)
echo ""
echo "👥 Setting up fraud detection organizations..."
php create-test-org.php 2>&1 || echo "⚠️  Organizations may already exist or table not ready"
echo ""

echo "🎉 Startup complete! Starting server..."

# Start the server
exec php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
