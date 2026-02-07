# 📮 Postman Setup Guide for TrustChain API

## Quick Setup (Using Collection)

### 1. Import the Collection

1. Open Postman
2. Click **Import** (top left)
3. Drag and drop `TrustChain_API.postman_collection.json` or click **Upload Files**
4. Click **Import**

### 2. Set Up Environment Variables

1. Click **Environments** (left sidebar)
2. Click **+** to create new environment
3. Name it: "TrustChain Railway"
4. Add these variables:

| Variable | Initial Value | Current Value |
|----------|--------------|---------------|
| `base_url` | `https://web-production-ef55e.up.railway.app` | `https://web-production-ef55e.up.railway.app` |
| `api_token` | (leave empty for now) | (leave empty for now) |

5. Click **Save**
6. Select "TrustChain Railway" from environment dropdown (top right)

---

## Manual Setup (If Collection Doesn't Exist)

### Create New Collection

1. Click **Collections** → **+** (Create Collection)
2. Name: "TrustChain API"
3. Add requests below:

---

### 1. Health Check

**Request:** `GET Health Check`
```
GET {{base_url}}/api/health
```

**Expected Response:**
```json
{
  "status": "ok",
  "timestamp": "2026-02-07T15:40:25+00:00",
  "service": "TrustChain API",
  "version": "1.0.0"
}
```

---

### 2. Blockchain Health Check

**Request:** `GET Blockchain Health`
```
GET {{base_url}}/api/health/blockchain
```

**Expected Response:**
```json
{
  "status": "ok",
  "blockchain": {
    "rpc_configured": true,
    "contract_deployed": true,
    "network": "sepolia",
    "service_ready": true
  }
}
```

---

### 3. Database Health Check

**Request:** `GET Database Health`
```
GET {{base_url}}/api/health/database
```

**Expected Response:**
```json
{
  "status": "ok",
  "database": {
    "connected": true,
    "documents_count": 0
  }
}
```

---

### 4. Register Document

**Request:** `POST Register Document`
```
POST {{base_url}}/api/documents
```

**Headers:**
```
Authorization: Bearer {{api_token}}
```

**Body:** (form-data)
| Key | Type | Value |
|-----|------|-------|
| `file` | File | Select a PDF file |
| `issuer_name` | Text | University of XYZ |
| `document_type` | Text | certificate |
| `metadata[student_name]` | Text | John Doe |
| `metadata[degree]` | Text | Computer Science |
| `expiry_date` | Text | 2030-12-31 (optional) |

**Expected Response:**
```json
{
  "success": true,
  "message": "Document registered successfully",
  "data": {
    "id": 1,
    "document_hash": "0x1234...",
    "transaction_hash": "0xabc...",
    "blockchain_status": "pending"
  }
}
```

---

### 5. Verify Document

**Request:** `POST Verify Document`
```
POST {{base_url}}/api/documents/verify
```

**Body:** (form-data)
| Key | Type | Value |
|-----|------|-------|
| `file` | File | Select the same PDF file |

**Expected Response:**
```json
{
  "success": true,
  "verified": true,
  "document": {
    "hash": "0x1234...",
    "issuer": "University of XYZ",
    "issue_date": "2026-02-07",
    "status": "valid"
  }
}
```

---

### 6. Get Document Details

**Request:** `GET Document Details`
```
GET {{base_url}}/api/documents/{id}
```

Replace `{id}` with actual document ID (e.g., `/api/documents/1`)

**Headers:**
```
Authorization: Bearer {{api_token}}
```

---

### 7. List All Documents

**Request:** `GET List Documents`
```
GET {{base_url}}/api/documents
```

**Headers:**
```
Authorization: Bearer {{api_token}}
```

**Query Parameters (optional):**
- `page`: 1
- `per_page`: 10

---

### 8. Revoke Document

**Request:** `POST Revoke Document`
```
POST {{base_url}}/api/documents/{id}/revoke
```

**Headers:**
```
Authorization: Bearer {{api_token}}
Content-Type: application/json
```

**Body:** (raw JSON)
```json
{
  "reason": "Document contains errors"
}
```

---

### 9. Check Transaction Status

**Request:** `GET Transaction Status`
```
GET {{base_url}}/api/documents/transaction/{hash}
```

Replace `{hash}` with actual transaction hash

---

## Getting API Token (For Protected Endpoints)

Most document endpoints require authentication. To get a token:

### Option 1: Register User (if authentication is set up)

**Request:** `POST Register`
```
POST {{base_url}}/api/register
Content-Type: application/json
```

**Body:**
```json
{
  "name": "Test User",
  "email": "test@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Option 2: Login

**Request:** `POST Login`
```
POST {{base_url}}/api/login
Content-Type: application/json
```

**Body:**
```json
{
  "email": "test@example.com",
  "password": "password123"
}
```

**Response will include token:**
```json
{
  "token": "1|abc123def456..."
}
```

**Copy this token** and add to your environment:
- Variable: `api_token`
- Value: `1|abc123def456...`

---

## Testing Flow

### Step 1: Check Health
```
GET /api/health ✅
GET /api/health/blockchain ✅
GET /api/health/database ✅
```

### Step 2: Register/Login
```
POST /api/register
POST /api/login → Get token
```

### Step 3: Register Document
```
POST /api/documents
(with PDF file)
```

### Step 4: Verify Document
```
POST /api/documents/verify
(with same PDF file)
```

### Step 5: Check Status
```
GET /api/documents/{id}
GET /api/documents/transaction/{hash}
```

---

## Environment Variables Reference

| Variable | Production | Local |
|----------|-----------|-------|
| `base_url` | `https://web-production-ef55e.up.railway.app` | `http://localhost:8000` |
| `api_token` | (from login response) | (from login response) |

---

## Tips

### 1. Save Test PDF
Create a simple test PDF for consistent testing. Use the same file for:
- Registration
- Verification

### 2. Collection Variables
Use Postman's collection variables to store:
```javascript
// In Tests tab of POST Register Document
pm.collectionVariables.set("document_id", pm.response.json().data.id);
pm.collectionVariables.set("transaction_hash", pm.response.json().data.transaction_hash);
```

### 3. Pre-request Scripts
Auto-add timestamp:
```javascript
pm.environment.set("timestamp", new Date().toISOString());
```

### 4. Test Scripts
Validate responses:
```javascript
pm.test("Status is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.success).to.eql(true);
});
```

---

## Troubleshooting

### Error: "Unauthenticated"
→ Add `Authorization: Bearer {{api_token}}` header

### Error: "could not find driver"
→ Database not configured in Railway

### Error: "Contract not deployed"
→ Add `DOCUMENT_REGISTRY_CONTRACT_ADDRESS` to Railway

### Error: 404 Not Found
→ Check `base_url` is correct

### Error: CORS
→ Add your domain to CORS configuration

---

## Quick Test Commands (cURL alternative)

```bash
# Health check
curl https://web-production-ef55e.up.railway.app/api/health

# Blockchain health
curl https://web-production-ef55e.up.railway.app/api/health/blockchain

# Register document
curl -X POST https://web-production-ef55e.up.railway.app/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@document.pdf" \
  -F "issuer_name=Test University" \
  -F "document_type=certificate"
```

---

## Next Steps

1. ✅ Import collection to Postman
2. ✅ Set up environment variables
3. ✅ Test health endpoints
4. ✅ Configure authentication
5. ✅ Test document registration
6. 🚀 Start building your integration!
