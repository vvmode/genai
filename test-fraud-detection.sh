#!/bin/bash

# Fraud Detection API Test Script
# Test the AI-powered certificate fraud detection feature

BASE_URL="https://web-production-ef55e.up.railway.app/api"
# BASE_URL="http://localhost:8000/api"  # Uncomment for local testing

# Color codes for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Certificate Fraud Detection API Test${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Test 1: Verify API key (should fail without key)
echo -e "${YELLOW}Test 1: Verify API Key (No Key)${NC}"
curl -X GET "$BASE_URL/fraud-detection/verify-key" \
  -H "Content-Type: application/json" \
  | jq '.'
echo -e "\n"

# Test 2: Verify API key (with valid key)
# Replace this with an actual API key from database
API_KEY="org_test_key_12345"  # This is a placeholder

echo -e "${YELLOW}Test 2: Verify API Key (With Key)${NC}"
curl -X GET "$BASE_URL/fraud-detection/verify-key" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Key: $API_KEY" \
  | jq '.'
echo -e "\n"

# Test 3: Analyze certificate (requires PDF file)
echo -e "${YELLOW}Test 3: Analyze Certificate${NC}"
echo -e "${RED}Note: This test requires a PDF file. Create a test certificate PDF first.${NC}"
echo -e "Command to use:${NC}"
echo -e "${GREEN}"
cat << 'EOF'
curl -X POST "$BASE_URL/fraud-detection/analyze" \
  -H "X-Organization-Key: your_api_key_here" \
  -F "document=@/path/to/certificate.pdf" \
  -F "document_type=certificate" \
  -F "issuer_name=Test University" \
  -F "holder_name=John Doe" \
  -F "issue_date=2025-01-15" \
  | jq '.'
EOF
echo -e "${NC}\n"

# Instructions
echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Setup Instructions:${NC}"
echo -e "${BLUE}========================================${NC}"
echo -e "1. Add ${GREEN}OPENAI_API_KEY${NC} to your .env file"
echo -e "2. Run: ${GREEN}php artisan migrate${NC}"
echo -e "3. Create a test organization in database:"
echo -e "${GREEN}"
cat << 'EOF'
   php artisan tinker
   
   use App\Models\VerifiedOrganization;
   
   $org = VerifiedOrganization::create([
       'organization_name' => 'Test Organization',
       'registration_number' => 'TEST-2025-001',
       'country_code' => 'US',
       'api_key' => VerifiedOrganization::generateApiKey(),
       'email' => 'test@example.com',
       'status' => 'active',
       'verified_at' => now(),
   ]);
   
   echo "API Key: " . $org->api_key;
EOF
echo -e "${NC}"
echo -e "4. Use the generated API key in ${GREEN}X-Organization-Key${NC} header"
echo -e "5. Upload a test certificate PDF\n"

echo -e "${BLUE}API Endpoints:${NC}"
echo -e "  GET  ${GREEN}/api/fraud-detection/verify-key${NC}   - Verify organization API key"
echo -e "  POST ${GREEN}/api/fraud-detection/analyze${NC}       - Analyze certificate for fraud\n"

echo -e "${BLUE}Example Response:${NC}"
echo -e "${GREEN}"
cat << 'EOF'
{
  "success": true,
  "organization": {
    "name": "Test Organization",
    "id": 1
  },
  "analysis": {
    "fraud_score": 25,
    "risk_level": "low",
    "is_suspicious": false,
    "confidence": 85,
    "fraud_indicators": [],
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
      "No immediate concerns detected"
    ],
    "summary": "Certificate appears authentic with no major fraud indicators."
  },
  "document_info": {
    "type": "certificate",
    "issuer": "Test University",
    "holder": "John Doe",
    "issue_date": "2025-01-15"
  },
  "analyzed_at": "2026-02-08T08:15:00+00:00"
}
EOF
echo -e "${NC}\n"
