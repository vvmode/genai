import hre from "hardhat";
import fs from "fs";
import path from "path";

async function main() {
  console.log("🚀 Deploying DocumentRegistryV2...\n");

  // Get deployer account
  const [deployer] = await hre.ethers.getSigners();
  console.log("Deploying contracts with account:", deployer.address);
  
  const balance = await hre.ethers.provider.getBalance(deployer.address);
  console.log("Account balance:", hre.ethers.formatEther(balance), "ETH\n");

  // Deploy contract
  const DocumentRegistryV2 = await hre.ethers.getContractFactory("DocumentRegistryV2");
  const contract = await DocumentRegistryV2.deploy();
  
  await contract.waitForDeployment();
  const contractAddress = await contract.getAddress();

  console.log("✅ DocumentRegistryV2 deployed to:", contractAddress);
  console.log("🔗 Etherscan:", `https://sepolia.etherscan.io/address/${contractAddress}`);
  
  // Copy ABI file to Laravel storage
  try {
    const abiSource = path.join(process.cwd(), 'artifacts/contracts/DocumentRegistryV2.sol/DocumentRegistryV2.json');
    const abiDestDir = path.join(process.cwd(), 'storage/app/contracts');
    const abiDest = path.join(abiDestDir, 'DocumentRegistryV2.json');
    
    // Create directory if it doesn't exist
    if (!fs.existsSync(abiDestDir)) {
      fs.mkdirSync(abiDestDir, { recursive: true });
      console.log("\n📁 Created contracts directory");
    }
    
    // Copy ABI file
    fs.copyFileSync(abiSource, abiDest);
    console.log("📄 ABI file copied to storage/app/contracts/DocumentRegistryV2.json");
  } catch (error) {
    console.log("⚠️  Warning: Could not copy ABI file:", error.message);
  }
  
  // Save deployment info
  const deploymentInfo = {
    network: "sepolia",
    contractAddress: contractAddress,
    deployer: deployer.address,
    deployedAt: new Date().toISOString(),
    version: "2.0"
  };
  
  fs.writeFileSync(
    'deployment-v2.json',
    JSON.stringify(deploymentInfo, null, 2)
  );
  
  console.log("📝 Deployment info saved to deployment-v2.json");
  console.log("\n⚙️  Add to Railway environment variables:");
  console.log(`DOCUMENT_REGISTRY_V2_ADDRESS=${contractAddress}`);
  console.log("\n⏳ Waiting 30 seconds before verification...");
  
  // Wait for block confirmations
  await new Promise(resolve => setTimeout(resolve, 30000));
  
  // Verify contract (optional, requires Etherscan API key)
  try {
    console.log("\n🔍 Verifying contract on Etherscan...");
    await hre.run("verify:verify", {
      address: contractAddress,
      constructorArguments: [],
    });
    console.log("✅ Contract verified!");
  } catch (error) {
    console.log("⚠️  Verification failed (you can verify manually later)");
    console.log("Error:", error.message);
  }
}

main()
  .then(() => process.exit(0))
  .catch((error) => {
    console.error(error);
    process.exit(1);
  });
