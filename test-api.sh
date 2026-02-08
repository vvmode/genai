#!/bin/bash

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Configuration
BASE_URL="http://localhost:8000"
BASE_URL_PROD="https://web-production-ef55e.up.railway.app"

# Use production or local
USE_PROD=false

if [ "$1" == "prod" ]; then
    USE_PROD=true
    API_URL="$BASE_URL_PROD"
    echo -e "${YELLOW}Using PRODUCTION: $API_URL${NC}\n"
else
    API_URL="$BASE_URL"
    echo -e "${YELLOW}Using LOCAL: $API_URL${NC}\n"
fi

# Test 1: Health Check
echo -e "${GREEN}=== Test 1: Health Check ===${NC}"
curl -s "$API_URL/api/health" | jq '.'
echo -e "\n"

# Test 2: V1 Registration (Local Storage)
echo -e "${GREEN}=== Test 2: V1 Registration (Local Storage) ===${NC}"
V1_RESPONSE=$(curl -s -X POST "$API_URL/api/document" \
  -H "Content-Type: application/json" \
  -d @test-hybrid-register.json)
echo "$V1_RESPONSE" | jq '.'
V1_DOC_ID=$(echo "$V1_RESPONSE" | jq -r '.data.document_id')
echo -e "${YELLOW}V1 Document ID: $V1_DOC_ID${NC}\n"

# Test 3: V2 Registration (Hybrid: IPFS + Blockchain)
echo -e "${GREEN}=== Test 3: V2 Registration (Hybrid Storage) ===${NC}"
V2_RESPONSE=$(curl -s -X POST "$API_URL/api/v2/document" \
  -H "Content-Type: application/json" \
  -d @test-hybrid-register.json)
echo "$V2_RESPONSE" | jq '.'
V2_DOC_ID=$(echo "$V2_RESPONSE" | jq -r '.data.document_id')
ENCRYPTION_KEY=$(echo "$V2_RESPONSE" | jq -r '.data.encryption_key')
IPFS_HASH=$(echo "$V2_RESPONSE" | jq -r '.data.ipfs.hash')
TX_HASH=$(echo "$V2_RESPONSE" | jq -r '.data.blockchain.transaction_hash')

echo -e "${YELLOW}V2 Document ID: $V2_DOC_ID${NC}"
echo -e "${YELLOW}Encryption Key: $ENCRYPTION_KEY${NC}"
echo -e "${YELLOW}IPFS Hash: $IPFS_HASH${NC}"
echo -e "${YELLOW}TX Hash: $TX_HASH${NC}\n"

# Save encryption key for later
echo "$ENCRYPTION_KEY" > .test-encryption-key.txt

# Test 4: V1 Verification
echo -e "${GREEN}=== Test 4: V1 Verification ===${NC}"
curl -s -X POST "$API_URL/api/document" \
  -H "Content-Type: application/json" \
  -d "{\"action\":\"verify\",\"document_id\":\"$V1_DOC_ID\"}" | jq '.'
echo -e "\n"

# Test 5: V2 Verification (Metadata Only)
echo -e "${GREEN}=== Test 5: V2 Verification (Metadata Only - No Key) ===${NC}"
curl -s -X POST "$API_URL/api/v2/document" \
  -H "Content-Type: application/json" \
  -d "{\"action\":\"verify\",\"document_id\":\"$V2_DOC_ID\"}" | jq '.'
echo -e "\n"

# Test 6: V2 Verification (With PDF Retrieval)
echo -e "${GREEN}=== Test 6: V2 Verification (With PDF - Using Key) ===${NC}"
curl -s -X POST "$API_URL/api/v2/document" \
  -H "Content-Type: application/json" \
  -d "{\"action\":\"verify\",\"document_id\":\"$V2_DOC_ID\",\"encryption_key\":\"$ENCRYPTION_KEY\"}" | jq '{success, verified: .data.verified, pdf_retrieved: .data.pdf_retrieved, storage_model, note}'
echo -e "\n"

# Test 7: Check IPFS Direct Access
if [ ! -z "$IPFS_HASH" ] && [ "$IPFS_HASH" != "null" ]; then
    echo -e "${GREEN}=== Test 7: IPFS Direct Access ===${NC}"
    echo -e "${YELLOW}IPFS Gateway URL: https://gateway.pinata.cloud/ipfs/$IPFS_HASH${NC}"
    echo "Fetching first 100 bytes from IPFS..."
    curl -s "https://gateway.pinata.cloud/ipfs/$IPFS_HASH" | head -c 100
    echo -e "\n${YELLOW}(This is encrypted data - should be unreadable)${NC}\n"
fi

# Test 8: Check Transaction on Etherscan
if [ ! -z "$TX_HASH" ] && [ "$TX_HASH" != "null" ]; then
    echo -e "${GREEN}=== Test 8: Blockchain Transaction ===${NC}"
    echo -e "${YELLOW}Etherscan URL: https://sepolia.etherscan.io/tx/$TX_HASH${NC}\n"
fi

# Test 9: Error Handling - Invalid Document
echo -e "${GREEN}=== Test 9: Error Handling (Non-existent Document) ===${NC}"
curl -s -X POST "$API_URL/api/v2/document" \
  -H "Content-Type: application/json" \
  -d '{"action":"verify","document_id":"INVALID-DOC-999"}' | jq '.'
echo -e "\n"

# Test 10: Error Handling - Wrong Encryption Key
echo -e "${GREEN}=== Test 10: Error Handling (Wrong Encryption Key) ===${NC}"
curl -s -X POST "$API_URL/api/v2/document" \
  -H "Content-Type: application/json" \
  -d "{\"action\":\"verify\",\"document_id\":\"$V2_DOC_ID\",\"encryption_key\":\"d3JvbmdLZXkxMjM0NTY3ODkwMTIzNDU2Nzg5MDEyMzQ=\"}" | jq '.'
echo -e "\n"

echo -e "${GREEN}=== All Tests Complete ===${NC}"
echo -e "${YELLOW}Encryption key saved to: .test-encryption-key.txt${NC}"
