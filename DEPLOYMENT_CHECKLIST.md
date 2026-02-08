# 🚀 Deployment Checklist for Hybrid Storage

## ✅ Pre-Deployment

- [ ] **Read Documentation**
  - Review [HYBRID_STORAGE_GUIDE.md](HYBRID_STORAGE_GUIDE.md)
  - Understand cost implications (~$2-5 per document)
  - Understand encryption key management (zero-knowledge)

- [ ] **Sign Up for Pinata**
  - Go to https://pinata.cloud
  - Choose plan: Free (1GB) or Paid ($20/month for 1TB)
  - Note: Production requires paid plan for uptime SLA

- [ ] **Generate Pinata API Keys**
  - Dashboard → API Keys → New Key
  - Permissions: pinFileToIPFS, unpin
  - Save API Key and API Secret

## 📋 Deployment Steps

### **1. Configure Blockchain**

```bash
# Ensure you have Sepolia testnet ETH
# Wallet: 0xE895AdB9cbf2cBFFBDA685C7F796F3FC4774960A
# Get testnet ETH: https://sepolia-faucet.pk910.de
```

### **2. Deploy V2 Smart Contract**

```bash
# Compile contract
npx hardhat compile

# Deploy to Sepolia
npx hardhat run scripts/deploy-v2.js --network sepolia

# Expected output:
# ✅ DocumentRegistryV2 deployed to: 0x...
# 🔗 Etherscan: https://sepolia.etherscan.io/address/0x...

# Save contract address from deployment-v2.json
```

### **3. Update Railway Environment Variables**

Add to Railway Variables:

```env
# Pinata IPFS
PINATA_API_KEY=your_actual_pinata_api_key
PINATA_SECRET_KEY=your_actual_pinata_secret_key

# V2 Contract
DOCUMENT_REGISTRY_V2_ADDRESS=0x... (from deployment-v2.json)

# Keep existing V1 contract
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x6028b310AEAb9cbf2cBFFBDA685C7F796F3FC4774960A

# API Configuration
DEFAULT_API_VERSION=2
ENABLE_V1_API=true
ENABLE_V2_API=true
```

### **4. Update BlockchainService**

The current implementation has mock methods. Update `app/Services/BlockchainService.php`:

```php
// Replace registerDocumentV2() mock with real implementation
// Replace getDocumentMetadata() mock with real implementation
```

**TODO:** Replace mock methods with actual Web3 contract calls after deployment.

### **5. Deploy to Railway**

```bash
# Commit changes
git add .
git commit -m "Add hybrid storage with IPFS + blockchain"
git push origin main

# Railway auto-deploys on push
# Monitor logs: railway logs
```

### **6. Test Endpoints**

**Test V2 Registration:**
```bash
curl -X POST https://your-app.up.railway.app/api/v2/document \
  -H "Content-Type: application/json" \
  -d @test-register.json

# Expected response:
# {
#   "success": true,
#   "data": {
#     "document_id": "...",
#     "encryption_key": "...",  ⚠️ SAVE THIS!
#     "ipfs": {"hash": "Qm..."},
#     "blockchain": {"transaction_hash": "0x..."}
#   }
# }
```

**Test V2 Verification (Metadata Only):**
```bash
curl -X POST https://your-app.up.railway.app/api/v2/document \
  -H "Content-Type: application/json" \
  -d '{
    "action": "verify",
    "document_id": "CERT-2026-001"
  }'

# Should return metadata from blockchain
```

**Test V2 Verification (With PDF):**
```bash
curl -X POST https://your-app.up.railway.app/api/v2/document \
  -H "Content-Type: application/json" \
  -d '{
    "action": "verify",
    "document_id": "CERT-2026-001",
    "encryption_key": "saved_key_from_registration"
  }'

# Should return metadata + decrypted PDF (base64)
```

**Test V1 Still Works:**
```bash
curl -X POST https://your-app.up.railway.app/api/document \
  -H "Content-Type: application/json" \
  -d @test-register.json

# V1 should still work (backward compatibility)
```

## ✅ Post-Deployment Verification

- [ ] **Test Registration Flow**
  - Register new document via V2 API
  - Receive encryption_key in response
  - Verify transaction on Etherscan
  - Check IPFS hash on Pinata gateway

- [ ] **Test Verification Flow**
  - Verify document without encryption_key (metadata only)
  - Verify document with encryption_key (get PDF)
  - Confirm PDF decrypts correctly
  - Verify hash matches original

- [ ] **Test Error Handling**
  - Try verifying non-existent document
  - Try decrypting with wrong key
  - Test revoked document verification
  - Test expired document detection

- [ ] **Monitor Costs**
  - Check Pinata usage dashboard
  - Monitor blockchain transaction costs
  - Track Railway compute/bandwidth

- [ ] **Security Audit**
  - Ensure encryption keys never logged
  - Verify HTTPS on all endpoints
  - Check CORS configuration
  - Review error messages (no sensitive data leak)

## 🔧 Troubleshooting

### **Issue: Pinata upload fails**
```bash
# Test Pinata connection
curl -X GET https://api.pinata.cloud/data/testAuthentication \
  -H "pinata_api_key: YOUR_API_KEY" \
  -H "pinata_secret_api_key: YOUR_SECRET_KEY"

# Expected: {"message":"Congratulations! You are communicating with the Pinata API!"}
```

### **Issue: Blockchain transaction fails**
```bash
# Check wallet balance
# Ensure at least 0.01 Sepolia ETH for gas

# Check contract deployment
# Verify contract address on Etherscan

# Check transaction status
# Look for revert reason on Etherscan
```

### **Issue: Decryption fails**
```bash
# Verify encryption key is base64 encoded
# Ensure key saved from registration response
# Check IPFS file retrieved successfully
# Verify file format (should have IV prepended)
```

### **Issue: Railway deployment fails**
```bash
# Check logs
railway logs

# Verify environment variables
railway variables

# Restart service
railway service restart
```

## 📊 Monitoring

### **Daily Checks**
- [ ] Check Railway logs for errors
- [ ] Monitor Pinata storage usage
- [ ] Check blockchain transaction success rate
- [ ] Review API response times

### **Weekly Checks**
- [ ] Review Pinata pinned files
- [ ] Check blockchain gas costs
- [ ] Audit registered documents count
- [ ] Verify no orphaned IPFS files

### **Monthly Checks**
- [ ] Review Pinata bill
- [ ] Analyze blockchain costs
- [ ] Check for failed transactions
- [ ] Performance optimization opportunities

## 📞 Support

**Pinata Issues:**
- Support: https://pinata.cloud/support
- Status: https://status.pinata.cloud

**Blockchain Issues:**
- Sepolia Faucet: https://sepolia-faucet.pk910.de
- Etherscan: https://sepolia.etherscan.io
- RPC Status: https://publicnode.com

**Railway Issues:**
- Logs: `railway logs`
- Support: https://railway.app/help

## 🎯 Success Criteria

✅ **Deployment Successful If:**
1. V2 contract deployed and verified on Sepolia
2. Pinata authentication successful
3. Document registration returns encryption_key
4. IPFS file accessible via gateway
5. Blockchain metadata retrievable
6. PDF decryption successful with key
7. V1 API still functional (backward compatibility)
8. No errors in Railway logs

---

**Production URL:** https://web-production-ef55e.up.railway.app

**Contract Addresses:**
- V1: `0x6028b310AEAb9cbf2cBFFBDA685C7F796F3FC4774960A`
- V2: `(deploy first)`

**Wallet:** `0xE895AdB9cbf2cBFFBDA685C7F796F3FC4774960A`

**Current Balance:** Check on [Sepolia Etherscan](https://sepolia.etherscan.io/address/0xE895AdB9cbf2cBFFBDA685C7F796F3FC4774960A)
