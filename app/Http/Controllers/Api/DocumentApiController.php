<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\BlockchainService;
use App\Services\DocumentHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class DocumentApiController extends Controller
{
    private BlockchainService $blockchainService;
    private DocumentHashService $documentHashService;

    public function __construct(
        BlockchainService $blockchainService,
        DocumentHashService $documentHashService
    ) {
        $this->blockchainService = $blockchainService;
        $this->documentHashService = $documentHashService;
    }

    /**
     * Register and verify document in one request (JSON only)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function processDocument(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|string|in:register,verify',
            
            // PDF required for both actions
            'document.pdf_base64' => 'required|string',
            
            // Document fields (required for register)
            'document.type' => 'required_if:action,register|string',
            'document.number' => 'nullable|string|max:255',
            'document.title' => 'nullable|string|max:500',
            'document.category' => 'nullable|string',
            'document.language' => 'nullable|string|max:10',
            
            // Validity fields
            'validity.issued_date' => 'nullable|date',
            'validity.effective_from' => 'nullable|date',
            'validity.effective_until' => 'nullable|date|after:validity.effective_from',
            'validity.expiry_date' => 'nullable|date',
            'validity.is_permanent' => 'nullable|boolean',
            'validity.status' => 'nullable|string|in:active,suspended,revoked,expired',
            
            // Issuer fields (required for register)
            'issuer.name' => 'required_if:action,register|string|max:255',
            'issuer.department' => 'nullable|string|max:255',
            'issuer.country' => 'nullable|string|size:2',
            'issuer.state' => 'nullable|string|max:255',
            'issuer.city' => 'nullable|string|max:255',
            'issuer.registration_number' => 'nullable|string|max:255',
            'issuer.contact_email' => 'nullable|email|max:255',
            'issuer.website' => 'nullable|url|max:255',
            'issuer.authorized_signatory' => 'nullable|string|max:255',
            
            // Holder fields
            'holder.full_name' => 'nullable|string|max:255',
            'holder.id_number' => 'nullable|string|max:255',
            'holder.date_of_birth' => 'nullable|date',
            'holder.nationality' => 'nullable|string|size:2',
            'holder.email' => 'nullable|email|max:255',
            
            // Metadata
            'metadata' => 'nullable|array',
            'metadata.description' => 'nullable|string',
            'metadata.grade' => 'nullable|string',
            'metadata.gpa' => 'nullable|string',
            'metadata.credits' => 'nullable|string',
            'metadata.specialization' => 'nullable|string',
            'metadata.notes' => 'nullable|string',
            'metadata.custom_fields' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Decode base64 PDF
            $pdfContent = base64_decode($request->input('document.pdf_base64'), true);
            
            if ($pdfContent === false || empty($pdfContent)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid base64 encoded PDF'
                ], 400);
            }

            // Generate document hash
            $documentHash = $this->documentHashService->hashContent($pdfContent);

            // Check action
            if ($request->action === 'verify') {
                return $this->verifyDocument($documentHash);
            } else {
                return $this->registerDocument($request, $pdfContent, $documentHash);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Register document on blockchain
     */
    private function registerDocument(Request $request, string $pdfContent, string $documentHash): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Check if document already exists
            $existing = Document::where('file_hash', $documentHash)
                ->orWhere('hash', $documentHash)
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

            // Generate unique document ID
            $documentId = $request->input('document.number') 
                ?? 'DOC-' . strtoupper(Str::random(16));
            
            // Store PDF
            $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $documentId) . '.pdf';
            Storage::disk('local')->put('documents/' . $filename, $pdfContent);

            // Register on blockchain
            $expiryTimestamp = $request->input('validity.expiry_date') 
                ? strtotime($request->input('validity.expiry_date')) 
                : 0;

            $txHash = $this->blockchainService->registerDocument(
                $documentId,
                $documentHash,
                $request->input('document.type'),
                $expiryTimestamp
            );

            // Prepare metadata
            $metadata = $request->input('metadata', []);

            // Save to database
            $document = Document::create([
                'document_id' => $documentId,
                'hash' => $documentHash,
                'file_hash' => $documentHash,
                'file_path' => 'documents/' . $filename,
                'blockchain_tx_hash' => $txHash,
                'blockchain_status' => 'pending',
                
                // Document fields
                'document_type' => $request->input('document.type'),
                'document_number' => $request->input('document.number'),
                'document_title' => $request->input('document.title'),
                'document_category' => $request->input('document.category'),
                'document_language' => $request->input('document.language', 'en'),
                
                // Validity fields
                'issued_date' => $request->input('validity.issued_date'),
                'effective_from' => $request->input('validity.effective_from'),
                'effective_until' => $request->input('validity.effective_until'),
                'expiry_date' => $request->input('validity.expiry_date'),
                'is_permanent' => $request->input('validity.is_permanent', false),
                'validity_status' => $request->input('validity.status', 'active'),
                
                // Issuer fields
                'issuer_name' => $request->input('issuer.name'),
                'issuer_department' => $request->input('issuer.department'),
                'issuer_country' => $request->input('issuer.country'),
                'issuer_state' => $request->input('issuer.state'),
                'issuer_city' => $request->input('issuer.city'),
                'issuer_registration_number' => $request->input('issuer.registration_number'),
                'issuer_contact_email' => $request->input('issuer.contact_email'),
                'issuer_website' => $request->input('issuer.website'),
                'issuer_authorized_signatory' => $request->input('issuer.authorized_signatory'),
                
                // Holder fields
                'holder_full_name' => $request->input('holder.full_name'),
                'holder_name' => $request->input('holder.full_name'), // Legacy field
                'holder_email' => $request->input('holder.email'),
                'holder_id_number' => $request->input('holder.id_number'),
                'holder_date_of_birth' => $request->input('holder.date_of_birth'),
                'holder_nationality' => $request->input('holder.nationality'),
                
                // Metadata
                'description' => $request->input('metadata.description'),
                'metadata' => $metadata,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'action' => 'registered',
                'message' => 'Document registered successfully on blockchain',
                'data' => [
                    'document_id' => $document->document_id,
                    'document_hash' => $documentHash,
                    'document_type' => $document->document_type,
                    'document_title' => $document->document_title,
                    'issuer_name' => $document->issuer_name,
                    'holder_name' => $document->holder_full_name,
                    'issued_date' => $document->issued_date,
                    'expiry_date' => $document->expiry_date,
                    'transaction_hash' => $txHash,
                    'status' => 'pending',
                    'registered_at' => $document->created_at,
                    'blockchain_explorer' => config('blockchain.network.explorer_url') . '/tx/' . $txHash
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to register document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify document authenticity
     */
    private function verifyDocument(string $documentHash): JsonResponse
    {
        try {
            // Look up document in database
            $document = Document::where('file_hash', $documentHash)
                ->orWhere('hash', $documentHash)
                ->first();

            if (!$document) {
                return response()->json([
                    'success' => true,
                    'action' => 'verified',
                    'verified' => false,
                    'message' => 'Document not found in blockchain registry'
                ], 200);
            }

            // Verify on blockchain
            $blockchainVerification = $this->blockchainService->verifyDocument(
                $document->document_id,
                $documentHash
            );

            $isValid = $blockchainVerification['valid'] ?? false;

            return response()->json([
                'success' => true,
                'action' => 'verified',
                'verified' => $isValid,
                'message' => $isValid ? 'Document is authentic and valid' : 'Document verification failed',
                'data' => [
                    'document_id' => $document->document_id,
                    'document_hash' => $documentHash,
                    
                    // Document info
                    'document' => [
                        'type' => $document->document_type,
                        'number' => $document->document_number,
                        'title' => $document->document_title,
                        'category' => $document->document_category,
                        'language' => $document->document_language,
                    ],
                    
                    // Validity info
                    'validity' => [
                        'issued_date' => $document->issued_date,
                        'effective_from' => $document->effective_from,
                        'effective_until' => $document->effective_until,
                        'expiry_date' => $document->expiry_date,
                        'is_permanent' => $document->is_permanent,
                        'status' => $document->validity_status,
                    ],
                    
                    // Issuer info
                    'issuer' => [
                        'name' => $document->issuer_name,
                        'department' => $document->issuer_department,
                        'country' => $document->issuer_country,
                        'state' => $document->issuer_state,
                        'city' => $document->issuer_city,
                        'registration_number' => $document->issuer_registration_number,
                        'contact_email' => $document->issuer_contact_email,
                        'website' => $document->issuer_website,
                        'authorized_signatory' => $document->issuer_authorized_signatory,
                    ],
                    
                    // Holder info
                    'holder' => [
                        'full_name' => $document->holder_full_name,
                        'id_number' => $document->holder_id_number,
                        'date_of_birth' => $document->holder_date_of_birth,
                        'nationality' => $document->holder_nationality,
                        'email' => $document->holder_email,
                    ],
                    
                    // Metadata
                    'metadata' => $document->metadata,
                    'description' => $document->description,
                    
                    // Blockchain info
                    'blockchain' => [
                        'revoked' => $blockchainVerification['revoked'] ?? false,
                        'status' => $document->blockchain_status,
                        'transaction_hash' => $document->blockchain_tx_hash,
                        'explorer_url' => config('blockchain.network.explorer_url') . '/tx/' . $document->blockchain_tx_hash,
                    ],
                    
                    'registered_at' => $document->created_at,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify document',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
