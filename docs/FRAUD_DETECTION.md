# AI Fraud Detection Feature

## Overview

This feature uses OpenAI GPT-4 to analyze uploaded certificates for fraud indicators and authenticity issues. Organizations must be verified before they can use this API.

## Key Features

- ✅ **AI-Powered Analysis**: Uses GPT-4 to detect fraud patterns
- ✅ **Organization Verification**: Only verified organizations can submit documents
- ✅ **Fraud Scoring**: 0-100 score with risk level classification
- ✅ **Detailed Indicators**: Specific fraud indicators and red flags
- ✅ **Independent from Blockchain**: Separate feature, doesn't affect blockchain writes
- ✅ **PDF Text Extraction**: Analyzes certificate content

## Setup Instructions

### 1. Add OpenAI API Key

Add to your `.env` file:

```bash
OPENAI_API_KEY=sk-your-openai-api-key-here
OPENAI_ORGANIZATION=org-your-org-id  # Optional
OPENAI_MODEL=gpt-4  # Default model
```

Get your API key from: https://platform.openai.com/api-keys

### 2. Run Migrations

```bash
# Production (Railway will ask for confirmation)
php artisan migrate

# Or run specific migration
php artisan migrate --path=database/migrations/2025_01_01_000006_create_verified_organizations_table.php
```

### 3. Create Verified Organizations

**Option A: Using Seeder (Recommended for testing)**

```bash
php artisan db:seed --class=VerifiedOrganizationSeeder
```

This creates 3 test organizations with API keys.

**Option B: Using Tinker (Manual)**

```bash
php artisan tinker
```

```php
use App\Models\VerifiedOrganization;

$org = VerifiedOrganization::create([
    'organization_name' => 'Your Organization Name',
    'registration_number' => 'ORG-2025-001',
    'country_code' => 'US',
    'api_key' => VerifiedOrganization::generateApiKey(),
    'email' => 'contact@organization.com',
    'contact_person' => 'John Doe',
    'address' => '123 Main St, City, Country',
    'status' => 'active',
    'verified_at' => now(),
]);

echo "API Key: " . $org->api_key;
```

**Save the API key** - you'll need it for API requests!

## API Endpoints

### 1. Verify Organization API Key

**Endpoint:** `GET /api/fraud-detection/verify-key`

**Headers:**
```
X-Organization-Key: org_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

**Response:**
```json
{
  "success": true,
  "verified": true,
  "organization": {
    "id": 1,
    "name": "Test University",
    "country": "US",
    "status": "active",
    "verified_at": "2026-02-08T07:00:00+00:00"
  }
}
```

### 2. Analyze Certificate for Fraud

**Endpoint:** `POST /api/fraud-detection/analyze`

**Headers:**
```
X-Organization-Key: org_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
Content-Type: multipart/form-data
```

**Form Data:**
- `document` (file, required) - PDF file, max 10MB
- `document_type` (string, required) - One of: certificate, diploma, license, degree, transcript
- `issuer_name` (string, required) - Name of issuing institution
- `holder_name` (string, required) - Name of certificate holder
- `issue_date` (date, required) - Date issued (YYYY-MM-DD)

**Example Request:**
```bash
curl -X POST "https://web-production-ef55e.up.railway.app/api/fraud-detection/analyze" \
  -H "X-Organization-Key: org_abc123..." \
  -F "document=@certificate.pdf" \
  -F "document_type=certificate" \
  -F "issuer_name=Harvard University" \
  -F "holder_name=John Doe" \
  -F "issue_date=2025-05-20"
```

**Success Response:**
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
    "fraud_indicators": [
      {
        "type": "date_mismatch",
        "description": "Issue date is slightly ahead of typical graduation period",
        "severity": "low"
      }
    ],
    "authenticity_checks": {
      "formatting_consistent": true,
      "language_professional": true,
      "dates_logical": true,
      "issuer_mentioned": true,
      "holder_mentioned": true,
      "signatures_references": true
    },
    "red_flags": [],
    "recommendations": [
      "Document appears legitimate",
      "Minor date discrepancy noted but within acceptable range"
    ],
    "summary": "Certificate appears authentic with minimal fraud indicators. Professional formatting and consistent institutional references detected."
  },
  "document_info": {
    "type": "certificate",
    "issuer": "Harvard University",
    "holder": "John Doe",
    "issue_date": "2025-05-20"
  },
  "analyzed_at": "2026-02-08T08:15:00+00:00"
}
```

## Fraud Score Interpretation

| Score Range | Risk Level | Description |
|-------------|------------|-------------|
| 0-25 | **Low** | Document appears legitimate |
| 26-50 | **Medium** | Some concerns, review recommended |
| 51-75 | **High** | Significant fraud indicators detected |
| 76-100 | **Critical** | High probability of fraud |

## What AI Checks For

The AI analyzes documents for:

1. **Formatting Issues**
   - Inconsistent fonts or layouts
   - Poor quality or pixelated text
   - Unusual spacing or alignment

2. **Content Analysis**
   - Grammar and spelling errors
   - Unprofessional language
   - Generic or template-like text
   - Missing institutional details

3. **Date Validation**
   - Future dates
   - Illogical date sequences
   - Expired credentials

4. **Metadata Verification**
   - Mismatch between submitted data and document content
   - Inconsistent issuer information
   - Suspicious holder details

5. **Institutional Credibility**
   - Missing contact information
   - Vague or generic institution names
   - Lack of official markers

6. **Tampering Indicators**
   - Signs of digital manipulation
   - Altered sections
   - Inconsistent document structure

## Testing

Use the provided test script:

```bash
chmod +x test-fraud-detection.sh
./test-fraud-detection.sh
```

Or test manually with curl (see examples above).

## Error Responses

### Invalid/Missing API Key
```json
{
  "success": false,
  "error": "Organization API key required in X-Organization-Key header"
}
```

### Inactive Organization
```json
{
  "success": false,
  "error": "Invalid or inactive organization"
}
```

### PDF Extraction Failed
```json
{
  "success": false,
  "error": "Unable to extract text from PDF. Document may be image-based or corrupted."
}
```

### OpenAI API Error
```json
{
  "success": false,
  "error": "Fraud detection analysis failed: OpenAI API key not configured"
}
```

## Database Schema

### verified_organizations Table

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| organization_name | varchar | Organization name |
| registration_number | varchar | Unique registration number |
| country_code | char(2) | ISO country code |
| api_key | varchar | Unique API key (org_...) |
| email | varchar | Contact email |
| contact_person | varchar | Contact person name |
| address | text | Physical address |
| status | enum | active, suspended, pending |
| verified_at | timestamp | When organization was verified |
| created_at | timestamp | Record creation |
| updated_at | timestamp | Last update |

## Security Considerations

- ✅ API keys are required for all requests
- ✅ Only `active` organizations can use the API
- ✅ File size limited to 10MB
- ✅ Only PDF files accepted
- ✅ All requests are logged with organization ID
- ✅ API keys stored hashed in production (recommended)

## Cost Estimation

**OpenAI API Costs (GPT-4):**
- Input: ~$0.03 per 1K tokens
- Output: ~$0.06 per 1K tokens
- Average certificate analysis: ~2K tokens input + 1K output = **~$0.12 per analysis**

Monitor your OpenAI usage at: https://platform.openai.com/usage

## Future Enhancements

- [ ] Image-based PDF analysis (OCR + Vision API)
- [ ] Historical fraud pattern learning
- [ ] Institution verification database
- [ ] Batch analysis support
- [ ] Webhook notifications for high-risk detections
- [ ] Custom fraud rules per organization
- [ ] API rate limiting

## Support

For issues or questions:
1. Check logs: `storage/logs/laravel.log`
2. Verify OpenAI API key is valid
3. Ensure organization status is `active`
4. Check PDF file is valid and contains extractable text

## Related Files

- Controller: `app/Http/Controllers/Api/FraudDetectionController.php`
- Model: `app/Models/VerifiedOrganization.php`
- Migration: `database/migrations/2025_01_01_000006_create_verified_organizations_table.php`
- Seeder: `database/seeders/VerifiedOrganizationSeeder.php`
- Routes: `routes/api.php` (fraud-detection prefix)
- Config: `config/services.php` (openai)
- Test Script: `test-fraud-detection.sh`
