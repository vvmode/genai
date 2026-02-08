#!/bin/bash

# Test Simple Blockchain API
# Tests the new 3-field JSON format

BASE_URL="https://web-production-ef55e.up.railway.app/api"
# BASE_URL="http://localhost:8000/api"  # Uncomment for local testing

# Color codes
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Simple Blockchain API Test${NC}"
echo -e "${BLUE}========================================${NC}\n"

# Test: Register document with 3-field format
echo -e "${YELLOW}Registering document with simple format...${NC}\n"

curl -X POST "$BASE_URL/blockchain/document" \
  -H "Content-Type: application/json" \
  -d '{
    "document_uuid": "123",
    "file_hash": "abcdef1234567890abcdef1234567890abcdef1234567890abcdef1234567890",
    "metadata_hash": "fedcba0987654321fedcba0987654321fedcba0987654321fedcba0987654321"
  }' \
  | jq '.'

echo -e "\n${GREEN}✓ Request completed${NC}\n"
echo -e "${BLUE}Check transaction on Etherscan using the explorer_url from response${NC}\n"
