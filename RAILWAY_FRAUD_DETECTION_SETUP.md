## Railway Setup for AI Fraud Detection

### Step 1: Add OpenAI API Key to Railway

1. Go to Railway Dashboard: https://railway.app/
2. Select your project: `web-production-ef55e`
3. Go to **Variables** tab
4. Click **+ New Variable**
5. Add:
   ```
   Name: OPENAI_API_KEY
   Value: sk-proj-YOUR_OPENAI_API_KEY_HERE
   ```
   (Use the OpenAI API key provided to you)
6. Click **Add** (Railway will auto-redeploy)

### Step 2: Run Migration on Railway

Open Railway terminal and run:

```bash
php artisan migrate --force
```

This creates the `verified_organizations` table.

### Step 3: Create Test Organization

**Option A: Using Seeder (Recommended)**

In Railway terminal:

```bash
php artisan db:seed --class=VerifiedOrganizationSeeder
```

This creates 3 test organizations. Copy one of the API keys shown.

**Option B: Manual Creation**

In Railway terminal:

```bash
php artisan tinker
```

Then run:

```php
use App\Models\VerifiedOrganization;

$org = VerifiedOrganization::create([
    'organization_name' => 'Test University',
    'registration_number' => 'TEST-UNI-2026-001',
    'country_code' => 'US',
    'api_key' => VerifiedOrganization::generateApiKey(),
    'email' => 'admin@testuniversity.edu',
    'contact_person' => 'Dr. John Smith',
    'status' => 'active',
    'verified_at' => now(),
]);

echo "API Key: " . $org->api_key;
```

**SAVE THE API KEY!** You'll need it for testing.

### Step 4: Test the API

#### Test 1: Verify API Key

```bash
curl -X GET "https://web-production-ef55e.up.railway.app/api/fraud-detection/verify-key" \
  -H "X-Organization-Key: YOUR_API_KEY_HERE"
```

Expected response:
```json
{
  "success": true,
  "verified": true,
  "organization": {
    "id": 1,
    "name": "Test University",
    "country": "US",
    "status": "active"
  }
}
```

#### Test 2: Analyze Certificate

You need a PDF certificate. Create a simple test certificate or use an existing one.

```bash
curl -X POST "https://web-production-ef55e.up.railway.app/api/fraud-detection/analyze" \
  -H "X-Organization-Key: YOUR_API_KEY_HERE" \
  -F "document=@certificate.pdf" \
  -F "document_type=certificate" \
  -F "issuer_name=Harvard University" \
  -F "holder_name=John Doe" \
  -F "issue_date=2025-06-15"
```

Expected response:
```json
{
  "success": true,
  "organization": {
    "name": "Test University",
    "id": 1
  },
  "analysis": {
    "fraud_score": 25,
    "risk_level": "low",
    "is_suspicious": false,
    "confidence": 85,
    "fraud_indicators": [...],
    "authenticity_checks": {...},
    "red_flags": [],
    "recommendations": [...]
  }
}
```

### Step 5: Create Test Certificate PDF

If you don't have a certificate PDF, create one:

**Quick method using LibreOffice/Word:**

1. Open Word/LibreOffice Writer
2. Create a simple certificate:
   ```
   CERTIFICATE OF COMPLETION
   
   This certifies that
   John Doe
   
   Has successfully completed
   Advanced Web Development Course
   
   Issued by: Tech Academy
   Date: June 15, 2025
   Certificate ID: CERT-2025-123
   ```
3. Save as PDF: `test-certificate.pdf`
4. Use this PDF for testing

### Step 6: Full Test Run

```bash
# 1. Get your API key from Step 3
API_KEY="org_xxxxxxxxxxxxxx"

# 2. Verify key works
curl -X GET "https://web-production-ef55e.up.railway.app/api/fraud-detection/verify-key" \
  -H "X-Organization-Key: $API_KEY"

# 3. Test fraud detection with your PDF
curl -X POST "https://web-production-ef55e.up.railway.app/api/fraud-detection/analyze" \
  -H "X-Organization-Key: $API_KEY" \
  -F "document=@test-certificate.pdf" \
  -F "document_type=certificate" \
  -F "issuer_name=Tech Academy" \
  -F "holder_name=John Doe" \
  -F "issue_date=2025-06-15" \
  | jq '.'
```

### Troubleshooting

**Error: "Organization API key required"**
- Check you're sending `X-Organization-Key` header
- Verify the header name is exact (case-sensitive)

**Error: "Invalid or inactive organization"**
- Check organization status is 'active'
- Verify API key is correct
- Run: `VerifiedOrganization::where('api_key', 'YOUR_KEY')->first()`

**Error: "Unable to extract text from PDF"**
- PDF might be image-based (scanned document)
- Try a different PDF with actual text
- Check PDF file is valid and not corrupted

**Error: "OpenAI API key not configured"**
- Verify `OPENAI_API_KEY` is set in Railway variables
- Check Railway has redeployed after adding the key
- View logs: Railway dashboard → Deployments → View logs

**Error: "OpenAI API request failed"**
- Check API key is valid at https://platform.openai.com/api-keys
- Verify you have credits in OpenAI account
- Check API key permissions (should have access to GPT-4)

### Cost Monitoring

Monitor OpenAI usage:
- Dashboard: https://platform.openai.com/usage
- Set usage limits: https://platform.openai.com/account/billing/limits

Average cost per analysis: ~$0.12 (GPT-4)

### Next Steps

1. ✅ Add OpenAI key to Railway
2. ✅ Run migrations
3. ✅ Create test organization
4. ✅ Test fraud detection
5. 📊 Monitor API usage
6. 🔒 Secure API keys (never commit to git)
7. 📈 Integrate with your frontend

### API Endpoints Summary

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/fraud-detection/verify-key` | Verify organization API key |
| POST | `/api/fraud-detection/analyze` | Analyze certificate for fraud |

### Documentation

Full documentation: `docs/FRAUD_DETECTION.md`

### Support

Check logs in Railway dashboard for detailed error messages.
