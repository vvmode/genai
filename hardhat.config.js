import "@nomicfoundation/hardhat-toolbox";
import dotenv from "dotenv";

// Load environment variables from .env for local development
dotenv.config();

// Railway injects environment variables directly
// No need for dotenv in production (it's already loaded above)

// Get RPC URL from environment with proper fallbacks
const getRpcUrl = () => {
  const url = process.env.BLOCKCHAIN_RPC_URL || 
              process.env.SEPOLIA_RPC_URL;
  
  if (!url) {
    console.log("⚠️  No RPC URL found in environment variables");
    return "https://ethereum-sepolia-rpc.publicnode.com";
  }
  
  console.log(`✅ Using RPC URL: ${url.substring(0, 50)}...`);
  return url;
};

// Get private key from environment
const getPrivateKey = () => {
  const key = process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY || process.env.PRIVATE_KEY;
  
  if (!key) {
    console.log("⚠️  No private key found in environment variables");
    return [];
  }
  
  console.log(`✅ Private key loaded: ${key.substring(0, 10)}...`);
  return [key];
};

/** @type import('hardhat/config').HardhatUserConfig */
export default {
  solidity: {
    version: "0.8.19",
    settings: {
      optimizer: {
        enabled: true,
        runs: 200
      },
      viaIR: true  // Enable IR-based code generation for complex contracts
    }
  },
  networks: {
    // Sepolia Testnet
    sepolia: {
      url: getRpcUrl(),
      accounts: getPrivateKey(),
      chainId: 11155111,
      timeout: 60000 // 60 second timeout
    },
    // Polygon Mumbai Testnet
    mumbai: {
      url: "https://polygon-mumbai.infura.io/v3/YOUR_INFURA_KEY",
      accounts: process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY 
        ? [process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY]
        : [],
      chainId: 80001
    },
    // Polygon Mainnet
    polygon: {
      url: "https://polygon-mainnet.infura.io/v3/YOUR_INFURA_KEY",
      accounts: process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY 
        ? [process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY]
        : [],
      chainId: 137
    },
    // Ethereum Mainnet
    mainnet: {
      url: "https://mainnet.infura.io/v3/YOUR_INFURA_KEY",
      accounts: process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY 
        ? [process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY]
        : [],
      chainId: 1
    },
    // Local Hardhat Network
    localhost: {
      url: "http://127.0.0.1:8545"
    }
  },
  paths: {
    sources: "./contracts",
    tests: "./test",
    cache: "./cache",
    artifacts: "./artifacts"
  },
  etherscan: {
    apiKey: process.env.ETHERSCAN_API_KEY || ""
  }
};
