# Hybrid Storage Implementation

## 🎯 Architecture Overview

**Storage Model:** Critical metadata on blockchain + Encrypted PDF on IPFS

### **What Goes Where:**

```
┌─────────────────────────────────────────────────────────────┐
│                     USER SUBMITS JSON                        │
│  {document, validity, issuer, holder, metadata, pdf_base64} │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    API PROCESSING                            │
│  1. Extract PDF → Generate hash                              │
│  2. Generate encryption key                                  │
│  3. Encrypt PDF with AES-256                                 │
└──────────────────────┬──────────────────────────────────────┘
                       │
          ┌────────────┴────────────┐
          │                         │
          ▼                         ▼
┌──────────────────┐      ┌──────────────────┐
│  IPFS Storage     │      │  Blockchain       │
│  (Pinata)         │      │  (Ethereum)       │
├──────────────────┤      ├──────────────────┤
│ • Encrypted PDF   │      │ • Document ID     │
│ • IV              │      │ • Document Type   │
│ • Algorithm       │      │ • Issuer Info     │
│ • Timestamp       │      │ • Holder Info     │
│                  │      │ • Validity Dates  │
│ Returns:          │      │ • IPFS Hash       │
│ IPFS Hash (CID)   │      │ • PDF Hash        │
└──────────────────┘      └──────────────────┘
          │                         │
          └────────────┬────────────┘
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    RESPONSE TO USER                          │
│  • Document ID                                               │
│  • Encryption Key ⚠️ SAVE THIS!                             │
│  • IPFS Hash + Gateway URL                                   │
│  • Blockchain Transaction Hash                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 📊 Cost Breakdown

```
Per Document Registration:
├─ IPFS Storage (Pinata):    ~$0.001
├─ Blockchain Transaction:   ~$2-5 (with metadata)
└─ Total:                    ~$2-5

Compare to:
├─ IPFS Only (hash on chain): ~$0.50
├─ Direct Blockchain:         ~$500-1000
└─ Hybrid (Recommended):      ~$2-5 ✅
```

**Why Hybrid Costs More:**
- Storing comprehensive metadata on-chain (not just hash)
- ~15 fields stored directly on Ethereum
- Critical data always accessible even if IPFS fails
- No external database dependency for verification

---

## 🔒 Security Features

### **Encryption**
- Algorithm: AES-256-CBC
- Key Size: 256 bits (32 bytes)
- Unique encryption key per document
- Random IV per encryption
- Zero-knowledge: API doesn't store keys

### **Data Privacy**
```
Original PDF → [Encryption] → IPFS (Public but unreadable)
                    ↓
              Encryption Key (User keeps)
                    ↓
              Metadata (Public on blockchain)
```

**Who Can See What:**
- **Everyone:** Metadata (issuer, holder, dates, document type)
- **Key Holder Only:** PDF content
- **Blockchain:** IPFS hash + PDF hash for verification

---

## 🚀 API Endpoints

### **POST /api/document (Register)**

**Request:**
```json
{
  "action": "register",
  "document": {
    "type": "certificate",
    "number": "CERT-2026-001",
    "title": "Bachelor of Science",
    "pdf_base64": "JVBERi0xLjQK..."
  },
  "validity": {
    "issued_date": "2026-01-15",
    "expiry_date": "2030-01-15",
    "is_permanent": false
  },
  "issuer": {
    "name": "University Name",
    "country": "US",
    "registration_number": "UNI-123"
  },
  "holder": {
    "full_name": "Jane Doe",
    "id_number": "STU-456",
    "nationality": "US"
  },
  "metadata": {
    "grade": "A+",
    "gpa": "4.0"
  }
}
```

**Response:**
```json
{
  "success": true,
  "action": "registered",
  "message": "Document registered successfully with hybrid storage",
  "data": {
    "document_id": "CERT-2026-001",
    "pdf_hash": "a1b2c3...",
    "encryption_key": "dGVzdGtleQ==...",
    "ipfs": {
      "hash": "QmT5NvUt...",
      "gateway_url": "https://gateway.pinata.cloud/ipfs/QmT5NvUt...",
      "pin_size": 524288
    },
    "blockchain": {
      "transaction_hash": "0x123abc...",
      "explorer_url": "https://sepolia.etherscan.io/tx/0x123abc...",
      "status": "pending"
    },
    "storage_model": "hybrid",
    "metadata_location": "blockchain",
    "pdf_location": "ipfs_encrypted",
    "registered_at": "2026-02-08T10:00:00Z"
  },
  "warning": "⚠️ IMPORTANT: Save the encryption_key! You need it to decrypt and retrieve the PDF later."
}
```

---

### **POST /api/document (Verify)**

**Request (Metadata Only):**
```json
{
  "action": "verify",
  "document_id": "CERT-2026-001"
}
```

**Response (Metadata Only):**
```json
{
  "success": true,
  "action": "verified",
  "verified": true,
  "message": "Document is authentic and valid",
  "data": {
    "document_id": "CERT-2026-001",
    "verified": true,
    "document": {
      "type": "certificate",
      "number": "CERT-2026-001",
      "title": "Bachelor of Science"
    },
    "validity": {
      "issued_date": "2026-01-15",
      "expiry_date": "2030-01-15",
      "is_permanent": false,
      "is_expired": false
    },
    "issuer": {
      "name": "University Name",
      "country": "US",
      "registration_number": "UNI-123"
    },
    "holder": {
      "full_name": "Jane Doe",
      "id_number": "STU-456",
      "nationality": "US"
    },
    "blockchain": {
      "ipfs_hash": "QmT5NvUt...",
      "pdf_hash": "0xa1b2c3...",
      "revoked": false,
      "registered_at": "2026-02-08 10:00:00"
    },
    "pdf_retrieved": false,
    "note": "Provide encryption_key to retrieve the PDF"
  },
  "storage_model": "hybrid",
  "note": "Metadata retrieved from blockchain. PDF requires encryption_key."
}
```

**Request (With PDF Retrieval):**
```json
{
  "action": "verify",
  "document_id": "CERT-2026-001",
  "encryption_key": "dGVzdGtleQ==..."
}
```

**Response (With PDF):**
```json
{
  "success": true,
  "action": "verified",
  "verified": true,
  "data": {
    "document_id": "CERT-2026-001",
    "document": {
      "type": "certificate",
      "pdf_base64": "JVBERi0xLjQK..."
    },
    "pdf_retrieved": true,
    // ... same metadata as above
  }
}
```

---

## 🛠️ Setup Instructions

### **1. Sign Up for Pinata (IPFS)**

1. Go to: https://pinata.cloud
2. Sign up for free account (1GB free storage)
3. Go to API Keys → Generate New Key
4. Save:
   ```
   API Key: YOUR_PINATA_API_KEY
   API Secret: YOUR_PINATA_SECRET_KEY
   ```

### **2. Configure Railway Environment**

Add to Railway Variables:
```env
PINATA_API_KEY=your_pinata_api_key_here
PINATA_SECRET_KEY=your_pinata_secret_key_here
```

### **3. Deploy New Smart Contract**

```bash
# Compile new contract
npx hardhat compile

# Deploy V2 contract
npx hardhat run scripts/deploy-v2.js --network sepolia

# Get contract address from output
# Add to Railway:
DOCUMENT_REGISTRY_V2_ADDRESS=0x...
```

---

## 📋 Advantages of Hybrid Storage

| Feature | Current | Hybrid |
|---------|---------|--------|
| **Metadata Access** | Requires database | Always from blockchain |
| **PDF Storage** | Local files | IPFS (decentralized) |
| **Encryption** | None | AES-256 |
| **Data Privacy** | Visible in DB | Encrypted everywhere |
| **IPFS Failure Resilience** | N/A | Metadata still accessible |
| **Cost per Document** | Minimal | ~$2-5 |
| **Decentralization** | Centralized | Fully decentralized |
| **Scalability** | Limited | Unlimited |
| **Blockchain Size** | Hash only (32b) | Full metadata (~2KB) |

---

## 🔄 Recovery Scenarios

### **Scenario 1: IPFS Node Down**
```
✅ Metadata accessible from blockchain
✅ Can verify document authenticity
❌ Cannot retrieve PDF
→ Solution: Pin to another IPFS node with same hash
```

### **Scenario 2: Lost Encryption Key**
```
✅ Metadata accessible from blockchain
✅ Document authenticity verified
❌ Cannot decrypt PDF
→ Solution: No recovery (zero-knowledge by design)
→ Prevention: Store key securely (password manager, vault)
```

### **Scenario 3: Database Wipe**
```
✅ All data retrievable from blockchain
✅ IPFS files still accessible
→ Solution: Rebuild database from blockchain
```

### **Scenario 4: Blockchain Reorg**
```
✅ Transaction will confirm eventually
⚠️ Wait for more confirmations
→ Solution: Monitor transaction status
```

---

## 🎯 Use Cases

### **Public Verification (No Key Needed)**
- Check if document exists
- Verify issuer and holder
- Check expiry date
- Confirm not revoked
- **Use Case:** Employer verifying certificate authenticity

### **Full Access (With Key)**
- Download original PDF
- All public verification features
- **Use Case:** Document holder presenting credentials

### **Issuer Actions**
- Revoke documents
- Update IPFS hash (if re-pinned)
- View all issued documents
- **Use Case:** University revoking fake certificates

---

## 🔧 Implementation Status

- ✅ Smart Contract V2 (DocumentRegistryV2.sol)
- ✅ Encryption Service (AES-256-CBC)
- ✅ IPFS Service (Pinata integration)
- ✅ API Controller V2 (Hybrid storage)
- ⏳ Deploy smart contract to Sepolia
- ⏳ Update routes to use V2 controller
- ⏳ Test end-to-end flow

---

## 📞 Support

**If you lose your encryption key:**
- Document metadata is still verifiable
- PDF cannot be retrieved
- Contact issuer to re-issue document

**If IPFS is unavailable:**
- Metadata is always available on blockchain
- Wait for IPFS network recovery
- Or re-pin to new IPFS node

**For technical issues:**
- Check Railway logs
- Verify Pinata API keys
- Confirm blockchain transaction status
