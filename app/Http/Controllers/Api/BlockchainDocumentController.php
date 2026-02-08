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
        // Validate request
        $validator = Validator::make($request->all(), [
            'document' => 'required|array',
            'document.type' => 'required|string',
            'document.number' => 'required|string',
            'document.title' => 'required|string',
            'validity' => 'required|array',
            'validity.issued_date' => 'required|date',
            'issuer' => 'required|array',
            'issuer.name' => 'required|string',
            'issuer.country' => 'required|string',
            'holder' => 'required|array',
            'holder.full_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->all();
        $document = $data['document'];
        $validity = $data['validity'];
        $issuer = $data['issuer'];
        $holder = $data['holder'];
        $metadata = $data['metadata'] ?? [];

        // Generate document hash from PDF if provided
        $pdfHash = '';
        if (isset($document['pdf_base64'])) {
            $pdfData = base64_decode($document['pdf_base64']);
            $pdfHash = '0x' . hash('sha256', $pdfData);
        } else {
            // If no PDF provided, hash document metadata as placeholder
            // (contract requires non-zero pdfHash)
            $hashInput = $document['number'] . $document['title'] . $document['type'];
            $pdfHash = '0x' . hash('sha256', $hashInput);
        }

        // Prepare blockchain data structure
        $blockchainData = [
            // Document info
            'documentType' => $document['type'],
            'documentNumber' => $document['number'],
            'documentTitle' => $document['title'],
            'documentCategory' => $document['category'] ?? '',
            'documentSubcategory' => $document['subcategory'] ?? '',
            'documentDescription' => $document['description'] ?? '',
            'documentLanguage' => $document['language'] ?? 'en',
            'documentVersion' => $document['version'] ?? '1.0',
            'securityLevel' => $document['security_level'] ?? 'standard',
            
            // Validity info
            'issuedDate' => strtotime($validity['issued_date']),
            'expiryDate' => isset($validity['expiry_date']) ? strtotime($validity['expiry_date']) : 0,
            'isPermanent' => $validity['is_permanent'] ?? false,
            'renewable' => $validity['renewable'] ?? false,
            'gracePeriodDays' => $validity['grace_period_days'] ?? 0,
            
            // Issuer info
            'issuerName' => $issuer['name'],
            'issuerCountry' => $issuer['country'],
            'issuerState' => $issuer['state'] ?? '',
            'issuerCity' => $issuer['city'] ?? '',
            'issuerRegistrationNumber' => $issuer['registration_number'] ?? '',
            'issuerContactEmail' => $issuer['contact_email'] ?? '',
            'issuerWebsite' => $issuer['website'] ?? '',
            'issuerDepartment' => $issuer['department'] ?? '',
            
            // Holder info
            'holderFullName' => $holder['full_name'],
            'holderIdNumber' => $holder['id_number'] ?? '',
            'holderNationality' => $holder['nationality'] ?? '',
            'holderDateOfBirth' => isset($holder['date_of_birth']) ? strtotime($holder['date_of_birth']) : 0,
            'holderContactEmail' => $holder['contact_email'] ?? '',
            
            // PDF hash and metadata
            'ipfsHash' => '', // Not using IPFS for direct blockchain storage
            'pdfHash' => $pdfHash,
            'additionalMetadata' => json_encode($metadata),
        ];

        Log::info('Registering document to blockchain', ['document_id' => $document['number']]);

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
                'document_id' => $document['number'],
                'document_type' => $document['type'],
                'document_title' => $document['title'],
                'pdf_hash' => $pdfHash,
                'blockchain' => [
                    'transaction_hash' => $result['transaction_hash'],
                    'contract_address' => $result['contract_address'] ?? config('blockchain.document_registry_v2_address'),
                    'explorer_url' => 'https://sepolia.etherscan.io/tx/' . $result['transaction_hash'],
                    'network' => 'sepolia',
                    'status' => 'pending'
                ],
                'issuer' => [
                    'name' => $issuer['name'],
                    'country' => $issuer['country']
                ],
                'holder' => [
                    'name' => $holder['full_name']
                ],
                'validity' => [
                    'issued_date' => $validity['issued_date'],
                    'expiry_date' => $validity['expiry_date'] ?? null,
                    'is_permanent' => $validity['is_permanent'] ?? false
                ],
                'registered_at' => now()->toIso8601String()
            ],
            'storage_model' => 'direct_blockchain',
            'note' => 'All metadata stored directly on blockchain. Check transaction status on Etherscan.'
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
