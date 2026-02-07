# Hardhat Smart Contract Deployment Guide

## 📋 Prerequisites

1. Node.js and npm installed
2. Hardhat installed (already done)
3. Private key in `.env` file
4. Test ETH in your wallet

## 🚀 Quick Start

### 1. Configure Your Environment

Make sure your `.env` has these variables:

```env
BLOCKCHAIN_RPC_URL=https://sepolia.infura.io/v3/YOUR_INFURA_KEY
BLOCKCHAIN_WALLET_PRIVATE_KEY=your_private_key_here
ETHERSCAN_API_KEY=your_etherscan_key (optional, for verification)
```

### 2. Compile the Smart Contract

```bash
npx hardhat compile
```

This will compile `contracts/DocumentRegistry.sol`

### 3. Run Tests (Optional but Recommended)

```bash
npx hardhat test
```

### 4. Deploy to Network

**Deploy to Sepolia Testnet:**
```bash
npm run deploy:sepolia
```

**Deploy to Local Network:**
```bash
# Terminal 1: Start local node
npx hardhat node

# Terminal 2: Deploy
npm run deploy:local
```

**Deploy to Polygon Mumbai:**
```bash
npm run deploy:polygon
```

## 📝 After Deployment

1. **Copy the contract address** from the output
2. **Update your `.env`:**
   ```env
   DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYourContractAddress
   ```
3. **Copy the ABI:**
   ```bash
   cp artifacts/contracts/DocumentRegistry.sol/DocumentRegistry.json storage/app/contracts/
   ```

## 🔍 Verify Contract on Etherscan

After deployment, verify your contract:

```bash
npx hardhat verify --network sepolia YOUR_CONTRACT_ADDRESS
```

## 📊 Useful Commands

```bash
# Compile contracts
npx hardhat compile

# Run tests
npx hardhat test

# Run tests with gas report
REPORT_GAS=true npx hardhat test

# Clean artifacts
npx hardhat clean

# Check contract size
npx hardhat size-contracts

# Run local blockchain node
npx hardhat node

# Deploy to specific network
npx hardhat run scripts/deploy.js --network sepolia
```

## 🌐 Network Configuration

The `hardhat.config.js` includes these networks:

- **sepolia** - Ethereum Sepolia Testnet
- **mumbai** - Polygon Mumbai Testnet
- **polygon** - Polygon Mainnet
- **mainnet** - Ethereum Mainnet
- **localhost** - Local Hardhat Network

## 🎯 Deployment Output

After successful deployment, you'll see:

```
🚀 Starting TrustChain Smart Contract Deployment...

📋 Deployment Details:
   Network: sepolia
   Deployer: 0xYourAddress
   Balance: 1.5 ETH

📄 Deploying DocumentRegistry contract...
✅ DocumentRegistry deployed to: 0xContractAddress
⏳ Waiting for block confirmations...
✅ Confirmed!

💾 Deployment info saved to: deployments/sepolia.json

📝 UPDATE YOUR .env FILE:
──────────────────────────────────────────────────────────────────────
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xContractAddress
──────────────────────────────────────────────────────────────────────

🎉 Deployment Complete!
```

## 🐛 Troubleshooting

### Error: "Insufficient funds"
- Get test ETH from faucet:
  - Sepolia: https://sepoliafaucet.com
  - Mumbai: https://faucet.polygon.technology

### Error: "Invalid API key"
- Check your Infura/Alchemy project ID in `.env`

### Error: "Nonce too high"
- Reset your MetaMask account or wait a few minutes

### Contract size too large
- Enable optimizer in `hardhat.config.js` (already enabled)

## 📚 Learn More

- [Hardhat Documentation](https://hardhat.org/docs)
- [Ethereum Development](https://ethereum.org/developers)
- [Solidity Documentation](https://docs.soliditylang.org)

## ✅ Checklist

- [ ] Hardhat installed
- [ ] `.env` configured with private key and RPC URL
- [ ] Smart contract compiled successfully
- [ ] Tests pass
- [ ] Contract deployed
- [ ] Contract address added to `.env`
- [ ] ABI copied to `storage/app/contracts/`
- [ ] Contract verified on Etherscan (optional)
- [ ] API tested with deployed contract
