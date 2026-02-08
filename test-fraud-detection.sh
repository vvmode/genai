#!/bin/bash

# Fraud Detection API Test Script
# Test the AI-powered certificate fraud detection feature

BASE_URL="https://web-production-ef55e.up.railway.app/api"
# BASE_URL="http://localhost:8000/api"  # Uncomment for local testing

# IMPORTANT: Replace with your actual API key from database
API_KEY="${1:-}"

# Color codes for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Certificate Fraud Detection API Test${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Check if API key is provided
if [ -z "$API_KEY" ]; then
    echo -e "${RED}ERROR: API Key required!${NC}\n"
    echo -e "Usage: ./test-fraud-detection.sh YOUR_API_KEY\n"
    echo -e "Get API key by running on Railway terminal:"
    echo -e "${GREEN}php artisan db:seed --class=VerifiedOrganizationSeeder${NC}\n"
    exit 1
fi

# Test 1: Verify API key
echo -e "${YELLOW}Test 1: Verify API Key${NC}"
echo -e "Testing with key: ${BLUE}${API_KEY:0:20}...${NC}\n"

VERIFY_RESPONSE=$(curl -s -X GET "$BASE_URL/fraud-detection/verify-key" \
  -H "Content-Type: application/json" \
  -H "X-Organization-Key: $API_KEY")

echo "$VERIFY_RESPONSE" | jq '.'

VERIFIED=$(echo "$VERIFY_RESPONSE" | jq -r '.verified // false')

if [ "$VERIFIED" == "true" ]; then
    echo -e "\n${GREEN}✓ API Key verified successfully!${NC}\n"
    ORG_NAME=$(echo "$VERIFY_RESPONSE" | jq -r '.organization.name')
    echo -e "Organization: ${BLUE}$ORG_NAME${NC}\n"
else
    echo -e "\n${RED}✗ API Key verification failed!${NC}"
    echo -e "${RED}Please check your API key and try again.${NC}\n"
    exit 1
fi

# Test 2: Analyze certificate (requires PDF file)
echo -e "${YELLOW}Test 2: Analyze Certificate${NC}"

# Check if test PDF exists
if [ -f "test-certificate.pdf" ]; then
    echo -e "${GREEN}Found test-certificate.pdf, analyzing...${NC}\n"
    
    ANALYSIS_RESPONSE=$(curl -s -X POST "$BASE_URL/fraud-detection/analyze" \
      -H "X-Organization-Key: $API_KEY" \
      -F "document=@test-certificate.pdf" \
      -F "document_type=certificate" \
      -F "issuer_name=Test University" \
      -F "holder_name=John Doe" \
      -F "issue_date=2025-06-15")
    
    echo "$ANALYSIS_RESPONSE" | jq '.'
    
    SUCCESS=$(echo "$ANALYSIS_RESPONSE" | jq -r '.success // false')
    
    if [ "$SUCCESS" == "true" ]; then
        echo -e "\n${GREEN}✓ Certificate analyzed successfully!${NC}\n"
        
        FRAUD_SCORE=$(echo "$ANALYSIS_RESPONSE" | jq -r '.analysis.fraud_score // "N/A"')
        RISK_LEVEL=$(echo "$ANALYSIS_RESPONSE" | jq -r '.analysis.risk_level // "N/A"')
        
        echo -e "Fraud Score: ${BLUE}$FRAUD_SCORE/100${NC}"
        echo -e "Risk Level: ${BLUE}$RISK_LEVEL${NC}\n"
    else
        echo -e "\n${RED}✗ Analysis failed!${NC}"
        ERROR=$(echo "$ANALYSIS_RESPONSE" | jq -r '.error // "Unknown error"')
        echo -e "${RED}Error: $ERROR${NC}\n"
    fi
else
    echo -e "${RED}test-certificate.pdf not found!${NC}"
    echo -e "${YELLOW}Please create a test certificate PDF first.${NC}\n"
    echo -e "To test with a custom PDF file, run:"
    echo -e "${GREEN}curl -X POST \"$BASE_URL/fraud-detection/analyze\" \\${NC}"
    echo -e "${GREEN}  -H \"X-Organization-Key: $API_KEY\" \\${NC}"
    echo -e "${GREEN}  -F \"document=@your-certificate.pdf\" \\${NC}"
    echo -e "${GREEN}  -F \"document_type=certificate\" \\${NC}"
    echo -e "${GREEN}  -F \"issuer_name=University Name\" \\${NC}"
    echo -e "${GREEN}  -F \"holder_name=Student Name\" \\${NC}"
    echo -e "${GREEN}  -F \"issue_date=2025-06-15\" | jq '.'${NC}\n"
fi

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
