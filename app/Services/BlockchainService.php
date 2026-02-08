<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Web3\Web3;
use Web3\Contract;
use Web3\Utils;
use phpseclib3\Math\BigInteger;
use Elliptic\EC;
use kornrunner\Keccak;

class BlockchainService
{
    private Web3 $web3;
    private string $walletAddress;
    private string $privateKey;
    private array $contractConfig;

    public function __construct()
    {
        $rpcUrl = config('blockchain.network.rpc_url');
        if (empty($rpcUrl)) {
            throw new Exception('Blockchain RPC URL not configured');
        }

        $this->web3 = new Web3($rpcUrl);
        $this->privateKey = config('blockchain.wallet.private_key');
        
        // Get wallet address from config, or derive from private key
        $configAddress = config('blockchain.wallet.address');
        if (!empty($configAddress)) {
            $this->walletAddress = $configAddress;
        } else if (!empty($this->privateKey)) {
            // Derive address from private key
            $this->walletAddress = $this->deriveAddressFromPrivateKey($this->privateKey);
            Log::info('Derived wallet address from private key', [
                'address' => $this->walletAddress
            ]);
        } else {
            throw new Exception('Neither wallet address nor private key configured');
        }
        
        $this->contractConfig = config('blockchain.contracts.document_registry');
    }

    /**
     * Register a document on the blockchain
     *
     * @param string $documentId Unique document identifier (32 bytes hex)
     * @param string $documentHash SHA-256 hash of the document
     * @param string $documentType Type of document (certificate, experience_letter, etc.)
     * @param int|null $expiryTimestamp Optional expiry timestamp
     * @param array $metadata Additional metadata
     * @return array Transaction details
     */
    public function registerDocument(
        string $documentId,
        string $documentHash,
        string $documentType,
        ?int $expiryTimestamp = null,
        array $metadata = []
    ): array {
        try {
            // Validate inputs
            $this->validateDocumentId($documentId);
            $this->validateHash($documentHash);

            // Load contract ABI
            $abi = $this->loadContractABI($this->contractConfig['abi_path']);
            $contractAddress = $this->contractConfig['address'];

            if (empty($contractAddress)) {
                throw new Exception('Document Registry contract address not configured');
            }

            // Prepare contract call
            $contract = new Contract($this->web3->provider, $abi);
            
            // Encode function call
            $functionData = $contract->at($contractAddress)->getData(
                'registerDocument',
                $documentId,
                $documentHash,
                $documentType,
                $expiryTimestamp ?? 0
            );

            // Get current gas price
            $gasPrice = $this->getGasPrice();

            // Build and sign transaction
            $txHash = $this->sendTransaction([
                'from' => $this->walletAddress,
                'to' => $contractAddress,
                'data' => $functionData,
                'gas' => '0x' . dechex(config('blockchain.gas.limit', 300000)),
                'gasPrice' => $gasPrice,
            ]);

            Log::info('Document registered on blockchain', [
                'document_id' => $documentId,
                'tx_hash' => $txHash,
                'contract' => $contractAddress,
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'contract_address' => $contractAddress,
                'document_id' => $documentId,
                'status' => 'pending',
            ];

        } catch (Exception $e) {
            Log::error('Failed to register document on blockchain', [
                'error' => $e->getMessage(),
                'document_id' => $documentId ?? null,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Revoke a document on the blockchain
     *
     * @param string $documentId Document identifier
     * @param string $reason Reason for revocation
     * @return array Transaction details
     */
    public function revokeDocument(string $documentId, string $reason = ''): array
    {
        try {
            $this->validateDocumentId($documentId);

            $abi = $this->loadContractABI($this->contractConfig['abi_path']);
            $contractAddress = $this->contractConfig['address'];

            $contract = new Contract($this->web3->provider, $abi);
            
            $functionData = $contract->at($contractAddress)->getData(
                'revokeDocument',
                $documentId
            );

            $gasPrice = $this->getGasPrice();

            $txHash = $this->sendTransaction([
                'from' => $this->walletAddress,
                'to' => $contractAddress,
                'data' => $functionData,
                'gas' => '0x' . dechex(config('blockchain.gas.limit', 300000)),
                'gasPrice' => $gasPrice,
            ]);

            Log::info('Document revoked on blockchain', [
                'document_id' => $documentId,
                'tx_hash' => $txHash,
                'reason' => $reason,
            ]);

            return [
                'success' => true,
                'tx_hash' => $txHash,
                'document_id' => $documentId,
                'status' => 'pending',
            ];

        } catch (Exception $e) {
            Log::error('Failed to revoke document on blockchain', [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify a document exists on the blockchain
     *
     * @param string $documentId Document identifier
     * @return array Document data from blockchain
     */
    public function verifyDocument(string $documentId): array
    {
        try {
            $this->validateDocumentId($documentId);

            $abi = $this->loadContractABI($this->contractConfig['abi_path']);
            $contractAddress = $this->contractConfig['address'];

            $contract = new Contract($this->web3->provider, $abi);
            
            $result = null;
            $contract->at($contractAddress)->call('getDocument', $documentId, function ($err, $data) use (&$result) {
                if ($err !== null) {
                    throw new Exception($err->getMessage());
                }
                $result = $data;
            });

            return [
                'success' => true,
                'data' => $result,
            ];

        } catch (Exception $e) {
            Log::error('Failed to verify document on blockchain', [
                'error' => $e->getMessage(),
                'document_id' => $documentId,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get transaction receipt
     *
     * @param string $txHash Transaction hash
     * @return array|null Receipt data
     */
    public function getTransactionReceipt(string $txHash): ?array
    {
        try {
            $receipt = null;
            $this->web3->eth->getTransactionReceipt($txHash, function ($err, $data) use (&$receipt) {
                if ($err === null && $data !== null) {
                    $receipt = $data;
                }
            });

            return $receipt;

        } catch (Exception $e) {
            Log::error('Failed to get transaction receipt', [
                'error' => $e->getMessage(),
                'tx_hash' => $txHash,
            ]);

            return null;
        }
    }

    /**
     * Get current gas price from network
     *
     * @return string Gas price in hex
     */
    private function getGasPrice(): string
    {
        $gasPrice = null;
        
        $this->web3->eth->gasPrice(function ($err, $price) use (&$gasPrice) {
            if ($err === null) {
                $gasPrice = $price;
            }
        });

        if ($gasPrice === null) {
            // Fallback to configured gas price
            $gasPriceGwei = config('blockchain.gas.price_gwei', '20');
            $gasPrice = Utils::toWei($gasPriceGwei, 'gwei');
        }

        return '0x' . $gasPrice->toHex();
    }

    /**
     * Send a transaction to the blockchain
     *
     * @param array $txParams Transaction parameters
     * @return string Transaction hash
     */
    private function sendTransaction(array $txParams): string
    {
        // Get nonce
        $nonce = $this->getNonce($this->walletAddress);
        $chainId = config('blockchain.network.chain_id');

        // Build transaction array for RLP encoding
        $transaction = [
            'nonce' => '0x' . dechex($nonce),
            'gasPrice' => $txParams['gasPrice'],
            'gas' => $txParams['gas'],
            'to' => $txParams['to'],
            'value' => $txParams['value'] ?? '0x0',
            'data' => $txParams['data'],
        ];

        Log::info('Preparing transaction', [
            'from' => $this->walletAddress,
            'to' => $txParams['to'],
            'nonce' => $nonce,
            'gas' => $txParams['gas'],
            'chainId' => $chainId,
        ]);

        // Sign and send the transaction
        $signedTx = $this->signTransaction($transaction, $chainId);

        // Send raw signed transaction
        $txHash = null;
        $this->web3->eth->sendRawTransaction($signedTx, function ($err, $hash) use (&$txHash) {
            if ($err !== null) {
                throw new Exception('Transaction failed: ' . $err->getMessage());
            }
            $txHash = $hash;
        });

        if (empty($txHash)) {
            throw new Exception('Failed to send transaction');
        }

        return $txHash;
    }

    /**
     * Sign an Ethereum transaction
     *
     * @param array $transaction Transaction data
     * @param int $chainId Chain ID for EIP-155
     * @return string Signed transaction hex string
     */
    private function signTransaction(array $transaction, int $chainId): string
    {
        // Prepare transaction for signing (EIP-155)
        $fields = [
            $this->hexToBytes($transaction['nonce']),
            $this->hexToBytes($transaction['gasPrice']),
            $this->hexToBytes($transaction['gas']),
            hex2bin(substr($transaction['to'], 2)),
            $this->hexToBytes($transaction['value']),
            hex2bin(substr($transaction['data'], 2)),
            $this->hexToBytes('0x' . dechex($chainId)), // chain ID as bytes
            '', // r (empty for unsigned)
            '', // s (empty for unsigned)
        ];

        // RLP encode
        $encodedTx = $this->rlpEncode($fields);
        
        // Hash the transaction
        $txHash = Keccak::hash($encodedTx, 256, true);

        // Sign with private key
        $privateKey = str_replace('0x', '', $this->privateKey);
        $secp256k1 = new EC('secp256k1');
        $key = $secp256k1->keyFromPrivate($privateKey, 'hex');
        $signature = $key->sign($txHash, ['canonical' => true]);

        // Calculate v value (EIP-155)
        $v = $signature->recoveryParam + $chainId * 2 + 35;

        // Update fields with signature
        $fields[6] = $this->hexToBytes('0x' . dechex($v)); // v as bytes
        $fields[7] = hex2bin(str_pad($signature->r->toString(16), 64, '0', STR_PAD_LEFT));
        $fields[8] = hex2bin(str_pad($signature->s->toString(16), 64, '0', STR_PAD_LEFT));

        // RLP encode again with signature
        $signedTx = $this->rlpEncode($fields);

        return '0x' . bin2hex($signedTx);
    }

    /**
     * RLP encode data
     *
     * @param array $input Data to encode
     * @return string Encoded data
     */
    private function rlpEncode($input): string
    {
        if (is_string($input)) {
            if (strlen($input) == 1 && ord($input) < 0x80) {
                return $input;
            }
            return $this->encodeLength(strlen($input), 0x80) . $input;
        } elseif (is_array($input)) {
            $output = '';
            foreach ($input as $val) {
                $output .= $this->rlpEncode($val);
            }
            return $this->encodeLength(strlen($output), 0xc0) . $output;
        }
        return '';
    }

    /**
     * Encode length for RLP
     *
     * @param int $length Length to encode
     * @param int $offset Offset
     * @return string Encoded length
     */
    private function encodeLength(int $length, int $offset): string
    {
        if ($length < 56) {
            return chr($length + $offset);
        } elseif ($length < 256 ** 8) {
            $lengthHex = dechex($length);
            if (strlen($lengthHex) % 2) {
                $lengthHex = '0' . $lengthHex;
            }
            $lengthBinary = hex2bin($lengthHex);
            return chr(strlen($lengthBinary) + $offset + 55) . $lengthBinary;
        }
        throw new Exception('Input too long');
    }

    /**
     * Convert hex string to bytes
     *
     * @param string $hex Hex string
     * @return string Binary string
     */
    private function hexToBytes(string $hex): string
    {
        $hex = str_replace('0x', '', $hex);
        if ($hex === '0' || $hex === '') {
            return '';
        }
        if (strlen($hex) % 2) {
            $hex = '0' . $hex;
        }
        return hex2bin($hex);
    }

    /**
     * Get nonce for address
     *
     * @param string $address Wallet address
     * @return int Nonce
     */
    private function getNonce(string $address): int
    {
        $nonce = 0;
        
        $this->web3->eth->getTransactionCount($address, 'pending', function ($err, $count) use (&$nonce) {
            if ($err === null) {
                $nonce = hexdec($count->toString());
            }
        });

        return $nonce;
    }

    /**
     * Load contract ABI from file
     *
     * @param string $abiPath Path to ABI JSON file
     * @return string ABI JSON string
     */
    private function loadContractABI(string $abiPath): string
    {
        if (!file_exists($abiPath)) {
            throw new Exception("Contract ABI file not found: {$abiPath}");
        }

        $content = file_get_contents($abiPath);
        $json = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid ABI JSON format');
        }

        // Check if this is a Hardhat compilation artifact (has 'abi' field)
        // If so, extract just the ABI array
        if (isset($json['abi']) && is_array($json['abi'])) {
            return json_encode($json['abi']);
        }

        // Otherwise assume the file is already just the ABI array
        return $content;
    }

    /**
     * Derive Ethereum address from private key
     *
     * @param string $privateKey Private key with or without 0x prefix
     * @return string Ethereum address with 0x prefix
     */
    private function deriveAddressFromPrivateKey(string $privateKey): string
    {
        // Remove 0x prefix if present
        $privateKey = str_replace('0x', '', $privateKey);
        
        // Use simplito/elliptic-php for secp256k1 operations
        try {
            $secp256k1 = new \Elliptic\EC('secp256k1');
            $keyPair = $secp256k1->keyFromPrivate($privateKey, 'hex');
            $publicKey = $keyPair->getPublic(false, 'hex');
            
            // Remove '04' prefix from uncompressed public key (first byte)
            $publicKey = substr($publicKey, 2);
            
            // Hash public key with keccak256
            $hash = \kornrunner\Keccak::hash(hex2bin($publicKey), 256);
            
            // Take last 20 bytes (40 hex chars) as address
            $address = '0x' . substr($hash, -40);
            
            return $address;
        } catch (Exception $e) {
            throw new Exception('Failed to derive address from private key: ' . $e->getMessage());
        }
    }

    /**
     * Validate document ID format
     *
     * @param string $documentId Document ID
     * @throws Exception
     */
    private function validateDocumentId(string $documentId): void
    {
        if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $documentId)) {
            throw new Exception('Invalid document ID format. Must be 32 bytes hex string with 0x prefix');
        }
    }

    /**
     * Validate hash format
     *
     * @param string $hash Hash string
     * @throws Exception
     */
    private function validateHash(string $hash): void
    {
        if (!preg_match('/^(0x)?[a-fA-F0-9]{64}$/', $hash)) {
            throw new Exception('Invalid hash format. Must be 64 character hex string');
        }
    }

    /**
     * Generate document ID from hash
     *
     * @param string $hash Document hash
     * @return string Document ID (32 bytes hex with 0x prefix)
     */
    public static function generateDocumentId(string $hash): string
    {
        // Use the hash as document ID or derive it
        $cleanHash = str_replace('0x', '', $hash);
        return '0x' . $cleanHash;
    }

    /**
     * Register document with comprehensive metadata (V2 contract)
     *
     * @param array $data Document data
     * @return array Transaction details
     * @throws Exception
     */
    public function registerDocumentV2(array $data): array
    {
        try {
            Log::info('Registering document V2 on blockchain', [
                'document_number' => $data['documentNumber']
            ]);

            // Load V2 contract configuration
            $contractConfig = config('blockchain.contracts.document_registry_v2');
            $contractAddress = $contractConfig['address'];
            
            if (empty($contractAddress)) {
                throw new Exception('Document Registry V2 contract address not configured');
            }

            $abi = $this->loadContractABI($contractConfig['abi_path']);
            $contract = new Contract($this->web3->provider, $abi);

            // Convert pdfHash to bytes32 format (remove 0x prefix if present)
            $pdfHashHex = $data['pdfHash'];
            if (strpos($pdfHashHex, '0x') === 0) {
                $pdfHashHex = substr($pdfHashHex, 2);
            }
            // Pad to 32 bytes (64 hex chars) if needed
            $pdfHashHex = str_pad($pdfHashHex, 64, '0', STR_PAD_RIGHT);

            // If no IPFS hash provided, use document number as placeholder
            // (contract requires non-empty ipfsHash)
            $ipfsHashValue = !empty($data['ipfsHash']) 
                ? $data['ipfsHash'] 
                : 'QmNone-' . $data['documentNumber'];

            // Prepare contract function call with parameters matching contract signature
            // Contract expects: documentId, documentType, documentNumber, documentTitle,
            // issuedDate, effectiveFrom, effectiveUntil, expiryDate, isPermanent,
            // issuerName, issuerCountry, issuerRegistrationNumber,
            // holderFullName, holderIdNumber, holderNationality,
            // ipfsHash, pdfHash
            $functionData = $contract->at($contractAddress)->getData(
                'registerDocument',
                $data['documentNumber'],                    // documentId (use document number as ID)
                $data['documentType'],                      // documentType
                $data['documentNumber'],                    // documentNumber
                $data['documentTitle'],                     // documentTitle
                (string)$data['issuedDate'],               // issuedDate (uint256)
                (string)($data['issuedDate']),             // effectiveFrom (use issued date)
                (string)($data['expiryDate'] ?: 0),        // effectiveUntil
                (string)($data['expiryDate'] ?: 0),        // expiryDate (uint256)
                $data['isPermanent'],                       // isPermanent (bool)
                $data['issuerName'],                        // issuerName
                $data['issuerCountry'],                     // issuerCountry
                $data['issuerRegistrationNumber'] ?: '',    // issuerRegistrationNumber
                $data['holderFullName'],                    // holderFullName
                $data['holderIdNumber'] ?: '',              // holderIdNumber
                $data['holderNationality'] ?: '',           // holderNationality
                $ipfsHashValue,                             // ipfsHash (placeholder if not using IPFS)
                '0x' . $pdfHashHex                          // pdfHash (bytes32)
            );

            // Get gas price
            $gasPrice = $this->getGasPrice();

            // Send transaction
            $txHash = $this->sendTransaction([
                'from' => $this->walletAddress,
                'to' => $contractAddress,
                'data' => $functionData,
                'gas' => '0x' . dechex(500000), // Higher gas limit for V2 (more data)
                'gasPrice' => $gasPrice,
            ]);

            Log::info('Document V2 registered on blockchain', [
                'document_number' => $data['documentNumber'],
                'tx_hash' => $txHash,
                'contract' => $contractAddress
            ]);

            return [
                'success' => true,
                'transaction_hash' => $txHash,
                'contract_address' => $contractAddress,
                'status' => 'pending'
            ];

        } catch (Exception $e) {
            Log::error('Failed to register document V2', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'document_number' => $data['documentNumber'] ?? 'unknown'
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get document metadata from blockchain (V2 contract)
     *
     * @param string $documentId Document ID
     * @return array Document metadata
     * @throws Exception
     */
    public function getDocumentMetadata(string $documentId): array
    {
        try {
            Log::info('Retrieving document metadata from blockchain', [
                'document_id' => $documentId
            ]);

            // Load V2 contract configuration
            $contractConfig = config('blockchain.contracts.document_registry_v2');
            $contractAddress = $contractConfig['address'];
            
            if (empty($contractAddress)) {
                throw new Exception('Document Registry V2 contract address not configured');
            }

            $abi = $this->loadContractABI($contractConfig['abi_path']);
            $contract = new Contract($this->web3->provider, $abi);

            // Call getDocument function on contract
            $result = null;
            $contract->at($contractAddress)->call('getDocument', $documentId, function ($err, $data) use (&$result) {
                if ($err !== null) {
                    throw new Exception($err->getMessage());
                }
                $result = $data;
            });

            // Check if document exists
            if (empty($result) || empty($result['documentNumber'])) {
                return [
                    'success' => false,
                    'error' => 'Document not found'
                ];
            }

            Log::info('Document metadata retrieved', [
                'document_id' => $documentId,
                'found' => true
            ]);

            // Return structured metadata
            return [
                'success' => true,
                'data' => [
                    'documentType' => $result['documentType'],
                    'documentNumber' => $result['documentNumber'],
                    'documentTitle' => $result['documentTitle'],
                    'documentCategory' => $result['documentCategory'],
                    'documentSubcategory' => $result['documentSubcategory'],
                    'documentDescription' => $result['documentDescription'],
                    'documentLanguage' => $result['documentLanguage'],
                    'documentVersion' => $result['documentVersion'],
                    'securityLevel' => $result['securityLevel'],
                    'issuedDate' => $result['issuedDate'],
                    'expiryDate' => $result['expiryDate'],
                    'isPermanent' => $result['isPermanent'],
                    'renewable' => $result['renewable'],
                    'gracePeriodDays' => $result['gracePeriodDays'],
                    'issuerName' => $result['issuerName'],
                    'issuerCountry' => $result['issuerCountry'],
                    'issuerState' => $result['issuerState'],
                    'issuerCity' => $result['issuerCity'],
                    'issuerRegistrationNumber' => $result['issuerRegistrationNumber'],
                    'issuerContactEmail' => $result['issuerContactEmail'],
                    'issuerWebsite' => $result['issuerWebsite'],
                    'issuerDepartment' => $result['issuerDepartment'],
                    'holderFullName' => $result['holderFullName'],
                    'holderIdNumber' => $result['holderIdNumber'],
                    'holderNationality' => $result['holderNationality'],
                    'holderDateOfBirth' => $result['holderDateOfBirth'],
                    'holderContactEmail' => $result['holderContactEmail'],
                    'ipfsHash' => $result['ipfsHash'],
                    'pdfHash' => $result['pdfHash'],
                    'additionalMetadata' => $result['additionalMetadata'],
                    'revoked' => $result['revoked'],
                    'registeredAt' => $result['registeredAt']
                ]
            ];

        } catch (Exception $e) {
            Log::error('Failed to get document metadata', [
                'document_id' => $documentId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

