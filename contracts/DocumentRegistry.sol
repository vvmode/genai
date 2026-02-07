// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title DocumentRegistry
 * @dev Smart contract for registering and verifying documents on the blockchain
 */
contract DocumentRegistry {
    struct Document {
        address issuer;
        bytes32 documentHash;
        string documentType;
        uint256 issueDate;
        uint256 expiryDate;
        bool revoked;
        bytes32 previousVersion;
    }

    mapping(bytes32 => Document) public documents;
    mapping(bytes32 => bool) public exists;

    event DocumentRegistered(
        bytes32 indexed documentId,
        address indexed issuer,
        bytes32 documentHash,
        string documentType,
        uint256 timestamp
    );

    event DocumentRevoked(
        bytes32 indexed documentId,
        address indexed revoker,
        uint256 timestamp
    );

    event DocumentReissued(
        bytes32 indexed oldDocumentId,
        bytes32 indexed newDocumentId,
        address indexed issuer,
        uint256 timestamp
    );

    modifier onlyIssuer(bytes32 documentId) {
        require(
            documents[documentId].issuer == msg.sender,
            "Only issuer can perform this action"
        );
        _;
    }

    modifier documentExists(bytes32 documentId) {
        require(exists[documentId], "Document does not exist");
        _;
    }

    /**
     * @dev Register a new document
     * @param documentId Unique identifier for the document
     * @param documentHash SHA-256 hash of the document
     * @param documentType Type of document
     * @param expiryTimestamp Expiry date (0 for no expiry)
     */
    function registerDocument(
        bytes32 documentId,
        bytes32 documentHash,
        string memory documentType,
        uint256 expiryTimestamp
    ) external {
        require(!exists[documentId], "Document already exists");
        require(documentHash != bytes32(0), "Invalid document hash");

        documents[documentId] = Document({
            issuer: msg.sender,
            documentHash: documentHash,
            documentType: documentType,
            issueDate: block.timestamp,
            expiryDate: expiryTimestamp,
            revoked: false,
            previousVersion: bytes32(0)
        });

        exists[documentId] = true;

        emit DocumentRegistered(
            documentId,
            msg.sender,
            documentHash,
            documentType,
            block.timestamp
        );
    }

    /**
     * @dev Revoke a document
     * @param documentId Document identifier
     */
    function revokeDocument(bytes32 documentId)
        external
        documentExists(documentId)
        onlyIssuer(documentId)
    {
        require(!documents[documentId].revoked, "Document already revoked");

        documents[documentId].revoked = true;

        emit DocumentRevoked(documentId, msg.sender, block.timestamp);
    }

    /**
     * @dev Reissue a document with corrections
     * @param oldDocumentId Original document identifier
     * @param newDocumentId New document identifier
     * @param newDocumentHash New document hash
     * @param documentType Document type
     * @param expiryTimestamp Expiry date
     */
    function reissueDocument(
        bytes32 oldDocumentId,
        bytes32 newDocumentId,
        bytes32 newDocumentHash,
        string memory documentType,
        uint256 expiryTimestamp
    )
        external
        documentExists(oldDocumentId)
        onlyIssuer(oldDocumentId)
    {
        require(!exists[newDocumentId], "New document ID already exists");
        require(newDocumentHash != bytes32(0), "Invalid document hash");

        // Revoke old document
        documents[oldDocumentId].revoked = true;

        // Create new document with reference to old one
        documents[newDocumentId] = Document({
            issuer: msg.sender,
            documentHash: newDocumentHash,
            documentType: documentType,
            issueDate: block.timestamp,
            expiryDate: expiryTimestamp,
            revoked: false,
            previousVersion: oldDocumentId
        });

        exists[newDocumentId] = true;

        emit DocumentRevoked(oldDocumentId, msg.sender, block.timestamp);
        emit DocumentReissued(
            oldDocumentId,
            newDocumentId,
            msg.sender,
            block.timestamp
        );
    }

    /**
     * @dev Get document details
     * @param documentId Document identifier
     */
    function getDocument(bytes32 documentId)
        external
        view
        documentExists(documentId)
        returns (
            address issuer,
            bytes32 documentHash,
            string memory documentType,
            uint256 issueDate,
            uint256 expiryDate,
            bool revoked
        )
    {
        Document memory doc = documents[documentId];
        return (
            doc.issuer,
            doc.documentHash,
            doc.documentType,
            doc.issueDate,
            doc.expiryDate,
            doc.revoked
        );
    }

    /**
     * @dev Check if document is valid (exists, not revoked, not expired)
     * @param documentId Document identifier
     */
    function isDocumentValid(bytes32 documentId)
        external
        view
        returns (bool)
    {
        if (!exists[documentId]) {
            return false;
        }

        Document memory doc = documents[documentId];

        if (doc.revoked) {
            return false;
        }

        if (doc.expiryDate > 0 && block.timestamp > doc.expiryDate) {
            return false;
        }

        return true;
    }

    /**
     * @dev Get previous version of document
     * @param documentId Document identifier
     */
    function getPreviousVersion(bytes32 documentId)
        external
        view
        documentExists(documentId)
        returns (bytes32)
    {
        return documents[documentId].previousVersion;
    }
}
