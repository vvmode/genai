# 📝 Direct Blockchain API

## Overview

**Simple approach:** Send JSON → Write to Blockchain → Return transaction hash

All document metadata is stored directly on the Ethereum blockchain. No databases, no file storage, no IPFS - just pure blockchain.

## 🚀 API Endpoint

**URL:** `POST /api/blockchain/document`

**Content-Type:** `application/json`

## 📥 Request Format

### Register Document

```json
{
  "action": "register",
  "document": {
    "type": "certificate",
    "number": "CERT-2026-001",
    "title": "Bachelor of Science",
    "category": "education",
    "subcategory": "degree",
    "description": "Computer Science Degree",
    "language": "en",
    "version": "1.0",
    "security_level": "high",
    "pdf_base64": "JVBERi0xLjQK..." // Optional
  },
  "validity": {
    "issued_date": "2026-01-15",
    "expiry_date": "2030-01-15",
    "is_permanent": false,
    "renewable": true,
    "grace_period_days": 30
  },
  "issuer": {
    "name": "University Name",
    "country": "US",
    "state": "California",
    "city": "San Francisco",
    "registration_number": "UNI-123",
    "contact_email": "issuer@university.edu",
    "website": "https://university.edu",
    "department": "Computer Science"
  },
  "holder": {
    "full_name": "Jane Doe",
    "id_number": "STU-456",
    "nationality": "US",
    "date_of_birth": "1995-05-15",
    "contact_email": "jane@example.com"
  },
  "metadata": {
    "degree": "Bachelor of Science",
    "major": "Computer Science",
    "gpa": "4.0",
    "honors": "Summa Cum Laude"
  }
}
```

### Verify Document

```json
{
  "action": "verify",
  "document_id": "CERT-2026-001"
}
```

## 📤 Response Format

### Registration Success

```json
{
  "success": true,
  "action": "registered",
  "message": "Document registered successfully on blockchain",
  "data": {
    "document_id": "CERT-2026-001",
    "document_type": "certificate",
    "document_title": "Bachelor of Science",
    "pdf_hash": "0xa1b2c3d4...",
    "blockchain": {
      "transaction_hash": "0x123abc...",
      "contract_address": "0x...",
      "explorer_url": "https://sepolia.etherscan.io/tx/0x123abc...",
      "network": "sepolia",
      "status": "pending"
    },
    "issuer": {
      "name": "University Name",
      "country": "US"
    },
    "holder": {
      "name": "Jane Doe"
    },
    "validity": {
      "issued_date": "2026-01-15",
      "expiry_date": "2030-01-15",
      "is_permanent": false
    },
    "registered_at": "2026-02-08T10:00:00Z"
  },
  "storage_model": "direct_blockchain",
  "note": "All metadata stored directly on blockchain. Check transaction status on Etherscan."
}
```

### Verification Success

```json
{
  "success": true,
  "action": "verified",
  "verified": true,
  "message": "Document is authentic and valid",
  "data": {
    "document_id": "CERT-2026-001",
    "verified": true,
    "status": "valid",
    "document": {
      "type": "certificate",
      "number": "CERT-2026-001",
      "title": "Bachelor of Science",
      "category": "education",
      "subcategory": "degree",
      "description": "Computer Science Degree",
      "language": "en",
      "version": "1.0",
      "security_level": "high"
    },
    "validity": {
      "issued_date": "2026-01-15",
      "expiry_date": "2030-01-15",
      "is_permanent": false,
      "is_expired": false,
      "renewable": true,
      "grace_period_days": 30
    },
    "issuer": {
      "name": "University Name",
      "country": "US",
      "state": "California",
      "city": "San Francisco",
      "registration_number": "UNI-123",
      "contact_email": "issuer@university.edu",
      "website": "https://university.edu",
      "department": "Computer Science"
    },
    "holder": {
      "full_name": "Jane Doe",
      "id_number": "STU-456",
      "nationality": "US",
      "date_of_birth": "1995-05-15",
      "contact_email": "jane@example.com"
    },
    "blockchain": {
      "pdf_hash": "0xa1b2c3d4...",
      "revoked": false,
      "registered_at": "2026-02-08 10:00:00"
    },
    "metadata": {
      "degree": "Bachelor of Science",
      "major": "Computer Science",
      "gpa": "4.0",
      "honors": "Summa Cum Laude"
    }
  },
  "storage_model": "direct_blockchain",
  "data_source": "ethereum_blockchain"
}
```

## 🔧 How It Works

```
┌─────────────────────────────────────────┐
│   1. Client sends JSON                   │
│      POST /api/blockchain/document      │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│   2. API validates data                  │
│      - Check required fields             │
│      - Generate PDF hash                 │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│   3. Write to Blockchain                 │
│      - Call DocumentRegistryV2          │
│      - Store all metadata on-chain      │
│      - Wait for transaction             │
└─────────────┬───────────────────────────┘
              │
              ▼
┌─────────────────────────────────────────┐
│   4. Return Result                       │
│      - Transaction hash                  │
│      - Etherscan link                    │
│      - Document ID                       │
└─────────────────────────────────────────┘
```

## 💰 Cost Per Document

**Blockchain Storage:**
- ~15 fields stored on-chain
- Gas cost: ~$2-5 per document
- Depends on network congestion
- Permanent storage (no monthly fees)

**What's Stored:**
- ✅ All document metadata (type, number, title, category, etc.)
- ✅ All validity information (dates, permanent status, etc.)
- ✅ Complete issuer information
- ✅ Complete holder information
- ✅ PDF hash (32 bytes)
- ✅ Additional metadata as JSON string

**What's NOT Stored:**
- ❌ PDF file itself (only hash)
- ❌ Images or attachments

## 🔍 Verification

**Anyone can verify:**
- Document authenticity
- Issuer information
- Holder information
- Validity period
- Expiry status
- Revocation status

**Data is public:**
- All metadata visible on blockchain
- Anyone can query Etherscan
- Transparent and auditable
- Tamper-proof

## 🧪 Testing

```bash
# Register document
curl -X POST https://your-api.com/api/blockchain/document \
  -H "Content-Type: application/json" \
  -d @test-document.json

# Verify document
curl -X POST https://your-api.com/api/blockchain/document \
  -H "Content-Type: application/json" \
  -d '{
    "action": "verify",
    "document_id": "CERT-2026-001"
  }'
```

## ⚙️ Configuration

Add to `.env`:

```env
# DocumentRegistry V2 Contract
DOCUMENT_REGISTRY_V2_ADDRESS=0x...

# Blockchain RPC
ETHEREUM_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
BLOCKCHAIN_NETWORK=sepolia

# Wallet (for signing transactions)
BLOCKCHAIN_WALLET_PRIVATE_KEY=your_private_key
```

## 🚀 Deployment Steps

1. **Deploy Smart Contract**
   ```bash
   npx hardhat run scripts/deploy-v2.js --network sepolia
   ```

2. **Configure Environment**
   ```bash
   # Add contract address to Railway
   DOCUMENT_REGISTRY_V2_ADDRESS=0x...
   ```

3. **Update BlockchainService**
   - Implement real `registerDocumentV2()` method
   - Implement real `getDocumentMetadata()` method
   - Use Web3 to interact with contract

4. **Test Endpoint**
   ```bash
   curl -X POST http://localhost:8000/api/blockchain/document \
     -H "Content-Type: application/json" \
     -d @test-hybrid-register.json
   ```

5. **Deploy to Production**
   ```bash
   git push origin main
   ```

## ✅ Advantages

- ✅ **Simple:** Just JSON in, transaction hash out
- ✅ **Immutable:** Blockchain guarantees permanence
- ✅ **Transparent:** All data publicly verifiable
- ✅ **No Dependencies:** No IPFS, no external storage
- ✅ **One-time Cost:** No monthly subscriptions
- ✅ **Fast Verification:** Direct blockchain query

## ⚠️ Considerations

- **Cost:** ~$2-5 per document (one-time, permanent)
- **Public Data:** All metadata visible to everyone
- **PDF Storage:** Only hash stored (not actual PDF)
- **Gas Fees:** Vary based on network congestion
- **Transaction Time:** ~15 seconds on Sepolia

## 🔗 Related Files

- **Controller:** [BlockchainDocumentController.php](app/Http/Controllers/Api/BlockchainDocumentController.php)
- **Smart Contract:** [DocumentRegistryV2.sol](contracts/DocumentRegistryV2.sol)
- **Service:** [BlockchainService.php](app/Services/BlockchainService.php)
- **Test Data:** [test-hybrid-register.json](test-hybrid-register.json)

---

**Production URL:** `https://web-production-ef55e.up.railway.app/api/blockchain/document`
