# Comprehensive Document API Structure

## Overview
The API now accepts a comprehensive JSON structure with detailed document information including validity periods, issuer details, holder information, and custom metadata.

## API Endpoint
```
POST https://web-production-ef55e.up.railway.app/api/document
Content-Type: application/json
```

## Complete JSON Structure

### Register Action
```json
{
  "action": "register",
  "document": {
    "type": "certificate|diploma|transcript|license|passport|id_card|contract",
    "number": "DOC-2026-001234",
    "title": "Document title",
    "category": "academic|professional|legal|government|medical",
    "language": "en",
    "pdf_base64": "base64_encoded_pdf_content"
  },
  "validity": {
    "issued_date": "2026-01-15",
    "effective_from": "2026-01-15",
    "effective_until": "2030-01-15",
    "expiry_date": "2030-01-15",
    "is_permanent": false,
    "status": "active|suspended|revoked|expired"
  },
  "issuer": {
    "name": "Organization Name",
    "department": "Department Name",
    "country": "US",
    "state": "State Name",
    "city": "City Name",
    "registration_number": "REG-12345",
    "contact_email": "contact@example.com",
    "website": "https://example.com",
    "authorized_signatory": "Full Name"
  },
  "holder": {
    "full_name": "John Doe",
    "id_number": "ID-12345",
    "date_of_birth": "2000-01-01",
    "nationality": "US",
    "email": "holder@example.com"
  },
  "metadata": {
    "description": "Document description",
    "grade": "A+",
    "gpa": "4.0",
    "credits": "120",
    "specialization": "Field of study",
    "notes": "Additional notes",
    "custom_fields": {
      "key": "value"
    }
  }
}
```

### Verify Action
```json
{
  "action": "verify",
  "document": {
    "pdf_base64": "base64_encoded_pdf_content"
  }
}
```

## Field Descriptions

### Document Object (Required for register)
- **type** (required): Type of document
- **number** (optional): Document identification number
- **title** (optional): Document title/name
- **category** (optional): Category classification
- **language** (optional): Document language code (default: "en")
- **pdf_base64** (required): Base64 encoded PDF file

### Validity Object (Optional)
- **issued_date** (optional): Date when document was issued
- **effective_from** (optional): Date when document becomes effective
- **effective_until** (optional): Date when document effectiveness ends
- **expiry_date** (optional): Date when document expires
- **is_permanent** (optional): Whether document is permanent (true/false)
- **status** (optional): Document status (active, suspended, revoked, expired)

### Issuer Object (Required for register)
- **name** (required): Name of issuing organization
- **department** (optional): Department within organization
- **country** (optional): 2-letter country code (e.g., "US", "UK")
- **state** (optional): State/province name
- **city** (optional): City name
- **registration_number** (optional): Official registration number
- **contact_email** (optional): Contact email address
- **website** (optional): Organization website URL
- **authorized_signatory** (optional): Name of authorized person

### Holder Object (Optional)
- **full_name** (optional): Full name of document holder
- **id_number** (optional): Holder's identification number
- **date_of_birth** (optional): Date of birth (YYYY-MM-DD)
- **nationality** (optional): 2-letter country code
- **email** (optional): Holder's email address

### Metadata Object (Optional)
- **description** (optional): Text description of the document
- **grade** (optional): Academic grade or performance rating
- **gpa** (optional): Grade point average
- **credits** (optional): Academic credits
- **specialization** (optional): Field of specialization
- **notes** (optional): Additional notes
- **custom_fields** (optional): Object with any custom key-value pairs

## Response Structure

### Success Response (Register)
```json
{
  "success": true,
  "action": "registered",
  "message": "Document registered successfully on blockchain",
  "data": {
    "document_id": "DOC-2026-001234",
    "document_hash": "0x...",
    "document_type": "certificate",
    "document_title": "Bachelor of Science",
    "issuer_name": "University Name",
    "holder_name": "John Doe",
    "issued_date": "2026-01-15",
    "expiry_date": "2030-01-15",
    "transaction_hash": "0x...",
    "status": "pending",
    "registered_at": "2026-02-07T19:30:00.000000Z",
    "blockchain_explorer": "https://sepolia.etherscan.io/tx/0x..."
  }
}
```

### Success Response (Verify)
```json
{
  "success": true,
  "action": "verified",
  "verified": true,
  "message": "Document is authentic and valid",
  "data": {
    "document_id": "DOC-2026-001234",
    "document_hash": "0x...",
    "document": {
      "type": "certificate",
      "number": "CERT-2026-001234",
      "title": "Bachelor of Science",
      "category": "academic",
      "language": "en"
    },
    "validity": {
      "issued_date": "2026-01-15",
      "effective_from": "2026-01-15",
      "effective_until": "2030-01-15",
      "expiry_date": "2030-01-15",
      "is_permanent": false,
      "status": "active"
    },
    "issuer": {
      "name": "University Name",
      "department": "Registrar Office",
      "country": "US",
      "state": "California",
      "city": "San Francisco",
      "registration_number": "UNI-12345",
      "contact_email": "registrar@university.edu",
      "website": "https://university.edu",
      "authorized_signatory": "Dr. John Smith"
    },
    "holder": {
      "full_name": "Jane Doe",
      "id_number": "STU-98765",
      "date_of_birth": "2000-05-20",
      "nationality": "US",
      "email": "jane.doe@example.com"
    },
    "metadata": {
      "description": "Bachelor degree in Computer Science",
      "grade": "First Class Honors",
      "gpa": "3.85",
      "custom_fields": {...}
    },
    "blockchain": {
      "revoked": false,
      "status": "confirmed",
      "transaction_hash": "0x...",
      "explorer_url": "https://sepolia.etherscan.io/tx/0x..."
    },
    "registered_at": "2026-02-07T19:30:00.000000Z"
  }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## Testing with cURL

### Register Document
```bash
curl -X POST https://web-production-ef55e.up.railway.app/api/document \
  -H "Content-Type: application/json" \
  -d @SAMPLE_REQUEST.json
```

### Verify Document
```bash
curl -X POST https://web-production-ef55e.up.railway.app/api/document \
  -H "Content-Type: application/json" \
  -d @SAMPLE_VERIFY_REQUEST.json
```

## Testing with Postman

1. Create a new POST request
2. URL: `https://web-production-ef55e.up.railway.app/api/document`
3. Headers: `Content-Type: application/json`
4. Body: Select "raw" and "JSON", paste the sample JSON
5. Click "Send"

## Notes

- All date fields use ISO 8601 format: `YYYY-MM-DD`
- Country codes use ISO 3166-1 alpha-2 format (2 letters)
- PDF files must be base64 encoded
- Maximum file size: Check your server configuration
- All optional fields can be omitted from the request
- Custom fields in metadata allow unlimited flexibility
- Document hash is automatically generated from PDF content
- Blockchain transaction is processed automatically

## Database Schema

The following fields are now stored in the database:

**Document Fields:**
- document_type, document_number, document_title, document_category, document_language

**Validity Fields:**
- issued_date, effective_from, effective_until, expiry_date, is_permanent, validity_status

**Issuer Fields:**
- issuer_name, issuer_department, issuer_country, issuer_state, issuer_city
- issuer_registration_number, issuer_contact_email, issuer_website, issuer_authorized_signatory

**Holder Fields:**
- holder_full_name, holder_id_number, holder_date_of_birth, holder_nationality, holder_email

**Metadata:**
- description, metadata (JSON field for custom data)
