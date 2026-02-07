const { expect } = require("chai");
const { ethers } = require("hardhat");

describe("DocumentRegistry", function () {
  let documentRegistry;
  let owner;
  let addr1;
  let addr2;

  beforeEach(async function () {
    [owner, addr1, addr2] = await ethers.getSigners();
    
    const DocumentRegistry = await ethers.getContractFactory("DocumentRegistry");
    documentRegistry = await DocumentRegistry.deploy();
    await documentRegistry.waitForDeployment();
  });

  describe("Document Registration", function () {
    it("Should register a new document", async function () {
      const documentId = ethers.id("DOC001");
      const documentHash = ethers.id("hash123");
      const documentType = "certificate";
      const expiryDate = Math.floor(Date.now() / 1000) + 86400; // 1 day from now

      await documentRegistry.registerDocument(
        documentId,
        documentHash,
        documentType,
        expiryDate
      );

      const doc = await documentRegistry.getDocument(documentId);
      expect(doc.issuer).to.equal(owner.address);
      expect(doc.documentHash).to.equal(documentHash);
      expect(doc.documentType).to.equal(documentType);
    });

    it("Should emit DocumentRegistered event", async function () {
      const documentId = ethers.id("DOC002");
      const documentHash = ethers.id("hash456");
      const documentType = "transcript";
      const expiryDate = 0;

      await expect(
        documentRegistry.registerDocument(
          documentId,
          documentHash,
          documentType,
          expiryDate
        )
      )
        .to.emit(documentRegistry, "DocumentRegistered")
        .withArgs(documentId, owner.address, documentHash, documentType, await ethers.provider.getBlock('latest').then(b => b.timestamp + 1));
    });

    it("Should reject duplicate document IDs", async function () {
      const documentId = ethers.id("DOC003");
      const documentHash = ethers.id("hash789");

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", 0);

      await expect(
        documentRegistry.registerDocument(documentId, documentHash, "certificate", 0)
      ).to.be.revertedWith("Document already exists");
    });
  });

  describe("Document Revocation", function () {
    it("Should allow issuer to revoke document", async function () {
      const documentId = ethers.id("DOC004");
      const documentHash = ethers.id("hash_revoke");

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", 0);
      await documentRegistry.revokeDocument(documentId);

      const doc = await documentRegistry.getDocument(documentId);
      expect(doc.revoked).to.be.true;
    });

    it("Should not allow non-issuer to revoke document", async function () {
      const documentId = ethers.id("DOC005");
      const documentHash = ethers.id("hash_revoke2");

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", 0);

      await expect(
        documentRegistry.connect(addr1).revokeDocument(documentId)
      ).to.be.revertedWith("Only issuer can perform this action");
    });
  });

  describe("Document Verification", function () {
    it("Should correctly validate a valid document", async function () {
      const documentId = ethers.id("DOC006");
      const documentHash = ethers.id("hash_valid");

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", 0);

      const isValid = await documentRegistry.isDocumentValid(documentId);
      expect(isValid).to.be.true;
    });

    it("Should invalidate revoked document", async function () {
      const documentId = ethers.id("DOC007");
      const documentHash = ethers.id("hash_revoked");

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", 0);
      await documentRegistry.revokeDocument(documentId);

      const isValid = await documentRegistry.isDocumentValid(documentId);
      expect(isValid).to.be.false;
    });

    it("Should invalidate expired document", async function () {
      const documentId = ethers.id("DOC008");
      const documentHash = ethers.id("hash_expired");
      const expiredDate = Math.floor(Date.now() / 1000) - 86400; // 1 day ago

      await documentRegistry.registerDocument(documentId, documentHash, "certificate", expiredDate);

      const isValid = await documentRegistry.isDocumentValid(documentId);
      expect(isValid).to.be.false;
    });
  });

  describe("Document Reissue", function () {
    it("Should allow reissuing a document with corrections", async function () {
      const oldDocId = ethers.id("DOC009");
      const newDocId = ethers.id("DOC010");
      const oldHash = ethers.id("hash_old");
      const newHash = ethers.id("hash_new");

      await documentRegistry.registerDocument(oldDocId, oldHash, "certificate", 0);
      await documentRegistry.reissueDocument(oldDocId, newDocId, newHash, "certificate", 0);

      const oldDoc = await documentRegistry.getDocument(oldDocId);
      const newDoc = await documentRegistry.getDocument(newDocId);

      expect(oldDoc.revoked).to.be.true;
      expect(newDoc.previousVersion).to.equal(oldDocId);
    });
  });
});
