#!/bin/bash

# TrustChain API Setup Verification Script
# This script checks if all components are properly configured

echo "🔍 TrustChain API Setup Verification"
echo "===================================="
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check counter
CHECKS_PASSED=0
CHECKS_FAILED=0

# Function to check and report
check_requirement() {
    if [ $2 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $1"
        ((CHECKS_PASSED++))
    else
        echo -e "${RED}✗${NC} $1"
        ((CHECKS_FAILED++))
    fi
}

# Check PHP version
echo "Checking PHP..."
php -v > /dev/null 2>&1
check_requirement "PHP is installed" $?

PHP_VERSION=$(php -r 'echo PHP_VERSION;' 2>/dev/null)
if [ $? -eq 0 ]; then
    if [ "$(printf '%s\n' "8.1" "$PHP_VERSION" | sort -V | head -n1)" = "8.1" ]; then
        check_requirement "PHP version >= 8.1 ($PHP_VERSION)" 0
    else
        check_requirement "PHP version >= 8.1 (found $PHP_VERSION)" 1
    fi
fi

echo ""

# Check Composer
echo "Checking Composer..."
composer --version > /dev/null 2>&1
check_requirement "Composer is installed" $?

echo ""

# Check .env file
echo "Checking Environment Configuration..."
if [ -f .env ]; then
    check_requirement ".env file exists" 0
    
    # Check important env variables
    if grep -q "BLOCKCHAIN_RPC_URL=" .env && ! grep -q "BLOCKCHAIN_RPC_URL=$" .env; then
        check_requirement "BLOCKCHAIN_RPC_URL is configured" 0
    else
        check_requirement "BLOCKCHAIN_RPC_URL is configured" 1
        echo -e "  ${YELLOW}→${NC} Set your RPC URL in .env"
    fi
    
    if grep -q "BLOCKCHAIN_WALLET_ADDRESS=" .env && ! grep -q "BLOCKCHAIN_WALLET_ADDRESS=$" .env && ! grep -q "BLOCKCHAIN_WALLET_ADDRESS=0x0000000000000000000000000000000000000000" .env; then
        check_requirement "BLOCKCHAIN_WALLET_ADDRESS is configured" 0
    else
        check_requirement "BLOCKCHAIN_WALLET_ADDRESS is configured" 1
        echo -e "  ${YELLOW}→${NC} Set your wallet address in .env"
    fi
    
    if grep -q "BLOCKCHAIN_WALLET_PRIVATE_KEY=" .env && ! grep -q "BLOCKCHAIN_WALLET_PRIVATE_KEY=$" .env; then
        check_requirement "BLOCKCHAIN_WALLET_PRIVATE_KEY is configured" 0
    else
        check_requirement "BLOCKCHAIN_WALLET_PRIVATE_KEY is configured" 1
        echo -e "  ${YELLOW}→${NC} Set your wallet private key in .env"
    fi
    
    if grep -q "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=" .env && ! grep -q "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=$" .env && ! grep -q "DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x0000000000000000000000000000000000000000" .env; then
        check_requirement "DOCUMENT_REGISTRY_CONTRACT_ADDRESS is configured" 0
    else
        check_requirement "DOCUMENT_REGISTRY_CONTRACT_ADDRESS is configured" 1
        echo -e "  ${YELLOW}→${NC} Deploy your smart contract and set the address in .env"
    fi
    
else
    check_requirement ".env file exists" 1
    echo -e "  ${YELLOW}→${NC} Run: cp .env.example.blockchain .env"
fi

echo ""

# Check vendor directory
echo "Checking Dependencies..."
if [ -d "vendor" ]; then
    check_requirement "Composer dependencies installed" 0
else
    check_requirement "Composer dependencies installed" 1
    echo -e "  ${YELLOW}→${NC} Run: composer install"
fi

echo ""

# Check database connection
echo "Checking Database..."
php artisan migrate:status > /dev/null 2>&1
check_requirement "Database connection works" $?

echo ""

# Check required directories
echo "Checking Directories..."
if [ -d "storage/app/contracts" ]; then
    check_requirement "Contract ABI directory exists" 0
else
    check_requirement "Contract ABI directory exists" 1
    echo -e "  ${YELLOW}→${NC} Directory created automatically"
fi

if [ -d "storage/app/documents" ]; then
    check_requirement "Documents storage directory exists" 0
else
    check_requirement "Documents storage directory exists" 1
    echo -e "  ${YELLOW}→${NC} Run: mkdir -p storage/app/documents"
fi

echo ""

# Check contract ABI files
echo "Checking Smart Contract ABIs..."
if [ -f "storage/app/contracts/DocumentRegistry.json" ]; then
    check_requirement "DocumentRegistry.json exists" 0
else
    check_requirement "DocumentRegistry.json exists" 1
fi

if [ -f "storage/app/contracts/IssuerRegistry.json" ]; then
    check_requirement "IssuerRegistry.json exists" 0
else
    check_requirement "IssuerRegistry.json exists" 1
fi

echo ""

# Check key PHP extensions
echo "Checking PHP Extensions..."
php -m | grep -q "pdo_mysql"
check_requirement "PDO MySQL extension" $?

php -m | grep -q "gmp"
if [ $? -eq 0 ]; then
    check_requirement "GMP extension (required for Web3)" 0
else
    check_requirement "GMP extension (required for Web3)" 1
    echo -e "  ${YELLOW}→${NC} Install: sudo apt-get install php-gmp (Linux) or via php.ini (Windows)"
fi

php -m | grep -q "bcmath"
if [ $? -eq 0 ]; then
    check_requirement "BCMath extension (required for Web3)" 0
else
    check_requirement "BCMath extension (required for Web3)" 1
    echo -e "  ${YELLOW}→${NC} Install: sudo apt-get install php-bcmath (Linux) or enable in php.ini (Windows)"
fi

echo ""

# Summary
echo "===================================="
echo "Summary:"
echo -e "${GREEN}Passed: $CHECKS_PASSED${NC}"
echo -e "${RED}Failed: $CHECKS_FAILED${NC}"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All checks passed! Your setup looks good.${NC}"
    echo ""
    echo "Next steps:"
    echo "1. Make sure you have test ETH in your wallet"
    echo "2. Deploy the smart contract if you haven't already"
    echo "3. Start the server: php artisan serve"
    echo "4. Test the API with the Postman collection"
else
    echo -e "${YELLOW}⚠ Some checks failed. Please fix the issues above.${NC}"
    echo ""
    echo "Quick fixes:"
    echo "1. Copy environment file: cp .env.example.blockchain .env"
    echo "2. Install dependencies: composer install"
    echo "3. Configure .env with your blockchain settings"
    echo "4. Run migrations: php artisan migrate"
fi

echo ""
