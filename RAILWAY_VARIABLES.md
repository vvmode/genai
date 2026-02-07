# Railway Environment Variables - Copy/Paste These

## Add these EXACT variable names in Railway:

```env
# Option 1: Use these variable names (preferred)
SEPOLIA_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
PRIVATE_KEY=0x75f2651ce40e8914218b3e5d8f9a2ef0e531b510692f26bc12fd156726373743

# Option 2: Or use these (also supported)
BLOCKCHAIN_RPC_URL=https://ethereum-sepolia-rpc.publicnode.com
BLOCKCHAIN_WALLET_PRIVATE_KEY=0x75f2651ce40e8914218b3e5d8f9a2ef0e531b510692f26bc12fd156726373743

# Database (SQLite - simple)
DB_CONNECTION=sqlite
DB_DATABASE=/app/database/database.sqlite

# Network info (optional but recommended)
BLOCKCHAIN_NETWORK=sepolia
BLOCKCHAIN_CHAIN_ID=11155111
BLOCKCHAIN_EXPLORER_URL=https://sepolia.etherscan.io
```

## Steps to Add in Railway:

1. Go to your Railway project
2. Click on your service
3. Go to **Variables** tab
4. Click **+ New Variable**
5. Add each variable above (name and value)
6. Click **Save**
7. Railway will auto-redeploy

## After Deployment:

Watch logs for:
```
✅ Contract deployed to: 0xABCDEF...
```

Then add:
```
DOCUMENT_REGISTRY_CONTRACT_ADDRESS=0xYourContractAddress
```

Save and redeploy one more time!
