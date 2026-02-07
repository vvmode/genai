import hre from "hardhat";
import fs from "fs";
import path from "path";

async function main() {
  console.log("🚀 Starting TrustChain Smart Contract Deployment...\n");

  // Get network info
  const network = hre.network.name;
  const [deployer] = await hre.ethers.getSigners();
  
  console.log("📋 Deployment Details:");
  console.log("   Network:", network);
  console.log("   Deployer:", deployer.address);
  console.log("   Balance:", hre.ethers.formatEther(await hre.ethers.provider.getBalance(deployer.address)), "ETH\n");

  // Deploy DocumentRegistry
  console.log("📄 Deploying DocumentRegistry contract...");
  const DocumentRegistry = await hre.ethers.getContractFactory("DocumentRegistry");
  const documentRegistry = await DocumentRegistry.deploy();
  await documentRegistry.waitForDeployment();
  
  const documentRegistryAddress = await documentRegistry.getAddress();
  console.log("✅ DocumentRegistry deployed to:", documentRegistryAddress);

  // Wait for a few block confirmations
  console.log("⏳ Waiting for block confirmations...");
  await documentRegistry.deploymentTransaction().wait(5);
  console.log("✅ Confirmed!\n");

  // Save deployment info
  const deploymentInfo = {
    network: network,
    chainId: (await hre.ethers.provider.getNetwork()).chainId.toString(),
    deployer: deployer.address,
    contracts: {
      DocumentRegistry: {
        address: documentRegistryAddress,
        deployedAt: new Date().toISOString()
      }
    }
  };

  // Save to JSON file
  const deploymentsDir = path.join(__dirname, "../deployments");
  if (!fs.existsSync(deploymentsDir)) {
    fs.mkdirSync(deploymentsDir, { recursive: true });
  }

  const deploymentFile = path.join(deploymentsDir, `${network}.json`);
  fs.writeFileSync(deploymentFile, JSON.stringify(deploymentInfo, null, 2));
  console.log("💾 Deployment info saved to:", deploymentFile);

  // Update .env instructions
  console.log("\n📝 UPDATE YOUR .env FILE:");
  console.log("─".repeat(70));
  console.log(`DOCUMENT_REGISTRY_CONTRACT_ADDRESS=${documentRegistryAddress}`);
  console.log("─".repeat(70));

  // Verify contract on Etherscan (if not localhost)
  if (network !== "localhost" && network !== "hardhat") {
    console.log("\n🔍 Verifying contract on Etherscan...");
    try {
      await hre.run("verify:verify", {
        address: documentRegistryAddress,
        constructorArguments: [],
      });
      console.log("✅ Contract verified on Etherscan!");
    } catch (error) {
      console.log("⚠️  Verification failed:", error.message);
      console.log("   You can verify manually later with:");
      console.log(`   npx hardhat verify --network ${network} ${documentRegistryAddress}`);
    }
  }

  console.log("\n🎉 Deployment Complete!");
  console.log("\n📚 Next Steps:");
  console.log("   1. Update DOCUMENT_REGISTRY_CONTRACT_ADDRESS in your .env file");
  console.log("   2. Copy the contract ABI to storage/app/contracts/");
  console.log("   3. Test your API with: php artisan serve");
  console.log("   4. View on explorer:", getExplorerUrl(network, documentRegistryAddress));
}

function getExplorerUrl(network, address) {
  const explorers = {
    sepolia: `https://sepolia.etherscan.io/address/${address}`,
    mumbai: `https://mumbai.polygonscan.com/address/${address}`,
    polygon: `https://polygonscan.com/address/${address}`,
    mainnet: `https://etherscan.io/address/${address}`,
    localhost: `http://localhost:8545 (Local Network)`
  };
  return explorers[network] || `Network: ${network}`;
}

// Error handling
main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error("❌ Deployment failed:", error);
    process.exit(1);
  });
