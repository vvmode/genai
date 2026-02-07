# TrustChain Blockchain API

This is the blockchain API for TrustChain - a universal document verification and digital attestation platform.

## Features

- **Document Registration**: Upload PDF documents and register them on the blockchain
- **Document Verification**: Verify document authenticity using file upload, document ID, or verification code
- **Document Revocation**: Revoke documents when needed
- **Blockchain Integration**: Full integration with Ethereum-compatible blockchains (Sepolia testnet by default)
- **Cryptographic Hashing**: SHA-256 hashing for document integrity

## Prerequisites

- PHP 8.1 or higher
- Composer
- Laravel 10.x
- MySQL or PostgreSQL database
- Access to an Ethereum RPC endpoint (Infura, Alchemy, or your own node)
- Web3 PHP library

## Installation

1. Clone the repository and install dependencies:
```bash
composer install
```

2. Copy the environment file and configure it:
```bash
cp .env.example .env
```

3. Configure your `.env` file with blockchain settings:
```env
# Blockchain Configuration
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_CHAIN_ID=11155111
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_INFURA_KEY
BLOCKCHAIN_EXPLORER_URL=https://sepolia.etherscan.io

# Wallet Configuration (for signing transactions)
BLOCKCHAIN_WALLET_ADDRESS=0xYourWalletAddress
BLOCKCHAIN_WALLET_PRIVATE_KEY=YourPrivateKey

# Smart Contract Addresses
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYourContractAddress
ISSUER_REGISTRY_CONTRACT_ADDRESS=0xYourIssuerRegistryAddress

# Etherscan API (optional, for blockchain explorer features)
ETHERSCAN_API_KEY=YourEtherscanApiKey
ETHERSCAN_BASE_URL=https://api-sepolia.etherscan.io/api

# Gas Configuration
BLOCKCHAIN_GAS_LIMIT=300000
BLOCKCHAIN_GAS_PRICE_GWEI=20
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Run migrations:
```bash
php artisan migrate
```

6. Start the development server:
```bash
php artisan serve
```

## API Endpoints

### Authentication

Most document management endpoints require authentication using Laravel Sanctum. Include the bearer token in your requests:

```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

### Document Endpoints

#### 1. Register Document (Store on Blockchain)

**Endpoint:** `POST /api/documents`

**Authentication:** Required

**Request:**
```bash
POST /api/documents
Content-Type: multipart/form-data
Authorization: Bearer YOUR_TOKEN

Form Data:
- document: [PDF file]
- holder_name: "John Doe"
- holder_email: "john@example.com"
- title: "Bachelor's Degree Certificate"
- document_type: "certificate" | "experience_letter" | "transcript" | "legal_document" | "other"
- expiry_date: "2025-12-31" (optional)
- metadata[institution_name]: "MIT" (optional)
- metadata[issue_date]: "2024-05-15" (optional)
- metadata[certificate_number]: "CERT-12345" (optional)
```

**Response:**
```json
{
  "success": true,
  "message": "Document registered successfully",
  "data": {
    "document_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "document_id": "0x1234567890abcdef...",
    "file_hash": "a3b2c1d4e5f6...",
    "blockchain_tx_hash": "0xabcdef123456...",
    "blockchain_status": "pending",
    "explorer_url": "https://sepolia.etherscan.io/tx/0xabcdef..."
  }
}
```

#### 2. Verify Document

**Endpoint:** `POST /api/documents/verify`

**Authentication:** Not required (public endpoint)

**Method 1: Verify by File Upload**
```bash
POST /api/documents/verify
Content-Type: multipart/form-data

Form Data:
- document: [PDF file]
```

**Method 2: Verify by Document ID**
```bash
POST /api/documents/verify
Content-Type: application/json

{
  "document_id": "0x1234567890abcdef..."
}
```

**Method 3: Verify by Verification Code (UUID)**
```bash
POST /api/documents/verify
Content-Type: application/json

{
  "verification_code": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response (Valid Document):**
```json
{
  "success": true,
  "status": "valid",
  "message": "Document is authentic and valid",
  "data": {
    "document_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "document_id": "0x1234567890abcdef...",
    "title": "Bachelor's Degree Certificate",
    "document_type": "certificate",
    "holder_name": "John Doe",
    "issuer": {
      "name": "MIT Admissions",
      "email": "admissions@mit.edu"
    },
    "issue_date": "2024-05-15T10:30:00.000000Z",
    "expiry_date": null,
    "blockchain_tx_hash": "0xabcdef123456...",
    "blockchain_status": "confirmed",
    "blockchain_verified": true,
    "metadata": {
      "institution_name": "MIT",
      "certificate_number": "CERT-12345"
    },
    "explorer_url": "https://sepolia.etherscan.io/tx/0xabcdef..."
  }
}
```

**Response (Invalid/Revoked/Expired):**
```json
{
  "success": true,
  "status": "revoked",
  "message": "This document has been revoked",
  "data": {
    "document_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "title": "Bachelor's Degree Certificate",
    "revoked_at": "2024-06-01T15:20:00.000000Z",
    "revoked_reason": "Information correction required"
  }
}
```

#### 3. Get Document Details

**Endpoint:** `GET /api/documents/{uuid}`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "document_id": "0x1234567890abcdef...",
    "title": "Bachelor's Degree Certificate",
    "document_type": "certificate",
    "holder_name": "John Doe",
    "holder_email": "john@example.com",
    "file_hash": "a3b2c1d4e5f6...",
    "blockchain_tx_hash": "0xabcdef123456...",
    "blockchain_status": "confirmed",
    "is_revoked": false,
    "metadata": {},
    "created_at": "2024-05-15T10:30:00.000000Z",
    "verifications_count": 15
  }
}
```

#### 4. List All Documents

**Endpoint:** `GET /api/documents`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "uuid": "550e8400-e29b-41d4-a716-446655440000",
      "title": "Bachelor's Degree Certificate",
      "document_type": "certificate",
      "blockchain_status": "confirmed",
      "created_at": "2024-05-15T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 45,
    "last_page": 3
  }
}
```

#### 5. Revoke Document

**Endpoint:** `POST /api/documents/{uuid}/revoke`

**Authentication:** Required (only document issuer)

**Request:**
```json
{
  "reason": "Information correction required"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Document revoked successfully",
  "data": {
    "document_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "revoked_at": "2024-06-01T15:20:00.000000Z",
    "blockchain_tx_hash": "0xdef456789abc..."
  }
}
```

#### 6. Check Transaction Status

**Endpoint:** `GET /api/documents/{uuid}/status`

**Authentication:** Required

**Response:**
```json
{
  "success": true,
  "data": {
    "document_uuid": "550e8400-e29b-41d4-a716-446655440000",
    "blockchain_status": "confirmed",
    "block_number": 12345678,
    "tx_hash": "0xabcdef123456...",
    "explorer_url": "https://sepolia.etherscan.io/tx/0xabcdef..."
  }
}
```

### Blockchain Information Endpoints

These endpoints provide information about the blockchain network:

- `GET /api/blockchain/balance?address=0x...` - Get ETH balance
- `GET /api/blockchain/gas-price` - Get current gas prices
- `GET /api/blockchain/eth-price` - Get current ETH price
- `GET /api/blockchain/tx/{txHash}` - Get transaction details
- `GET /api/blockchain/block/{blockNumber}` - Get block details

## Document Types

The following document types are supported:

- `certificate` - Educational certificates
- `experience_letter` - Employment experience letters
- `transcript` - Academic transcripts
- `legal_document` - Legal documents attested by lawyers
- `other` - Other document types

## Document Status

Documents can have the following statuses:

- `pending` - Transaction submitted but not yet confirmed
- `confirmed` - Transaction confirmed on blockchain
- `failed` - Transaction failed

## Verification Results

Document verification can return the following statuses:

- `valid` - Document is authentic and valid
- `invalid` - Document content doesn't match records
- `not_found` - Document not found in records
- `revoked` - Document has been revoked
- `expired` - Document has expired

## Error Responses

All API endpoints return error responses in the following format:

```json
{
  "success": false,
  "message": "Error description",
  "error": "Detailed error message (only in debug mode)"
}
```

## Security Considerations

1. **Private Keys**: Never commit private keys to version control
2. **Environment Variables**: Store all sensitive data in `.env` file
3. **File Storage**: Documents are stored locally by default - configure secure storage for production
4. **Authentication**: Use Laravel Sanctum tokens for authenticated endpoints
5. **Rate Limiting**: Implement rate limiting for public verification endpoints
6. **HTTPS**: Always use HTTPS in production

## Blockchain Deployment

### Deploy Smart Contracts

1. Install Hardhat or Truffle for contract deployment
2. Deploy the `DocumentRegistry.sol` contract to your chosen network
3. Update the contract address in `.env`
4. Ensure your wallet has sufficient ETH for gas fees

### Example using Hardhat:

```bash
npx hardhat run scripts/deploy.js --network sepolia
```

## Testing

Run the test suite:

```bash
php artisan test
```

## Troubleshooting

### Common Issues

1. **Transaction fails**: Check gas limit and gas price settings
2. **RPC connection errors**: Verify your RPC URL is correct and accessible
3. **Contract not found**: Ensure contract address is deployed and correct
4. **File upload fails**: Check file size limits and storage permissions

## Production Checklist

- [ ] Configure secure file storage (S3, etc.)
- [ ] Set up proper database backups
- [ ] Configure queue workers for background jobs
- [ ] Set up monitoring and logging
- [ ] Implement rate limiting
- [ ] Configure CORS properly
- [ ] Use production blockchain network
- [ ] Secure private keys in vault
- [ ] Enable HTTPS
- [ ] Set up CDN for static assets

## Support

For issues and questions, please open an issue on GitHub.

## License

This project is licensed under the MIT License.
