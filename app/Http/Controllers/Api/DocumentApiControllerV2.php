<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\BlockchainService;
use App\Services\DocumentHashService;
use App\Services\EncryptionService;
use App\Services\IpfsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentApiController extends Controller
{
    private BlockchainService $blockchainService;
    private DocumentHashService $documentHashService;
    private EncryptionService $encryptionService;
    private IpfsService $ipfsService;

    public function __construct(
        BlockchainService $blockchainService,
        DocumentHashService $documentHashService,
        EncryptionService $encryptionService,
        IpfsService $ipfsService
    ) {
        $this->blockchainService = $blockchainService;
        $this->documentHashService = $documentHashService;
        $this->encryptionService = $encryptionService;
        $this->ipfsService = $ipfsService;
    }

    /**
     * Process document - register or verify
     * Hybrid Storage: Metadata on blockchain, encrypted PDF on IPFS
     */
    public function processDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:register,verify',
            
            // For register action
            'document.pdf_base64' => 'required_if:action,register|string',
            'document.type' => 'required_if:action,register|string',
            'document.number' => 'nullable|string|max:255',
            'document.title' => 'nullable|string|max:500',
            
            'validity.issued_date' => 'nullable|date',
            'validity.effective_from' => 'nullable|date',
            'validity.effective_until' => 'nullable|date',
            'validity.expiry_date' => 'nullable|date',
            'validity.is_permanent' => 'nullable|boolean',
            
            'issuer.name' => 'required_if:action,register|string|max:255',
            'issuer.country' => 'nullable|string|size:2',
            'issuer.registration_number' => 'nullable|string|max:255',
            
            'holder.full_name' => 'nullable|string|max:255',
            'holder.id_number' => 'nullable|string|max:255',
            'holder.nationality' => 'nullable|string|size:2',
            
            'metadata' => 'nullable|array',
            
            // For verify action
            'document_id' => 'required_if:action,verify|string',
            'encryption_key' => 'nullable|string', // Optional for verify
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            if ($request->action === 'verify') {
                return $this->verifyDocument($request);
            } else {
                return $this->registerDocument($request);
            }
        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'action' => $request->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register document with hybrid storage
     */
    private function registerDocument(Request $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate document ID
            $documentId = $request->input('document.number') 
                ?? 'DOC-' . strtoupper(Str::random(16));

            // Check if already exists
            $existing = Document::where('document_id', $documentId)
                ->orWhere('document_number', $documentId)
                ->first();
            
            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document already registered',
                    'data' => [
                        'document_id' => $existing->document_id,
                        'registered_at' => $existing->created_at
                    ]
                ], 409);
            }

            // Get PDF and generate hash
            $pdfBase64 = $request->input('document.pdf_base64');
            $pdfContent = base64_decode($pdfBase64, true);
            
            if ($pdfContent === false) {
                throw new \Exception('Invalid base64 encoded PDF');
            }

            $pdfHash = $this->documentHashService->hashContent($pdfContent);

            // Generate encryption key
            $encryptionKey = $this->encryptionService->generateKey();

            // Encrypt PDF
            $encryptedPdf = $this->encryptionService->encryptPdf($pdfBase64, $encryptionKey);

            // Create encrypted package for IPFS
            $ipfsPackage = json_encode([
                'encrypted_pdf' => $encryptedPdf['encrypted'],
                'iv' => $encryptedPdf['iv'],
                'algorithm' => 'AES-256-CBC',
                'document_id' => $documentId,
                'encrypted_at' => now()->toIso8601String()
            ]);

            // Upload to IPFS
            $ipfsResult = $this->ipfsService->uploadToIpfs($ipfsPackage, [
                'document_id' => $documentId,
                'document_type' => $request->input('document.type')
            ]);

            $ipfsHash = $ipfsResult['ipfs_hash'];

            // Store metadata on blockchain
            $txHash = $this->blockchainService->registerDocumentV2([
                'documentId' => $documentId,
                'documentType' => $request->input('document.type'),
                'documentNumber' => $request->input('document.number', ''),
                'documentTitle' => $request->input('document.title', ''),
                'issuedDate' => strtotime($request->input('validity.issued_date', 'now')),
                'effectiveFrom' => strtotime($request->input('validity.effective_from', 'now')),
                'effectiveUntil' => strtotime($request->input('validity.effective_until', '+1 year')),
                'expiryDate' => $request->input('validity.expiry_date') 
                    ? strtotime($request->input('validity.expiry_date')) : 0,
                'isPermanent' => $request->input('validity.is_permanent', false),
                'issuerName' => $request->input('issuer.name'),
                'issuerCountry' => $request->input('issuer.country', ''),
                'issuerRegistrationNumber' => $request->input('issuer.registration_number', ''),
                'holderFullName' => $request->input('holder.full_name', ''),
                'holderIdNumber' => $request->input('holder.id_number', ''),
                'holderNationality' => $request->input('holder.nationality', ''),
                'ipfsHash' => $ipfsHash,
                'pdfHash' => '0x' . $pdfHash,
            ]);

            // Save to local database (for quick lookup)
            $document = Document::create([
                'document_id' => $documentId,
                'hash' => $pdfHash,
                'file_hash' => $pdfHash,
                'blockchain_tx_hash' => $txHash,
                'blockchain_status' => 'pending',
                
                'document_type' => $request->input('document.type'),
                'document_number' => $request->input('document.number'),
                'document_title' => $request->input('document.title'),
                'document_category' => $request->input('document.category'),
                
                'issued_date' => $request->input('validity.issued_date'),
                'expiry_date' => $request->input('validity.expiry_date'),
                'is_permanent' => $request->input('validity.is_permanent', false),
                
                'issuer_name' => $request->input('issuer.name'),
                'issuer_country' => $request->input('issuer.country'),
                'issuer_registration_number' => $request->input('issuer.registration_number'),
                
                'holder_full_name' => $request->input('holder.full_name'),
                'holder_name' => $request->input('holder.full_name'),
                'holder_id_number' => $request->input('holder.id_number'),
                'holder_nationality' => $request->input('holder.nationality'),
                
                'metadata' => [
                    'ipfs_hash' => $ipfsHash,
                    'ipfs_gateway' => $ipfsResult['gateway_url'],
                    'encrypted' => true,
                    ...$request->input('metadata', [])
                ],
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'action' => 'registered',
                'message' => 'Document registered successfully with hybrid storage',
                'data' => [
                    'document_id' => $documentId,
                    'pdf_hash' => $pdfHash,
                    'encryption_key' => $encryptionKey,
                    'ipfs' => [
                        'hash' => $ipfsHash,
                        'gateway_url' => $ipfsResult['gateway_url'],
                        'pin_size' => $ipfsResult['pin_size']
                    ],
                    'blockchain' => [
                        'transaction_hash' => $txHash,
                        'explorer_url' => config('blockchain.network.explorer_url') . '/tx/' . $txHash,
                        'status' => 'pending'
                    ],
                    'storage_model' => 'hybrid',
                    'metadata_location' => 'blockchain',
                    'pdf_location' => 'ipfs_encrypted',
                    'registered_at' => $document->created_at
                ],
                'warning' => '⚠️ IMPORTANT: Save the encryption_key! You need it to decrypt and retrieve the PDF later.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document registration failed', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to register document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify document and optionally retrieve PDF
     */
    private function verifyDocument(Request $request): JsonResponse
    {
        try {
            $documentId = $request->input('document_id');
            $encryptionKey = $request->input('encryption_key');

            // Get metadata from blockchain
            $blockchainData = $this->blockchainService->getDocumentMetadata($documentId);

            if (!$blockchainData || !$blockchainData['exists']) {
                return response()->json([
                    'success' => true,
                    'action' => 'verified',
                    'verified' => false,
                    'message' => 'Document not found in blockchain registry'
                ], 200);
            }

            // Get from local DB for quick access
            $document = Document::where('document_id', $documentId)->first();

            // Prepare response data
            $responseData = [
                'document_id' => $documentId,
                'verified' => true,
                'document' => [
                    'type' => $blockchainData['documentType'],
                    'number' => $blockchainData['documentNumber'],
                    'title' => $blockchainData['documentTitle'],
                ],
                'validity' => [
                    'issued_date' => date('Y-m-d', $blockchainData['issuedDate']),
                    'expiry_date' => $blockchainData['expiryDate'] > 0 
                        ? date('Y-m-d', $blockchainData['expiryDate']) : null,
                    'is_permanent' => $blockchainData['isPermanent'],
                    'is_expired' => $blockchainData['expiryDate'] > 0 && time() > $blockchainData['expiryDate'],
                ],
                'issuer' => [
                    'name' => $blockchainData['issuerName'],
                    'country' => $blockchainData['issuerCountry'],
                    'registration_number' => $blockchainData['issuerRegistrationNumber'],
                ],
                'holder' => [
                    'full_name' => $blockchainData['holderFullName'],
                    'id_number' => $blockchainData['holderIdNumber'],
                    'nationality' => $blockchainData['holderNationality'],
                ],
                'blockchain' => [
                    'ipfs_hash' => $blockchainData['ipfsHash'],
                    'pdf_hash' => $blockchainData['pdfHash'],
                    'revoked' => $blockchainData['revoked'],
                    'registered_at' => date('Y-m-d H:i:s', $blockchainData['registeredAt']),
                ],
            ];

            // If encryption key provided, retrieve and decrypt PDF
            if ($encryptionKey) {
                try {
                    $ipfsData = $this->ipfsService->retrieveFromIpfs($blockchainData['ipfsHash']);
                    
                    $decryptedPdf = $this->encryptionService->decrypt(
                        $ipfsData['encrypted_pdf'],
                        $encryptionKey,
                        $ipfsData['iv']
                    );

                    $responseData['document']['pdf_base64'] = $decryptedPdf;
                    $responseData['pdf_retrieved'] = true;

                } catch (\Exception $e) {
                    $responseData['pdf_retrieved'] = false;
                    $responseData['pdf_error'] = 'Failed to retrieve or decrypt PDF: ' . $e->getMessage();
                }
            } else {
                $responseData['pdf_retrieved'] = false;
                $responseData['note'] = 'Provide encryption_key to retrieve the PDF';
            }

            // Add metadata from database if available
            if ($document && $document->metadata) {
                $responseData['metadata'] = $document->metadata;
            }

            return response()->json([
                'success' => true,
                'action' => 'verified',
                'verified' => !$blockchainData['revoked'],
                'message' => $blockchainData['revoked'] 
                    ? 'Document is revoked' 
                    : 'Document is authentic and valid',
                'data' => $responseData,
                'storage_model' => 'hybrid',
                'note' => 'Metadata retrieved from blockchain. PDF requires encryption_key.'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Document verification failed', [
                'document_id' => $request->input('document_id'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
