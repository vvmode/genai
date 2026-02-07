import "@nomicfoundation/hardhat-toolbox";

// Load from .env file if it exists (local development)
try {
  await import("dotenv/config");
} catch (e) {
  // Railway injects variables directly, no .env needed
}

// Get RPC URL from environment with proper fallbacks
const getRpcUrl = () => {
  return process.env.BLOCKCHAIN_RPC_URL || 
         process.env.SEPOLIA_RPC_URL || 
         "https://ethereum-sepolia-rpc.publicnode.com";
};

// Get private key from environment
const getPrivateKey = () => {
  const key = process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY || process.env.PRIVATE_KEY;
  return key ? [key] : [];
};

/** @type import('hardhat/config').HardhatUserConfig */
export default {
  solidity: {
    version: "0.8.19",
    settings: {
      optimizer: {
        enabled: true,
        runs: 200
      }
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
