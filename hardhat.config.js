import "@nomicfoundation/hardhat-toolbox";
import "dotenv/config";

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
      url: process.env.BLOCKCHAIN_RPC_URL || process.env.SEPOLIA_RPC_URL || "https://ethereum-sepolia-rpc.publicnode.com",
      accounts: process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY || process.env.PRIVATE_KEY
        ? [process.env.BLOCKCHAIN_WALLET_PRIVATE_KEY || process.env.PRIVATE_KEY]
        : [],
      chainId: 11155111
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
