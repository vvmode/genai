<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BlockchainService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class BlockchainDocumentController extends Controller
{
    protected $blockchain;

    public function __construct(BlockchainService $blockchain)
    {
        $this->blockchain = $blockchain;
    }

    /**
     * Process document - Register or Verify
     * JSON → Blockchain
     */
    public function process(Request $request)
    {
        try {
            $action = strtolower($request->input('action', 'register'));

            if ($action === 'register') {
                return $this->register($request);
            } elseif ($action === 'verify') {
                return $this->verify($request);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid action. Use "register" or "verify".',
                    'valid_actions' => ['register', 'verify']
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Document processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Document processing failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register document - Write JSON data to blockchain
     */
    protected function register(Request $request)
    {
        // Validate request - Simple 3-field format
        $validator = Validator::make($request->all(), [
            'document_uuid' => 'required|string|max:255',
            'file_hash' => 'required|string|max:255',
            'metadata_hash' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        
        // Generate unique document ID to avoid "already exists" errors
        $uniqueDocumentId = $data['document_uuid'] . '-' . time() . '-' . substr(md5(random_bytes(16)), 0, 8);
        
        // Add 0x prefix if not present
        $fileHash = $data['file_hash'];
        if (!str_starts_with($fileHash, '0x')) {
            $fileHash = '0x' . $fileHash;
        }
        
        $metadataHash = $data['metadata_hash'];
        if (!str_starts_with($metadataHash, '0x')) {
            $metadataHash = '0x' . $metadataHash;
        }
        
        $pdfHash = $fileHash;

        
        // Prepare blockchain data structure with minimal required fields
        $blockchainData = [
            // Document info
            'documentType' => 'generic',
            'documentNumber' => $uniqueDocumentId,
            'documentTitle' => $data['document_uuid'],
            'documentCategory' => '',
            'documentSubcategory' => '',
            'documentDescription' => '',
            'documentLanguage' => 'en',
            'documentVersion' => '1.0',
            'securityLevel' => 'standard',
            
            // Validity info
            'issuedDate' => time(),
            'expiryDate' => 0,
            'isPermanent' => true,
            'renewable' => false,
            'gracePeriodDays' => 0,
            
            // Issuer info
            'issuerName' => 'System',
            'issuerCountry' => 'N/A',
            'issuerState' => '',
            'issuerCity' => '',
            'issuerRegistrationNumber' => '',
            'issuerContactEmail' => '',
            'issuerWebsite' => '',
            'issuerDepartment' => '',
            
            // Holder info
            'holderFullName' => 'N/A',
            'holderIdNumber' => '',
            'holderNationality' => '',
            'holderDateOfBirth' => 0,
            'holderContactEmail' => '',
            
            // PDF hash and metadata - Contract requires non-empty ipfsHash
            'ipfsHash' => 'QmPlaceholder-' . substr($uniqueDocumentId, 0, 20),
            'pdfHash' => $pdfHash,
            'additionalMetadata' => json_encode([
                'document_uuid' => $data['document_uuid'],
                'unique_id' => $uniqueDocumentId,
                'metadata_hash' => $metadataHash
            ]),
        ];

        Log::info('Registering document to blockchain', ['document_uuid' => $data['document_uuid']]);

        // Write to blockchain
        $result = $this->blockchain->registerDocumentV2($blockchainData);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Blockchain registration failed',
                'error' => $result['error'] ?? 'Unknown error'
            ], 500);
        }

        return response()->json([
            'success' => true,
            'action' => 'registered',
            'message' => 'Document registered successfully on blockchain',
            'data' => [
                'document_uuid' => $data['document_uuid'],
                'unique_document_id' => $uniqueDocumentId,
                'file_hash' => $fileHash,
                'metadata_hash' => $metadataHash,
                'blockchain' => [
                    'transaction_hash' => $result['transaction_hash'],
                    'contract_address' => $result['contract_address'] ?? config('blockchain.document_registry_v2_address'),
                    'explorer_url' => 'https://sepolia.etherscan.io/tx/' . $result['transaction_hash'],
                    'network' => 'sepolia',
                    'status' => 'pending'
                ],
                'registered_at' => now()->toIso8601String()
            ],
            'storage_model' => 'direct_blockchain',
            'note' => 'Hashes stored directly on blockchain. Check transaction status on Etherscan.'
        ], 201);
    }

    /**
     * Verify document - Read from blockchain
     */
    protected function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $documentId = $request->input('document_id');

        Log::info('Verifying document from blockchain', ['document_id' => $documentId]);

        // Read from blockchain
        $result = $this->blockchain->getDocumentMetadata($documentId);

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'action' => 'verify',
                'verified' => false,
                'message' => 'Document not found or verification failed',
                'data' => [
                    'document_id' => $documentId,
                    'exists' => false
                ]
            ], 404);
        }

        $metadata = $result['data'];

        // Check if document is revoked
        if ($metadata['revoked']) {
            return response()->json([
                'success' => true,
                'action' => 'verified',
                'verified' => false,
                'message' => 'Document has been revoked',
                'data' => [
                    'document_id' => $documentId,
                    'status' => 'revoked',
                    'revoked_at' => $metadata['revokedAt'] ?? null
                ]
            ], 200);
        }

        // Check if expired
        $isExpired = false;
        if ($metadata['expiryDate'] > 0) {
            $isExpired = time() > $metadata['expiryDate'];
        }

        // Decode additional metadata
        $additionalMetadata = [];
        if (!empty($metadata['additionalMetadata'])) {
            $additionalMetadata = json_decode($metadata['additionalMetadata'], true) ?? [];
        }

        return response()->json([
            'success' => true,
            'action' => 'verified',
            'verified' => true,
            'message' => $isExpired ? 'Document is expired but authentic' : 'Document is authentic and valid',
            'data' => [
                'document_id' => $documentId,
                'verified' => true,
                'status' => $isExpired ? 'expired' : 'valid',
                'document' => [
                    'type' => $metadata['documentType'],
                    'number' => $metadata['documentNumber'],
                    'title' => $metadata['documentTitle'],
                    'category' => $metadata['documentCategory'],
                    'subcategory' => $metadata['documentSubcategory'],
                    'description' => $metadata['documentDescription'],
                    'language' => $metadata['documentLanguage'],
                    'version' => $metadata['documentVersion'],
                    'security_level' => $metadata['securityLevel']
                ],
                'validity' => [
                    'issued_date' => date('Y-m-d', $metadata['issuedDate']),
                    'expiry_date' => $metadata['expiryDate'] > 0 ? date('Y-m-d', $metadata['expiryDate']) : null,
                    'is_permanent' => $metadata['isPermanent'],
                    'is_expired' => $isExpired,
                    'renewable' => $metadata['renewable'],
                    'grace_period_days' => $metadata['gracePeriodDays']
                ],
                'issuer' => [
                    'name' => $metadata['issuerName'],
                    'country' => $metadata['issuerCountry'],
                    'state' => $metadata['issuerState'],
                    'city' => $metadata['issuerCity'],
                    'registration_number' => $metadata['issuerRegistrationNumber'],
                    'contact_email' => $metadata['issuerContactEmail'],
                    'website' => $metadata['issuerWebsite'],
                    'department' => $metadata['issuerDepartment']
                ],
                'holder' => [
                    'full_name' => $metadata['holderFullName'],
                    'id_number' => $metadata['holderIdNumber'],
                    'nationality' => $metadata['holderNationality'],
                    'date_of_birth' => $metadata['holderDateOfBirth'] > 0 ? date('Y-m-d', $metadata['holderDateOfBirth']) : null,
                    'contact_email' => $metadata['holderContactEmail']
                ],
                'blockchain' => [
                    'pdf_hash' => $metadata['pdfHash'],
                    'revoked' => $metadata['revoked'],
                    'registered_at' => date('Y-m-d H:i:s', $metadata['registeredAt'])
                ],
                'metadata' => $additionalMetadata
            ],
            'storage_model' => 'direct_blockchain',
            'data_source' => 'ethereum_blockchain'
        ], 200);
    }
}
