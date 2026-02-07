# TrustChain Blockchain API - Quick Start Guide

## 🚀 Quick Setup (5 Minutes)

### Step 1: Environment Setup

1. Copy the blockchain environment template:
```bash
cp .env.example.blockchain .env
```

2. Edit `.env` and configure these essential values:

```env
# Database
DB_DATABASE=trustchain
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Blockchain - Get free RPC from Infura/Alchemy
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_PROJECT_ID

# Wallet - Create a new wallet for testing
BLOCKCHAIN_WALLET_ADDRESS=0xYourWalletAddress
BLOCKCHAIN_WALLET_PRIVATE_KEY=YourPrivateKey

# Etherscan (optional)
ETHERSCAN_API_KEY=YourEtherscanApiKey
```

### Step 2: Install Dependencies

```bash
composer install
php artisan key:generate
```

### Step 3: Database Setup

```bash
php artisan migrate
```

### Step 4: Deploy Smart Contract

You need to deploy the smart contract first. Here's a simple guide:

#### Option A: Using Remix IDE (Easiest)

1. Go to https://remix.ethereum.org
2. Create a new file: `DocumentRegistry.sol`
3. Copy the content from `contracts/DocumentRegistry.sol`
4. Compile the contract (Solidity 0.8.19)
5. Deploy to Sepolia Testnet using MetaMask
6. Copy the deployed contract address to your `.env`:
   ```env
   DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYourDeployedContractAddress
   ```

#### Option B: Using Hardhat (Advanced)

```bash
npm install --save-dev hardhat @nomicfoundation/hardhat-toolbox
npx hardhat init
# Follow the prompts, then deploy your contract
```

### Step 5: Start the Server

```bash
php artisan serve
```

Your API is now running at `http://localhost:8000`

## 🧪 Testing the API

### Using cURL

#### 1. Register a Document

```bash
curl -X POST http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "document=@/path/to/certificate.pdf" \
  -F "holder_name=John Doe" \
  -F "holder_email=john@example.com" \
  -F "title=Bachelor Degree" \
  -F "document_type=certificate"
```

#### 2. Verify a Document (Public - No Auth)

```bash
curl -X POST http://localhost:8000/api/documents/verify \
  -F "document=@/path/to/certificate.pdf"
```

### Using Postman

1. Import the collection: `TrustChain_API.postman_collection.json`
2. Set your `auth_token` in collection variables
3. Test the endpoints!

## 📝 Getting Test ETH

You need Sepolia testnet ETH for gas fees:

1. Go to https://sepoliafaucet.com/
2. Enter your wallet address
3. Get free test ETH

## 🔑 Authentication

The API uses Laravel Sanctum for authentication. To get an auth token:

### Create a User

```bash
php artisan tinker
```

Then in tinker:
```php
$user = User::create([
    'name' => 'Test University',
    'email' => 'test@university.edu',
    'password' => bcrypt('password123'),
    'role' => 'issuer'
]);

$token = $user->createToken('api-token')->plainTextToken;
echo $token;
```

Use this token in your API requests:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

## 📊 API Flow

### Document Registration Flow

```
1. User uploads PDF + metadata
   ↓
2. System generates SHA-256 hash
   ↓
3. Document saved to database (status: pending)
   ↓
4. Smart contract called to register on blockchain
   ↓
5. Transaction submitted (returns tx_hash)
   ↓
6. Check status endpoint to see when confirmed
   ↓
7. Status changes to "confirmed" once mined
```

### Document Verification Flow

```
1. Verifier uploads PDF or provides document ID
   ↓
2. System computes hash / looks up document
   ↓
3. Checks database for document
   ↓
4. Verifies status (valid/revoked/expired)
   ↓
5. Optionally verifies on blockchain
   ↓
6. Returns verification result
```

## 🎯 Key Endpoints

| Endpoint | Method | Auth | Purpose |
|----------|--------|------|---------|
| `/api/documents` | POST | ✅ | Register new document |
| `/api/documents/verify` | POST | ❌ | Verify document (public) |
| `/api/documents/{uuid}` | GET | ✅ | Get document details |
| `/api/documents` | GET | ✅ | List all documents |
| `/api/documents/{uuid}/revoke` | POST | ✅ | Revoke document |
| `/api/documents/{uuid}/status` | GET | ✅ | Check blockchain status |

## 🔍 Document Types

- `certificate` - Educational certificates
- `experience_letter` - Employment letters
- `transcript` - Academic transcripts
- `legal_document` - Legal attestations
- `other` - Other documents

## ⚠️ Troubleshooting

### "Contract address not configured"
- Make sure you deployed the smart contract
- Update `DOCUMENT_REGISTRY_CONTRACT_ADDRESS` in `.env`

### "RPC connection failed"
- Check your RPC URL is correct
- Verify you have internet connection
- Try a different RPC provider (Alchemy, Infura, or public RPCs)

### "Insufficient funds for gas"
- Get test ETH from Sepolia faucet
- Make sure your wallet address is correct in `.env`

### "Transaction taking too long"
- Sepolia testnet can be slow
- Use the status endpoint to check transaction
- Wait 1-2 minutes for confirmation

### "File upload fails"
- Check PHP upload limits in `php.ini`:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 10M
  ```
- Restart PHP server after changes

## 🛡️ Security Notes

⚠️ **NEVER commit your `.env` file to git**

⚠️ **Use a dedicated wallet for the application**

⚠️ **Store private keys securely in production**

⚠️ **Use HTTPS in production**

## 📚 Next Steps

1. ✅ Set up authentication system
2. ✅ Create issuer dashboard
3. ✅ Add document holder portal
4. ✅ Implement QR code generation
5. ✅ Add PDF parsing with AI
6. ✅ Deploy to production

## 🆘 Need Help?

- Read the full documentation: `API_DOCUMENTATION.md`
- Check Laravel docs: https://laravel.com/docs
- Check Web3.php docs: https://github.com/web3p/web3.php

## 🎉 That's It!

Your blockchain API is now ready to:
- ✅ Register documents on blockchain
- ✅ Verify document authenticity
- ✅ Revoke compromised documents
- ✅ Track all verifications
- ✅ Provide immutable proof of existence

Happy building! 🚀
