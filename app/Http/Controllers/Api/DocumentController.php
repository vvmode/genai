<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\VerifyDocumentRequest;
use App\Models\Document;
use App\Models\Verification;
use App\Services\BlockchainService;
use App\Services\DocumentHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    private BlockchainService $blockchainService;

    public function __construct(BlockchainService $blockchainService)
    {
        $this->blockchainService = $blockchainService;
    }

    /**
     * Register a new document on the blockchain
     *
     * @param StoreDocumentRequest $request
     * @return JsonResponse
     */
    public function store(StoreDocumentRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Get authenticated user (issuer)
            $issuer = $request->user();
            if (!$issuer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            // Handle file upload
            $file = $request->file('document');
            $originalFilename = $file->getClientOriginalName();
            $fileSize = $file->getSize();

            // Generate file hash
            $fileHash = DocumentHashService::hashFile($file);

            // Check for duplicate hash
            $existingDoc = Document::where('file_hash', $fileHash)->first();
            if ($existingDoc) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document with this content already exists',
                    'existing_document_id' => $existingDoc->uuid,
                ], 422);
            }

            // Store file
            $filePath = $file->store('documents', 'local');

            // Generate unique document ID for blockchain
            $documentId = BlockchainService::generateDocumentId($fileHash);
            $uuid = Str::uuid()->toString();

            // Prepare metadata
            $metadata = $request->input('metadata', []);
            $expiryDate = $request->input('expiry_date');
            $expiryTimestamp = $expiryDate ? strtotime($expiryDate) : null;

            // Create document record (status: pending)
            $document = Document::create([
                'uuid' => $uuid,
                'document_id' => $documentId,
                'issuer_id' => $issuer->id,
                'holder_email' => $request->input('holder_email'),
                'holder_name' => $request->input('holder_name'),
                'title' => $request->input('title'),
                'document_type' => $request->input('document_type'),
                'file_path' => $filePath,
                'file_hash' => $fileHash,
                'original_filename' => $originalFilename,
                'file_size' => $fileSize,
                'metadata' => $metadata,
                'expiry_date' => $expiryDate,
                'blockchain_status' => 'pending',
            ]);

            // Register on blockchain
            $blockchainResult = $this->blockchainService->registerDocument(
                $documentId,
                DocumentHashService::addHexPrefix($fileHash),
                $request->input('document_type'),
                $expiryTimestamp,
                $metadata
            );

            if ($blockchainResult['success']) {
                // Update document with transaction hash
                $document->update([
                    'blockchain_tx_hash' => $blockchainResult['tx_hash'],
                    'blockchain_status' => 'pending',
                ]);

                DB::commit();

                Log::info('Document registered successfully', [
                    'document_uuid' => $document->uuid,
                    'tx_hash' => $blockchainResult['tx_hash'],
                    'issuer_id' => $issuer->id,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Document registered successfully',
                    'data' => [
                        'document_uuid' => $document->uuid,
                        'document_id' => $document->document_id,
                        'file_hash' => $document->file_hash,
                        'blockchain_tx_hash' => $blockchainResult['tx_hash'],
                        'blockchain_status' => 'pending',
                        'explorer_url' => config('blockchain.network.explorer_url') . '/tx/' . $blockchainResult['tx_hash'],
                    ],
                ], 201);

            } else {
                // Blockchain registration failed
                DB::rollBack();

                // Clean up uploaded file
                Storage::disk('local')->delete($filePath);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to register document on blockchain',
                    'error' => $blockchainResult['error'] ?? 'Unknown error',
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Document registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while registering the document',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Verify a document against blockchain records
     *
     * @param VerifyDocumentRequest $request
     * @return JsonResponse
     */
    public function verify(VerifyDocumentRequest $request): JsonResponse
    {
        try {
            $verificationMethod = null;
            $document = null;
            $fileHash = null;

            // Method 1: Verify by uploaded file
            if ($request->hasFile('document')) {
                $verificationMethod = 'file_upload';
                $file = $request->file('document');
                $fileHash = DocumentHashService::hashFile($file);
                
                // Find document by hash
                $document = Document::where('file_hash', $fileHash)->first();
            }
            // Method 2: Verify by document ID
            elseif ($request->has('document_id')) {
                $verificationMethod = 'document_id';
                $documentId = $request->input('document_id');
                
                // Find document by blockchain document ID
                $document = Document::where('document_id', $documentId)->first();
            }
            // Method 3: Verify by verification code (UUID)
            elseif ($request->has('verification_code')) {
                $verificationMethod = 'verification_code';
                $verificationCode = $request->input('verification_code');
                
                // Find document by UUID
                $document = Document::where('uuid', $verificationCode)->first();
            }

            // Document not found in database
            if (!$document) {
                // Log verification attempt
                Verification::create([
                    'verification_method' => $verificationMethod,
                    'verification_result' => 'not_found',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'not_found',
                    'message' => 'Document not found in our records',
                ], 404);
            }

            // Check if document is revoked
            if ($document->is_revoked) {
                Verification::create([
                    'document_id' => $document->id,
                    'verification_method' => $verificationMethod,
                    'verification_result' => 'revoked',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'revoked',
                    'message' => 'This document has been revoked',
                    'data' => [
                        'document_uuid' => $document->uuid,
                        'title' => $document->title,
                        'revoked_at' => $document->revoked_at,
                        'revoked_reason' => $document->revoked_reason,
                    ],
                ], 200);
            }

            // Check if document is expired
            if ($document->isExpired()) {
                Verification::create([
                    'document_id' => $document->id,
                    'verification_method' => $verificationMethod,
                    'verification_result' => 'expired',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => true,
                    'status' => 'expired',
                    'message' => 'This document has expired',
                    'data' => [
                        'document_uuid' => $document->uuid,
                        'title' => $document->title,
                        'expiry_date' => $document->expiry_date,
                    ],
                ], 200);
            }

            // Verify hash if file was uploaded
            if ($fileHash && !hash_equals($document->file_hash, $fileHash)) {
                Verification::create([
                    'document_id' => $document->id,
                    'verification_method' => $verificationMethod,
                    'verification_result' => 'invalid',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);

                return response()->json([
                    'success' => false,
                    'status' => 'invalid',
                    'message' => 'Document content does not match our records',
                ], 200);
            }

            // Verify on blockchain (optional - check if confirmed)
            $blockchainVerified = false;
            if ($document->blockchain_status === 'confirmed') {
                $blockchainResult = $this->blockchainService->verifyDocument($document->document_id);
                $blockchainVerified = $blockchainResult['success'] ?? false;
            }

            // Log successful verification
            Verification::create([
                'document_id' => $document->id,
                'verification_method' => $verificationMethod,
                'verification_result' => 'valid',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'success' => true,
                'status' => 'valid',
                'message' => 'Document is authentic and valid',
                'data' => [
                    'document_uuid' => $document->uuid,
                    'document_id' => $document->document_id,
                    'title' => $document->title,
                    'document_type' => $document->document_type,
                    'holder_name' => $document->holder_name,
                    'issuer' => [
                        'name' => $document->issuer->name,
                        'email' => $document->issuer->email,
                    ],
                    'issue_date' => $document->created_at,
                    'expiry_date' => $document->expiry_date,
                    'blockchain_tx_hash' => $document->blockchain_tx_hash,
                    'blockchain_status' => $document->blockchain_status,
                    'blockchain_verified' => $blockchainVerified,
                    'metadata' => $document->metadata,
                    'explorer_url' => $document->blockchain_tx_hash 
                        ? config('blockchain.network.explorer_url') . '/tx/' . $document->blockchain_tx_hash
                        : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Document verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during verification',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Revoke a document
     *
     * @param string $uuid Document UUID
     * @param Request $request
     * @return JsonResponse
     */
    public function revoke(string $uuid, Request $request): JsonResponse
    {
        try {
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $document = Document::where('uuid', $uuid)->firstOrFail();

            // Check authorization - only issuer can revoke
            if ($document->issuer_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to revoke this document',
                ], 403);
            }

            // Check if already revoked
            if ($document->is_revoked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document is already revoked',
                ], 422);
            }

            DB::beginTransaction();

            // Revoke on blockchain
            $blockchainResult = $this->blockchainService->revokeDocument(
                $document->document_id,
                $request->input('reason')
            );

            if ($blockchainResult['success']) {
                // Update document status
                $document->update([
                    'is_revoked' => true,
                    'revoked_at' => now(),
                    'revoked_reason' => $request->input('reason'),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Document revoked successfully',
                    'data' => [
                        'document_uuid' => $document->uuid,
                        'revoked_at' => $document->revoked_at,
                        'blockchain_tx_hash' => $blockchainResult['tx_hash'],
                    ],
                ], 200);

            } else {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to revoke document on blockchain',
                    'error' => $blockchainResult['error'] ?? 'Unknown error',
                ], 500);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Document revocation failed', [
                'error' => $e->getMessage(),
                'document_uuid' => $uuid,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while revoking the document',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get document details
     *
     * @param string $uuid Document UUID
     * @return JsonResponse
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $document = Document::with(['issuer', 'verifications', 'attestations'])
                ->where('uuid', $uuid)
                ->firstOrFail();

            // Check transaction status if pending
            if ($document->blockchain_status === 'pending' && $document->blockchain_tx_hash) {
                $receipt = $this->blockchainService->getTransactionReceipt($document->blockchain_tx_hash);
                
                if ($receipt && isset($receipt['status'])) {
                    $status = hexdec($receipt['status']) === 1 ? 'confirmed' : 'failed';
                    $blockNumber = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;
                    
                    $document->update([
                        'blockchain_status' => $status,
                        'block_number' => $blockNumber,
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'uuid' => $document->uuid,
                    'document_id' => $document->document_id,
                    'title' => $document->title,
                    'document_type' => $document->document_type,
                    'holder_name' => $document->holder_name,
                    'holder_email' => $document->holder_email,
                    'file_hash' => $document->file_hash,
                    'file_size' => $document->file_size,
                    'original_filename' => $document->original_filename,
                    'issuer' => [
                        'id' => $document->issuer->id,
                        'name' => $document->issuer->name,
                        'email' => $document->issuer->email,
                    ],
                    'blockchain_tx_hash' => $document->blockchain_tx_hash,
                    'blockchain_status' => $document->blockchain_status,
                    'block_number' => $document->block_number,
                    'is_revoked' => $document->is_revoked,
                    'revoked_at' => $document->revoked_at,
                    'revoked_reason' => $document->revoked_reason,
                    'expiry_date' => $document->expiry_date,
                    'is_expired' => $document->isExpired(),
                    'metadata' => $document->metadata,
                    'created_at' => $document->created_at,
                    'updated_at' => $document->updated_at,
                    'verifications_count' => $document->verifications->count(),
                    'attestations_count' => $document->attestations->count(),
                    'explorer_url' => $document->blockchain_tx_hash 
                        ? config('blockchain.network.explorer_url') . '/tx/' . $document->blockchain_tx_hash
                        : null,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Document not found',
            ], 404);
        }
    }

    /**
     * List all documents for authenticated issuer
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            $documents = Document::where('issuer_id', $user->id)
                ->with('issuer')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $documents->items(),
                'pagination' => [
                    'current_page' => $documents->currentPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                    'last_page' => $documents->lastPage(),
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to list documents', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching documents',
            ], 500);
        }
    }

    /**
     * Check blockchain transaction status
     *
     * @param string $uuid Document UUID
     * @return JsonResponse
     */
    public function checkTransactionStatus(string $uuid): JsonResponse
    {
        try {
            $document = Document::where('uuid', $uuid)->firstOrFail();

            if (!$document->blockchain_tx_hash) {
                return response()->json([
                    'success' => false,
                    'message' => 'No blockchain transaction found for this document',
                ], 404);
            }

            $receipt = $this->blockchainService->getTransactionReceipt($document->blockchain_tx_hash);

            if (!$receipt) {
                return response()->json([
                    'success' => false,
                    'status' => 'pending',
                    'message' => 'Transaction is still pending',
                ], 200);
            }

            $status = hexdec($receipt['status']) === 1 ? 'confirmed' : 'failed';
            $blockNumber = isset($receipt['blockNumber']) ? hexdec($receipt['blockNumber']) : null;

            // Update document status
            $document->update([
                'blockchain_status' => $status,
                'block_number' => $blockNumber,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'document_uuid' => $document->uuid,
                    'blockchain_status' => $status,
                    'block_number' => $blockNumber,
                    'tx_hash' => $document->blockchain_tx_hash,
                    'explorer_url' => config('blockchain.network.explorer_url') . '/tx/' . $document->blockchain_tx_hash,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error('Failed to check transaction status', [
                'error' => $e->getMessage(),
                'document_uuid' => $uuid,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking transaction status',
            ], 500);
        }
    }
}
