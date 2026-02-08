<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerifiedOrganization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class FraudDetectionController extends Controller
{
    /**
     * Analyze uploaded certificate for fraud indicators
     * 
     * POST /api/fraud-detection/analyze
     */
    public function analyzeCertificate(Request $request)
    {
        // Validate organization API key
        $apiKey = $request->header('X-Organization-Key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'Organization API key required in X-Organization-Key header'
            ], 401);
        }

        $organization = VerifiedOrganization::verifyByApiKey($apiKey);
        
        if (!$organization) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid or inactive organization'
            ], 403);
        }

        // Validate request - only PDF is required, other fields optional
        $validated = $request->validate([
            'certificate' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'document_type' => 'nullable|string|in:certificate,diploma,license,degree,transcript',
            'issuer_name' => 'nullable|string|max:255',
            'holder_name' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
        ]);

        try {
            // Extract text from PDF
            $pdfText = $this->extractPdfText($request->file('certificate'));

            if (empty($pdfText)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unable to extract text from PDF. Document may be image-based or corrupted.'
                ], 400);
            }

            // Perform AI fraud detection
            $fraudAnalysis = $this->detectFraud($pdfText, $validated);

            // Log the analysis
            Log::info('Fraud detection completed', [
                'organization_id' => $organization->id,
                'organization_name' => $organization->organization_name,
                'fraud_score' => $fraudAnalysis['fraud_score'],
                'risk_level' => $fraudAnalysis['risk_level'],
            ]);

            return response()->json([
                'success' => true,
                'organization' => [
                    'name' => $organization->organization_name,
                    'id' => $organization->id,
                ],
                'analysis' => $fraudAnalysis,
                'document_info' => array_filter([
                    'type' => $validated['document_type'] ?? 'unknown',
                    'issuer' => $validated['issuer_name'] ?? null,
                    'holder' => $validated['holder_name'] ?? null,
                    'issue_date' => $validated['issue_date'] ?? null,
                ]),
                'analyzed_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Fraud detection failed', [
                'organization_id' => $organization->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Fraud detection analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract text from PDF file
     */
    private function extractPdfText($pdfFile): string
    {
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfFile->getPathname());
            $text = $pdf->getText();
            
            // Clean up text
            $text = preg_replace('/\s+/', ' ', $text);
            $text = trim($text);
            
            return $text;
        } catch (\Exception $e) {
            Log::error('PDF text extraction failed', ['error' => $e->getMessage()]);
            throw new \Exception('Failed to extract PDF text: ' . $e->getMessage());
        }
    }

    /**
     * Perform AI-based fraud detection using OpenAI
     */
    private function detectFraud(string $documentText, array $metadata): array
    {
        $openaiKey = config('services.openai.api_key');
        
        if (!$openaiKey) {
            throw new \Exception('OpenAI API key not configured');
        }

        // Prepare analysis prompt
        $prompt = $this->buildFraudDetectionPrompt($documentText, $metadata);

        // Call OpenAI API
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $openaiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => 'gpt-4o-mini',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert document fraud detection system. Analyze certificates and documents for authenticity, fraud indicators, and tampering. Provide detailed, objective analysis.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 1500,
        ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $result = $response->json();
        $analysisText = $result['choices'][0]['message']['content'] ?? '';

        // Parse AI response (expecting JSON format from AI)
        return $this->parseAiResponse($analysisText);
    }

    /**
     * Build fraud detection prompt for AI
     */
    private function buildFraudDetectionPrompt(string $documentText, array $metadata): string
    {
        $docType = $metadata['document_type'] ?? 'document';
        $metadataText = '';
        
        if (!empty($metadata['document_type'])) {
            $metadataText .= "- Type: {$metadata['document_type']}\n";
        }
        if (!empty($metadata['issuer_name'])) {
            $metadataText .= "- Issuer: {$metadata['issuer_name']}\n";
        }
        if (!empty($metadata['holder_name'])) {
            $metadataText .= "- Holder: {$metadata['holder_name']}\n";
        }
        if (!empty($metadata['issue_date'])) {
            $metadataText .= "- Issue Date: {$metadata['issue_date']}\n";
        }
        
        $metadataSection = $metadataText 
            ? "**DOCUMENT METADATA:**\n{$metadataText}\n" 
            : "**DOCUMENT METADATA:** Not provided - analyze the extracted text below\n\n";
        
        return <<<PROMPT
Analyze this {$docType} for fraud indicators and authenticity issues.

{$metadataSection}**EXTRACTED DOCUMENT TEXT:**
{$documentText}

**ANALYSIS REQUIRED:**

Provide a JSON response with the following structure:

{
  "fraud_score": <number 0-100, where 0 is no fraud risk, 100 is definite fraud>,
  "risk_level": "<low|medium|high|critical>",
  "is_suspicious": <boolean>,
  "confidence": <number 0-100>,
  "fraud_indicators": [
    {
      "type": "<type of indicator>",
      "description": "<detailed description>",
      "severity": "<low|medium|high>"
    }
  ],
  "authenticity_checks": {
    "formatting_consistent": <boolean>,
    "language_professional": <boolean>,
    "dates_logical": <boolean>,
    "issuer_mentioned": <boolean>,
    "holder_mentioned": <boolean>,
    "signatures_references": <boolean>
  },
  "red_flags": [
    "<specific red flag 1>",
    "<specific red flag 2>"
  ],
  "recommendations": [
    "<recommendation 1>",
    "<recommendation 2>"
  ],
  "summary": "<brief summary of findings>"
}

Check for:
1. Inconsistent formatting or fonts
2. Suspicious dates (future dates, illogical sequences)
3. Grammar/spelling errors in official documents
4. Missing standard certificate elements
5. Unusual language or phrasing
6. Mismatch between metadata and document content
7. Generic or template-like language
8. Missing institution details or contact info
9. Suspicious claims or qualifications
10. Any signs of alteration or manipulation

Be thorough and objective. Only return valid JSON.
PROMPT;
    }

    /**
     * Parse AI response into structured format
     */
    private function parseAiResponse(string $aiResponse): array
    {
        // Try to extract JSON from response
        $jsonMatch = [];
        if (preg_match('/\{[\s\S]*\}/', $aiResponse, $jsonMatch)) {
            $aiResponse = $jsonMatch[0];
        }

        $parsed = json_decode($aiResponse, true);

        if (!$parsed) {
            // Fallback if AI didn't return valid JSON
            return [
                'fraud_score' => 50,
                'risk_level' => 'medium',
                'is_suspicious' => true,
                'confidence' => 30,
                'fraud_indicators' => [],
                'authenticity_checks' => [],
                'red_flags' => ['Unable to parse AI analysis'],
                'recommendations' => ['Manual review recommended'],
                'summary' => 'Analysis parsing failed. Manual review required.',
                'raw_response' => $aiResponse,
            ];
        }

        return $parsed;
    }

    /**
     * Get organization verification status
     * 
     * GET /api/fraud-detection/verify-key
     */
    public function verifyApiKey(Request $request)
    {
        $apiKey = $request->header('X-Organization-Key');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API key required'
            ], 401);
        }

        $organization = VerifiedOrganization::verifyByApiKey($apiKey);

        if (!$organization) {
            return response()->json([
                'success' => false,
                'verified' => false,
                'error' => 'Invalid or inactive organization'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'verified' => true,
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->organization_name,
                'country' => $organization->country_code,
                'status' => $organization->status,
                'verified_at' => $organization->verified_at,
            ]
        ]);
    }
}
