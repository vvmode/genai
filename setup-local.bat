@echo off
echo ========================================
echo  TrustChain Local Blockchain Setup
echo ========================================
echo.

echo Step 1: Checking if Hardhat node is running...
netstat -ano | findstr :8545 >nul
if %errorlevel% equ 0 (
    echo [OK] Port 8545 is in use - Node might be running
    echo.
    echo If you need to restart the node:
    echo   1. Press Ctrl+C
    echo   2. Run: npx hardhat node
    echo   3. Then run this script again
    pause
    exit /b
) else (
    echo [X] No node running on port 8545
    echo.
    echo Please start the Hardhat node first:
    echo   npx hardhat node
    echo.
    echo Then run this script again in a new terminal
    pause
    exit /b
)

echo.
echo Step 2: Deploying contract to localhost...
call npx hardhat run scripts/deploy.js --network localhost

if %errorlevel% neq 0 (
    echo.
    echo [X] Deployment failed!
    pause
    exit /b 1
)

echo.
echo ========================================
echo  Deployment Complete!
echo ========================================
echo.
echo Now update your .env file with:
echo   BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
echo   BLOCKCHAIN_WALLET_PRIVATE_KEY=0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80
echo.
echo And copy the contract address shown above to:
echo   DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x...
echo.
echo Then test with:
echo   php test-blockchain-api.php
echo.
pause
