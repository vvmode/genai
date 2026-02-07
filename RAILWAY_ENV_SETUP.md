# 🚀 Railway Environment Variables Setup

## Current Status
From health check: `GET /api/health/blockchain`
```json
{
  "status": "ok",
  "blockchain": {
    "rpc_configured": false,  ❌
    "contract_deployed": false, ❌
    "network": "sepolia"
  }
}
```

## Required Environment Variables for Railway

### 1. Go to Railway Dashboard
https://railway.app/project/YOUR_PROJECT_ID/settings

Navigate to: **Variables** tab

### 2. Add These Variables

```env
# Blockchain Network
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_CHAIN_ID=11155111
BLOCKCHAIN_EXPLORER_URL=https://sepolia.etherscan.io

# RPC URL (Get from Infura or Alchemy)
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_INFURA_PROJECT_ID

# Wallet Private Key (for signing transactions)
BLOCKCHAIN_WALLET_PRIVATE_KEY=0xYOUR_PRIVATE_KEY_HERE

# Contract Address (Deploy first, then add this)
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYOUR_CONTRACT_ADDRESS

# Optional: Etherscan API Key (for contract verification)
ETHERSCAN_API_KEY=YOUR_ETHERSCAN_API_KEY
ETHERSCAN_BASE_URL=https://api-sepolia.etherscan.io/api
```

### 3. Get RPC URL

**Option A: Infura (Recommended)**
1. Go to https://infura.io/
2. Sign up for free account
3. Create new project
4. Select "Web3 API"
5. Copy Sepolia endpoint: `https://sepolia.infura.io/v3/YOUR_PROJECT_ID`

**Option B: Alchemy**
1. Go to https://alchemy.com/
2. Create free account
3. Create new app (Ethereum → Sepolia)
4. Copy HTTPS endpoint

**Option C: Public RPC (Not recommended for production)**
```
https://rpc.sepolia.org
https://ethereum-sepolia.publicnode.com
```

### 4. Get Test ETH

1. Go to https://sepoliafaucet.com/
2. Or https://sepolia-faucet.pk910.de/
3. Enter your wallet address
4. Request test ETH (free)

### 5. Deploy Contract

**From your local machine:**

```bash
# Set environment variables locally
export BLOCKCHAIN_RPC_URL="https://sepolia.infura.io/v3/YOUR_PROJECT_ID"
export BLOCKCHAIN_WALLET_PRIVATE_KEY="0xYOUR_PRIVATE_KEY"

# Deploy to Sepolia
npm run deploy:sepolia
```

**Output will look like:**
```
✅ DocumentRegistry deployed to: 0x1234567890abcdef...
```

Copy the contract address!

### 6. Add Contract Address to Railway

Back in Railway Variables, add:
```
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x1234567890abcdef...
```

### 7. Redeploy on Railway

Railway will auto-redeploy when you save variables.

Or manually trigger:
- Go to **Deployments** tab
- Click **Redeploy**

### 8. Verify Setup

Wait for deployment to complete, then check:

```bash
curl https://web-production-ef55e.up.railway.app/api/health/blockchain
```

**Expected response:**
```json
{
  "status": "ok",
  "blockchain": {
    "rpc_configured": true,  ✅
    "contract_deployed": true, ✅
    "network": "sepolia",
    "service_ready": true  ✅
  }
}
```

## Security Notes

⚠️ **Never commit private keys to Git!**

✅ **Railway Variables are encrypted and secure**

✅ **Use a dedicated wallet for testing (not your main wallet)**

✅ **Sepolia test ETH has no real value**

## Troubleshooting

### "service_ready": false

Check Railway logs:
```
railway logs
```

Common issues:
- Invalid RPC URL
- Invalid private key format (must start with 0x)
- Contract not deployed
- No test ETH in wallet

### Can't get test ETH?

Try multiple faucets:
- https://sepoliafaucet.com/
- https://sepolia-faucet.pk910.de/
- https://www.alchemy.com/faucets/ethereum-sepolia

Or ask in Discord:
- Ethereum Discord
- Alchemy Discord

## Quick Reference

| Variable | Where to Get | Required |
|----------|-------------|----------|
| BLOCKCHAIN_RPC_URL | Infura/Alchemy | ✅ Yes |
| BLOCKCHAIN_WALLET_PRIVATE_KEY | Your wallet | ✅ Yes |
| DOCUMENT_REGISTRY_CONTRACT_ADDRESS | After deployment | ✅ Yes |
| ETHERSCAN_API_KEY | etherscan.io | ⚠️ Optional |

## Next Steps

1. ✅ Add RPC URL to Railway
2. ✅ Add wallet private key to Railway
3. ✅ Deploy contract locally
4. ✅ Add contract address to Railway
5. ✅ Verify with health check endpoint
6. 🚀 Start testing API!
