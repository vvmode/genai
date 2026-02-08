#!/bin/bash

# Test Direct Blockchain API
# JSON → Blockchain storage

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
API_URL="${1:-http://localhost:8000}"

echo -e "${YELLOW}Testing Direct Blockchain API${NC}"
echo -e "${YELLOW}Using: $API_URL${NC}\n"

# Test 1: Register Document
echo -e "${GREEN}=== Test 1: Register Document (JSON → Blockchain) ===${NC}"
REGISTER_RESPONSE=$(curl -s -X POST "$API_URL/api/blockchain/document" \
  -H "Content-Type: application/json" \
  -d @test-direct-blockchain.json)

echo "$REGISTER_RESPONSE" | jq '.'

DOC_ID=$(echo "$REGISTER_RESPONSE" | jq -r '.data.document_id // empty')
TX_HASH=$(echo "$REGISTER_RESPONSE" | jq -r '.data.blockchain.transaction_hash // empty')
PDF_HASH=$(echo "$REGISTER_RESPONSE" | jq -r '.data.pdf_hash // empty')

if [ -z "$DOC_ID" ]; then
    echo -e "${RED}Registration failed!${NC}"
    exit 1
fi

echo -e "\n${YELLOW}Document ID: $DOC_ID${NC}"
echo -e "${YELLOW}Transaction: $TX_HASH${NC}"
echo -e "${YELLOW}PDF Hash: $PDF_HASH${NC}"
echo -e "${YELLOW}Explorer: https://sepolia.etherscan.io/tx/$TX_HASH${NC}\n"

# Wait for blockchain confirmation
echo -e "${YELLOW}Waiting 20 seconds for blockchain confirmation...${NC}"
sleep 20

# Test 2: Verify Document
echo -e "\n${GREEN}=== Test 2: Verify Document (Read from Blockchain) ===${NC}"
VERIFY_RESPONSE=$(curl -s -X POST "$API_URL/api/blockchain/document" \
  -H "Content-Type: application/json" \
  -d "{\"action\":\"verify\",\"document_id\":\"$DOC_ID\"}")

echo "$VERIFY_RESPONSE" | jq '.'

VERIFIED=$(echo "$VERIFY_RESPONSE" | jq -r '.verified // false')

if [ "$VERIFIED" == "true" ]; then
    echo -e "\n${GREEN}✅ Document verified successfully from blockchain!${NC}"
else
    echo -e "\n${YELLOW}⚠️  Document not yet confirmed on blockchain (wait longer)${NC}"
fi

# Test 3: Invalid Document
echo -e "\n${GREEN}=== Test 3: Verify Non-existent Document ===${NC}"
curl -s -X POST "$API_URL/api/blockchain/document" \
  -H "Content-Type: application/json" \
  -d '{"action":"verify","document_id":"INVALID-999"}' | jq '.'

echo -e "\n${GREEN}=== All Tests Complete ===${NC}"
