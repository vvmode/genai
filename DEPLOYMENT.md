# 🚀 Railway Deployment Guide

## Production URL
**Live API:** https://web-production-ef55e.up.railway.app

## Railway Configuration

### Build Settings
- **Builder:** Dockerfile
- **Dockerfile Path:** `Dockerfile`
- **Build Command:** Automatic (Docker build)

### Deploy Settings
- **Start Command:** `docker-entrypoint.sh`
- **Restart Policy:** ON_FAILURE
- **Max Retries:** 10

## Environment Variables (Railway Dashboard)

### Required Variables

```env
# Laravel
APP_NAME=TrustChain
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://web-production-ef55e.up.railway.app

# Database (Railway provides PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

# Blockchain Configuration
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_INFURA_KEY
BLOCKCHAIN_WALLET_PRIVATE_KEY=your_wallet_private_key_here
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=your_deployed_contract_address
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_CHAIN_ID=11155111
BLOCKCHAIN_EXPLORER_URL=https://sepolia.etherscan.io

# Etherscan (Optional - for contract verification)
ETHERSCAN_API_KEY=your_etherscan_api_key

# Storage
FILESYSTEM_DISK=local

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=info
```

## Deployment Steps

### 1. Initial Setup

1. **Connect GitHub Repository**
   - Go to Railway dashboard
   - Click "New Project" → "Deploy from GitHub repo"
   - Select `vvmode/genai` repository

2. **Configure Build**
   - Railway auto-detects `railway.json` and `Dockerfile`
   - Build starts automatically

### 2. Configure Environment Variables

Add all required environment variables in Railway dashboard:
- Settings → Variables
- Add each variable from the list above

### 3. Deploy Smart Contract (One-time)

```bash
# On your local machine
npm run deploy:sepolia

# Copy the contract address to Railway env vars
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x...
```

### 4. Run Migrations

After first deployment:
```bash
# Railway CLI or dashboard
railway run php artisan migrate --force
```

### 5. Verify Deployment

Test the API:
```bash
curl https://web-production-ef55e.up.railway.app/api/health

# Expected response
{
  "status": "ok",
  "blockchain": "connected",
  "contract": "deployed"
}
```

## Continuous Deployment

Every push to `main` branch triggers automatic deployment:

1. **Push to GitHub**
   ```bash
   git push origin main
   ```

2. **Railway Auto-deploys**
   - Detects changes
   - Builds Docker image
   - Deploys new version
   - Zero downtime deployment

## Monitoring

### Railway Dashboard
- **Logs:** View real-time application logs
- **Metrics:** CPU, Memory, Network usage
- **Deployments:** Track deployment history

### Health Check Endpoint
```bash
GET https://web-production-ef55e.up.railway.app/api/health
```

## Troubleshooting

### Build Fails

**Check:**
1. `Dockerfile` syntax
2. All required files are committed
3. Build logs in Railway dashboard

**Solution:**
```bash
# Test Docker build locally
docker build -t trustchain-api .
```

### Database Connection Issues

**Check:**
1. Railway PostgreSQL is provisioned
2. Database env vars are set correctly
3. Run migrations

**Solution:**
```bash
railway run php artisan migrate:fresh --force
```

### Blockchain Connection Issues

**Check:**
1. `BLOCKCHAIN_RPC_URL` is valid
2. `BLOCKCHAIN_WALLET_PRIVATE_KEY` has funds
3. Contract is deployed

**Test:**
```bash
php test-blockchain-api.php
```

## API Endpoints

### Base URL
```
https://web-production-ef55e.up.railway.app/api
```

### Available Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/documents` | Register new document |
| GET | `/documents` | List all documents |
| GET | `/documents/{id}` | Get document details |
| POST | `/documents/verify` | Verify document |
| POST | `/documents/{id}/revoke` | Revoke document |
| GET | `/documents/transaction/{hash}` | Check transaction status |

### Example: Register Document

```bash
curl -X POST https://web-production-ef55e.up.railway.app/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@certificate.pdf" \
  -F "issuer_name=University of XYZ" \
  -F "document_type=certificate" \
  -F "metadata[student_name]=John Doe"
```

## Security Considerations

### Production Checklist

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Strong `APP_KEY` generated
- [ ] Private keys stored securely
- [ ] HTTPS enabled (Railway provides this)
- [ ] CORS configured properly
- [ ] Rate limiting enabled
- [ ] Input validation on all endpoints

### Secure Private Key Storage

**Option 1: Railway Environment Variables**
```env
BLOCKCHAIN_WALLET_PRIVATE_KEY=0x...
```

**Option 2: Railway Secrets (Recommended)**
- Use Railway's secret storage
- Reference with `${SECRET_NAME}`

## Scaling

Railway automatically scales based on traffic:

- **Horizontal:** Add more instances
- **Vertical:** Increase CPU/Memory per instance

Configure in Railway dashboard → Settings → Resources

## Backup & Recovery

### Database Backups

Railway PostgreSQL includes automatic backups:
- Point-in-time recovery
- 7-day retention

### Contract Recovery

Smart contracts are immutable on blockchain:
- Contract address never changes
- Store address securely in multiple locations

## Cost Estimation

### Railway Pricing (as of 2026)

- **Starter Plan:** $5/month - 512MB RAM, 1 vCPU
- **Developer Plan:** $20/month - 8GB RAM, 4 vCPU
- **Team Plan:** Custom pricing

### Blockchain Costs

- **Sepolia Testnet:** FREE (test ETH from faucet)
- **Polygon Mainnet:** ~$0.01 per transaction
- **Ethereum Mainnet:** ~$5-50 per transaction

## Support

- **Railway Docs:** https://docs.railway.app
- **Railway Discord:** https://discord.gg/railway
- **Project Issues:** https://github.com/vvmode/genai/issues

## Next Steps

1. ✅ Configure environment variables
2. ✅ Deploy smart contract to Sepolia
3. ✅ Update contract address in Railway
4. ✅ Run migrations
5. ✅ Test API endpoints
6. 📝 Monitor logs and metrics
7. 🚀 Go live!
