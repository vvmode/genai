# 🔐 TrustChain - Blockchain Document Verification API

<p align="center">
  <strong>Universal Document Verification & Digital Attestation Platform</strong>
</p>

<p align="center">
  Built with Laravel 10 | Ethereum Smart Contracts | Web3.php
</p>

---

## 📋 Overview

TrustChain is a blockchain-powered API for document verification and digital attestation. It eliminates document fraud by storing cryptographic hashes on the blockchain, providing permanent, tamper-proof records of document authenticity.

### ✨ Key Features

- **🔒 Blockchain Registration** - Store document hashes on Ethereum-compatible networks
- **✅ Public Verification** - Anyone can verify document authenticity
- **🔄 Document Revocation** - Revoke compromised or incorrect documents
- **📝 Version Control** - Track document corrections with full history
- **🔍 Multiple Verification Methods** - Verify by file, document ID, or QR code
- **⚡ Fast & Secure** - SHA-256 hashing with blockchain immutability
- **🌐 Multi-network Support** - Sepolia, Polygon, Ethereum mainnet

## 🚀 Quick Start

Get started in 5 minutes! See [QUICK_START.md](QUICK_START.md) for detailed instructions.

```bash
# 1. Install dependencies
composer install

# 2. Configure environment
cp .env.example.blockchain .env
# Edit .env with your blockchain settings

# 3. Run migrations
php artisan migrate

# 4. Start server
php artisan serve
```

Then visit: `http://localhost:8000`

## 📦 What's Included

This repository contains:

```
blockchain-app/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── DocumentController.php      # Main document API
│   │   │   └── BlockchainApiController.php # Blockchain info
│   │   └── Requests/
│   │       ├── StoreDocumentRequest.php    # Validation
│   │       └── VerifyDocumentRequest.php
│   ├── Models/
│   │   ├── Document.php                    # Document model
│   │   ├── Attestation.php                 # Lawyer attestations
│   │   └── Verification.php                # Verification logs
│   └── Services/
│       ├── BlockchainService.php           # Smart contract interaction
│       ├── DocumentHashService.php         # Hashing utilities
│       └── EtherscanService.php            # Blockchain explorer
├── contracts/
│   └── DocumentRegistry.sol                # Smart contract
├── storage/app/contracts/
│   ├── DocumentRegistry.json               # Contract ABI
│   └── IssuerRegistry.json
├── routes/api.php                          # API routes
├── API_DOCUMENTATION.md                    # Full API docs
├── QUICK_START.md                          # Setup guide
└── TrustChain_API.postman_collection.json  # Postman tests
```

## 🎯 Core API Endpoints

### Document Management

| Endpoint | Method | Auth | Description |
|----------|--------|------|-------------|
| `/api/documents` | POST | ✅ | Register document on blockchain |
| `/api/documents/verify` | POST | ❌ | Verify document (public) |
| `/api/documents/{uuid}` | GET | ✅ | Get document details |
| `/api/documents/{uuid}/revoke` | POST | ✅ | Revoke document |
| `/api/documents/{uuid}/status` | GET | ✅ | Check blockchain status |

### Example: Register a Document

```bash
curl -X POST http://localhost:8000/api/documents \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "document=@certificate.pdf" \
  -F "holder_name=John Doe" \
  -F "holder_email=john@example.com" \
  -F "title=Bachelor Degree" \
  -F "document_type=certificate"
```

### Example: Verify a Document (Public)

```bash
curl -X POST http://localhost:8000/api/documents/verify \
  -F "document=@certificate.pdf"
```

## 🔧 Configuration

### Blockchain Settings (.env)

```env
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_PROJECT_ID
BLOCKCHAIN_WALLET_ADDRESS=0xYourWalletAddress
BLOCKCHAIN_WALLET_PRIVATE_KEY=YourPrivateKey
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYourContractAddress
```

### Supported Networks

- ✅ Sepolia Testnet (recommended for development)
- ✅ Polygon Mumbai Testnet
- ✅ Ethereum Mainnet
- ✅ Polygon Mainnet
- ✅ Any EVM-compatible network

## 📚 Documentation

- **[API Documentation](API_DOCUMENTATION.md)** - Complete API reference
- **[Quick Start Guide](QUICK_START.md)** - Setup in 5 minutes
- **[Postman Collection](TrustChain_API.postman_collection.json)** - Test the API

## 🛠️ Technology Stack

- **Backend**: Laravel 10.x (PHP 8.1+)
- **Blockchain**: Web3.php, Ethereum, Solidity
- **Database**: MySQL/PostgreSQL
- **Authentication**: Laravel Sanctum
- **File Processing**: PDF Parser, QR Code Generator
- **Testing**: PHPUnit

## 🔒 Security Features

- ✅ SHA-256 cryptographic hashing
- ✅ Blockchain immutability
- ✅ Token-based authentication
- ✅ Role-based access control
- ✅ Audit trail logging
- ✅ Private key encryption

- ✅ Audit trail logging
- ✅ Private key encryption

## 🧪 Testing

### Verify Setup

**Windows:**
```bash
verify-setup.bat
```

**Linux/Mac:**
```bash
chmod +x verify-setup.sh
./verify-setup.sh
```

### Run Tests

```bash
php artisan test
```

### Test with Postman

1. Import `TrustChain_API.postman_collection.json`
2. Set your auth token in collection variables
3. Run the requests!

## 📖 Use Cases

### 🎓 Education
- University degree certificates
- Academic transcripts
- Course completion certificates

### 💼 Employment
- Experience letters
- Recommendation letters
- Employment verification

### ⚖️ Legal
- Lawyer-attested documents
- Legal certificates
- Notarized documents

### 🏛️ Government
- License verification
- Official certificates
- Public records

## 🔄 How It Works

### Document Registration

```
1. User uploads PDF + metadata
   ↓
2. System generates SHA-256 hash
   ↓
3. Smart contract stores hash on blockchain
   ↓
4. Transaction confirmed & recorded
   ↓
5. Document assigned unique ID
```

### Document Verification

```
1. Verifier provides document or ID
   ↓
2. System computes/retrieves hash
   ↓
3. Checks database & blockchain
   ↓
4. Returns: Valid/Invalid/Revoked/Expired
```

## 🚦 Roadmap

- [x] Core blockchain API
- [x] Document registration & verification
- [x] Revocation system
- [ ] QR code generation
- [ ] AI-powered OCR & metadata extraction
- [ ] Lawyer attestation workflow
- [ ] Temporary share links
- [ ] Mobile SDK
- [ ] Zero-knowledge proofs
- [ ] Encrypted document vault

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🆘 Support

- **Documentation**: See [API_DOCUMENTATION.md](API_DOCUMENTATION.md)
- **Quick Start**: See [QUICK_START.md](QUICK_START.md)
- **Issues**: Open an issue on GitHub

## 🙏 Acknowledgments

Built with:
- [Laravel](https://laravel.com) - PHP Framework
- [Web3.php](https://github.com/web3p/web3.php) - Ethereum PHP Library
- [Ethereum](https://ethereum.org) - Blockchain Platform

---

<p align="center">
  Made with ❤️ for transparent, verifiable credentials
</p>

<p align="center">
  <strong>TrustChain - Because trust should be verifiable</strong>
</p>

