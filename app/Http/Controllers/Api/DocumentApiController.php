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
            'pdf_base64' => 'required|string',
            'issuer_name' => 'required|string|max:255',
            'document_type' => 'required|string|in:certificate,transcript,diploma,license,deed,contract,other',
            'holder_name' => 'nullable|string|max:255',
            'expiry_date' => 'nullable|date|after:today',
            'metadata' => 'nullable|array',
            'action' => 'required|string|in:register,verify'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Decode base64 PDF
            $pdfContent = base64_decode($request->pdf_base64, true);
            
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
            $existing = Document::where('document_hash', $documentHash)->first();
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
            $documentId = 'DOC-' . strtoupper(Str::random(16));
            
            // Store PDF
            $filename = $documentId . '.pdf';
            Storage::disk('local')->put('documents/' . $filename, $pdfContent);

            // Register on blockchain
            $expiryTimestamp = $request->expiry_date 
                ? strtotime($request->expiry_date) 
                : 0;

            $txHash = $this->blockchainService->registerDocument(
                $documentId,
                $documentHash,
                $request->document_type,
                $expiryTimestamp
            );

            // Save to database
            $document = Document::create([
                'document_id' => $documentId,
                'issuer_name' => $request->issuer_name,
                'holder_name' => $request->holder_name,
                'document_type' => $request->document_type,
                'document_hash' => $documentHash,
                'file_path' => 'documents/' . $filename,
                'blockchain_tx_hash' => $txHash,
                'expiry_date' => $request->expiry_date,
                'metadata' => $request->metadata ?? [],
                'status' => 'pending'
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'action' => 'registered',
                'message' => 'Document registered successfully on blockchain',
                'data' => [
                    'document_id' => $document->document_id,
                    'document_hash' => $documentHash,
                    'issuer_name' => $document->issuer_name,
                    'document_type' => $document->document_type,
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
            $document = Document::where('document_hash', $documentHash)->first();

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
                    'issuer_name' => $document->issuer_name,
                    'holder_name' => $document->holder_name,
                    'document_type' => $document->document_type,
                    'issue_date' => $document->created_at,
                    'expiry_date' => $document->expiry_date,
                    'status' => $document->status,
                    'revoked' => $blockchainVerification['revoked'] ?? false,
                    'blockchain_status' => $document->blockchain_status,
                    'transaction_hash' => $document->blockchain_tx_hash,
                    'blockchain_explorer' => config('blockchain.network.explorer_url') . '/tx/' . $document->blockchain_tx_hash
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
