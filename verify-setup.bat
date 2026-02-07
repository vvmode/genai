@echo off
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   TrustChain API Setup Verification
echo ========================================
echo.

set CHECKS_PASSED=0
set CHECKS_FAILED=0

:: Check PHP
echo Checking PHP...
php -v >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] PHP is installed
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] PHP is not installed
    set /a CHECKS_FAILED+=1
)

:: Check Composer
echo.
echo Checking Composer...
composer --version >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Composer is installed
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] Composer is not installed
    set /a CHECKS_FAILED+=1
)

:: Check .env file
echo.
echo Checking Environment Configuration...
if exist .env (
    echo [OK] .env file exists
    set /a CHECKS_PASSED+=1
    
    findstr /C:"BLOCKCHAIN_RPC_URL=https" .env >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] BLOCKCHAIN_RPC_URL is configured
        set /a CHECKS_PASSED+=1
    ) else (
        echo [FAIL] BLOCKCHAIN_RPC_URL is not configured
        echo        ^-^> Set your RPC URL in .env
        set /a CHECKS_FAILED+=1
    )
    
    findstr /C:"BLOCKCHAIN_WALLET_ADDRESS=0x" .env | findstr /V "0x0000000000000000000000000000000000000000" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] BLOCKCHAIN_WALLET_ADDRESS is configured
        set /a CHECKS_PASSED+=1
    ) else (
        echo [FAIL] BLOCKCHAIN_WALLET_ADDRESS is not configured
        echo        ^-^> Set your wallet address in .env
        set /a CHECKS_FAILED+=1
    )
    
    findstr /C:"DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0x" .env | findstr /V "0x0000000000000000000000000000000000000000" >nul 2>&1
    if %errorlevel% equ 0 (
        echo [OK] DOCUMENT_REGISTRY_CONTRACT_ADDRESS is configured
        set /a CHECKS_PASSED+=1
    ) else (
        echo [FAIL] DOCUMENT_REGISTRY_CONTRACT_ADDRESS is not configured
        echo        ^-^> Deploy smart contract and set address in .env
        set /a CHECKS_FAILED+=1
    )
) else (
    echo [FAIL] .env file not found
    echo        ^-^> Run: copy .env.example.blockchain .env
    set /a CHECKS_FAILED+=1
)

:: Check vendor directory
echo.
echo Checking Dependencies...
if exist vendor (
    echo [OK] Composer dependencies installed
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] Composer dependencies not installed
    echo        ^-^> Run: composer install
    set /a CHECKS_FAILED+=1
)

:: Check database connection
echo.
echo Checking Database...
php artisan migrate:status >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Database connection works
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] Database connection failed
    echo        ^-^> Check database settings in .env
    set /a CHECKS_FAILED+=1
)

:: Check directories
echo.
echo Checking Directories...
if exist storage\app\contracts (
    echo [OK] Contract ABI directory exists
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] Contract ABI directory missing
    echo        ^-^> Directory should be created automatically
    set /a CHECKS_FAILED+=1
)

if exist storage\app\documents (
    echo [OK] Documents storage directory exists
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] Documents storage directory missing
    echo        ^-^> Run: mkdir storage\app\documents
    set /a CHECKS_FAILED+=1
)

:: Check contract ABI files
echo.
echo Checking Smart Contract ABIs...
if exist storage\app\contracts\DocumentRegistry.json (
    echo [OK] DocumentRegistry.json exists
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] DocumentRegistry.json not found
    set /a CHECKS_FAILED+=1
)

if exist storage\app\contracts\IssuerRegistry.json (
    echo [OK] IssuerRegistry.json exists
    set /a CHECKS_PASSED+=1
) else (
    echo [FAIL] IssuerRegistry.json not found
    set /a CHECKS_FAILED+=1
)

:: Summary
echo.
echo ========================================
echo Summary:
echo Passed: %CHECKS_PASSED%
echo Failed: %CHECKS_FAILED%
echo.

if %CHECKS_FAILED% equ 0 (
    echo [SUCCESS] All checks passed! Your setup looks good.
    echo.
    echo Next steps:
    echo 1. Make sure you have test ETH in your wallet
    echo 2. Deploy the smart contract if you haven't already
    echo 3. Start the server: php artisan serve
    echo 4. Test the API with the Postman collection
) else (
    echo [WARNING] Some checks failed. Please fix the issues above.
    echo.
    echo Quick fixes:
    echo 1. Copy environment file: copy .env.example.blockchain .env
    echo 2. Install dependencies: composer install
    echo 3. Configure .env with your blockchain settings
    echo 4. Run migrations: php artisan migrate
)

echo.
pause
