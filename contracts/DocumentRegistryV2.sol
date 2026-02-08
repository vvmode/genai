// SPDX-License-Identifier: MIT
pragma solidity ^0.8.19;

/**
 * @title DocumentRegistryV2
 * @dev Hybrid storage: Critical metadata on blockchain, encrypted PDF on IPFS
 */
contract DocumentRegistryV2 {
    struct DocumentMetadata {
        // Blockchain storage - always accessible
        address issuer;
        
        // Document info
        string documentType;
        string documentNumber;
        string documentTitle;
        
        // Validity
        uint256 issuedDate;
        uint256 effectiveFrom;
        uint256 effectiveUntil;
        uint256 expiryDate;
        bool isPermanent;
        
        // Issuer info
        string issuerName;
        string issuerCountry;
        string issuerRegistrationNumber;
        
        // Holder info
        string holderFullName;
        string holderIdNumber;
        string holderNationality;
        
        // IPFS storage pointer
        string ipfsHash;  // Points to encrypted PDF
        bytes32 pdfHash;  // SHA-256 of original PDF for verification
        
        // Status
        bool revoked;
        uint256 registeredAt;
    }

    mapping(string => DocumentMetadata) public documents;
    mapping(string => bool) public exists;
    mapping(address => string[]) public issuerDocuments;

    event DocumentRegistered(
        string indexed documentId,
        address indexed issuer,
        string ipfsHash,
        bytes32 pdfHash,
        uint256 timestamp
    );

    event DocumentRevoked(
        string indexed documentId,
        address indexed revoker,
        uint256 timestamp
    );

    event DocumentUpdated(
        string indexed documentId,
        string newIpfsHash,
        uint256 timestamp
    );

    modifier onlyIssuer(string memory documentId) {
        require(
            documents[documentId].issuer == msg.sender,
            "Only issuer can perform this action"
        );
        _;
    }

    modifier documentExists(string memory documentId) {
        require(exists[documentId], "Document does not exist");
        _;
    }

    /**
     * @dev Register a new document with complete metadata
     */
    function registerDocument(
        string memory documentId,
        string memory documentType,
        string memory documentNumber,
        string memory documentTitle,
        uint256 issuedDate,
        uint256 effectiveFrom,
        uint256 effectiveUntil,
        uint256 expiryDate,
        bool isPermanent,
        string memory issuerName,
        string memory issuerCountry,
        string memory issuerRegistrationNumber,
        string memory holderFullName,
        string memory holderIdNumber,
        string memory holderNationality,
        string memory ipfsHash,
        bytes32 pdfHash
    ) external {
        require(!exists[documentId], "Document already exists");
        require(bytes(ipfsHash).length > 0, "IPFS hash required");
        require(pdfHash != bytes32(0), "PDF hash required");

        documents[documentId] = DocumentMetadata({
            issuer: msg.sender,
            documentType: documentType,
            documentNumber: documentNumber,
            documentTitle: documentTitle,
            issuedDate: issuedDate,
            effectiveFrom: effectiveFrom,
            effectiveUntil: effectiveUntil,
            expiryDate: expiryDate,
            isPermanent: isPermanent,
            issuerName: issuerName,
            issuerCountry: issuerCountry,
            issuerRegistrationNumber: issuerRegistrationNumber,
            holderFullName: holderFullName,
            holderIdNumber: holderIdNumber,
            holderNationality: holderNationality,
            ipfsHash: ipfsHash,
            pdfHash: pdfHash,
            revoked: false,
            registeredAt: block.timestamp
        });

        exists[documentId] = true;
        issuerDocuments[msg.sender].push(documentId);

        emit DocumentRegistered(
            documentId,
            msg.sender,
            ipfsHash,
            pdfHash,
            block.timestamp
        );
    }

    /**
     * @dev Revoke a document
     */
    function revokeDocument(string memory documentId)
        external
        documentExists(documentId)
        onlyIssuer(documentId)
    {
        require(!documents[documentId].revoked, "Already revoked");
        
        documents[documentId].revoked = true;

        emit DocumentRevoked(documentId, msg.sender, block.timestamp);
    }

    /**
     * @dev Update IPFS hash (e.g., if IPFS node changed)
     */
    function updateIpfsHash(string memory documentId, string memory newIpfsHash)
        external
        documentExists(documentId)
        onlyIssuer(documentId)
    {
        require(bytes(newIpfsHash).length > 0, "Invalid IPFS hash");
        require(!documents[documentId].revoked, "Document is revoked");
        
        documents[documentId].ipfsHash = newIpfsHash;

        emit DocumentUpdated(documentId, newIpfsHash, block.timestamp);
    }

    /**
     * @dev Verify document authenticity
     */
    function verifyDocument(string memory documentId, bytes32 pdfHash)
        external
        view
        returns (
            bool isValid,
            bool isRevoked,
            bool isExpired,
            address issuer,
            string memory documentType,
            string memory issuerName,
            string memory holderName
        )
    {
        if (!exists[documentId]) {
            return (false, false, false, address(0), "", "", "");
        }

        DocumentMetadata memory doc = documents[documentId];
        bool expired = doc.expiryDate > 0 && block.timestamp > doc.expiryDate;
        bool valid = doc.pdfHash == pdfHash && !doc.revoked && !expired;

        return (
            valid,
            doc.revoked,
            expired,
            doc.issuer,
            doc.documentType,
            doc.issuerName,
            doc.holderFullName
        );
    }

    /**
     * @dev Get complete document metadata
     */
    function getDocument(string memory documentId)
        external
        view
        documentExists(documentId)
        returns (DocumentMetadata memory)
    {
        return documents[documentId];
    }

    /**
     * @dev Get all documents by an issuer
     */
    function getIssuerDocuments(address issuer)
        external
        view
        returns (string[] memory)
    {
        return issuerDocuments[issuer];
    }

    /**
     * @dev Check if document is expired
     */
    function isExpired(string memory documentId)
        external
        view
        documentExists(documentId)
        returns (bool)
    {
        DocumentMetadata memory doc = documents[documentId];
        return doc.expiryDate > 0 && block.timestamp > doc.expiryDate;
    }
}
