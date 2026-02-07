<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentApiTest extends TestCase
{
    use RefreshDatabase;

    protected $issuer;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a test issuer user
        $this->issuer = User::factory()->create([
            'role' => 'issuer',
            'name' => 'Test University',
            'email' => 'test@university.edu',
        ]);

        // Create authentication token
        $this->token = $this->issuer->createToken('test-token')->plainTextToken;

        // Fake storage
        Storage::fake('local');
    }

    /** @test */
    public function it_can_register_a_document()
    {
        // Skip if blockchain is not configured
        if (empty(config('blockchain.wallet.address'))) {
            $this->assertTrue(true, 'Blockchain not configured - test skipped');
            return;
        }

        $file = UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents', [
            'document' => $file,
            'holder_name' => 'John Doe',
            'holder_email' => 'john@example.com',
            'title' => 'Bachelor Degree Certificate',
            'document_type' => 'certificate',
            'metadata' => [
                'institution_name' => 'Test University',
                'certificate_number' => 'CERT-2024-001',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'document_uuid',
                    'document_id',
                    'file_hash',
                    'blockchain_tx_hash',
                    'blockchain_status',
                ],
            ]);

        // Verify document was stored in database
        $this->assertDatabaseHas('documents', [
            'holder_name' => 'John Doe',
            'holder_email' => 'john@example.com',
            'title' => 'Bachelor Degree Certificate',
            'document_type' => 'certificate',
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_register_document()
    {
        $file = UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/documents', [
            'document' => $file,
            'holder_name' => 'John Doe',
            'holder_email' => 'john@example.com',
            'title' => 'Certificate',
            'document_type' => 'certificate',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_document_registration_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents', [
            // Missing required fields
            'holder_name' => 'John Doe',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document', 'holder_email', 'title', 'document_type']);
    }

    /** @test */
    public function it_can_verify_document_without_authentication()
    {
        // Create a test document
        $document = Document::factory()->create([
            'issuer_id' => $this->issuer->id,
            'holder_name' => 'John Doe',
            'title' => 'Test Certificate',
            'document_type' => 'certificate',
            'blockchain_status' => 'confirmed',
        ]);

        $response = $this->postJson('/api/documents/verify', [
            'verification_code' => $document->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'valid',
            ]);
    }

    /** @test */
    public function it_returns_not_found_for_non_existent_document()
    {
        $response = $this->postJson('/api/documents/verify', [
            'verification_code' => '00000000-0000-0000-0000-000000000000',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'status' => 'not_found',
            ]);
    }

    /** @test */
    public function it_detects_revoked_documents()
    {
        $document = Document::factory()->create([
            'issuer_id' => $this->issuer->id,
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_reason' => 'Test revocation',
        ]);

        $response = $this->postJson('/api/documents/verify', [
            'verification_code' => $document->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'status' => 'revoked',
            ]);
    }

    /** @test */
    public function it_can_list_documents_for_authenticated_user()
    {
        Document::factory()->count(3)->create([
            'issuer_id' => $this->issuer->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/documents');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(3, count($response->json('data')));
    }

    /** @test */
    public function it_can_get_document_details()
    {
        $document = Document::factory()->create([
            'issuer_id' => $this->issuer->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/documents/' . $document->uuid);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'uuid' => $document->uuid,
                    'title' => $document->title,
                ],
            ]);
    }

    /** @test */
    public function issuer_can_revoke_their_own_document()
    {
        // Skip if blockchain is not configured
        if (empty(config('blockchain.wallet.address'))) {
            $this->assertTrue(true, 'Blockchain not configured - test skipped');
            return;
        }

        $document = Document::factory()->create([
            'issuer_id' => $this->issuer->id,
            'is_revoked' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents/' . $document->uuid . '/revoke', [
            'reason' => 'Information correction required',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Document revoked successfully',
            ]);

        $this->assertDatabaseHas('documents', [
            'uuid' => $document->uuid,
            'is_revoked' => true,
        ]);
    }

    /** @test */
    public function issuer_cannot_revoke_other_issuers_documents()
    {
        $otherIssuer = User::factory()->create(['role' => 'issuer']);
        $document = Document::factory()->create([
            'issuer_id' => $otherIssuer->id,
            'is_revoked' => false,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents/' . $document->uuid . '/revoke', [
            'reason' => 'Test',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_prevents_duplicate_document_hashes()
    {
        // Skip if blockchain is not configured
        if (empty(config('blockchain.wallet.address'))) {
            $this->assertTrue(true, 'Blockchain not configured - test skipped');
            return;
        }

        $file = UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf');
        $hash = DocumentHashService::hashFile($file);

        // Create existing document with same hash
        Document::factory()->create([
            'issuer_id' => $this->issuer->id,
            'file_hash' => $hash,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents', [
            'document' => $file,
            'holder_name' => 'John Doe',
            'holder_email' => 'john@example.com',
            'title' => 'Certificate',
            'document_type' => 'certificate',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Document with this content already exists',
            ]);
    }

    /** @test */
    public function it_validates_document_type()
    {
        $file = UploadedFile::fake()->create('certificate.pdf', 100, 'application/pdf');

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/documents', [
            'document' => $file,
            'holder_name' => 'John Doe',
            'holder_email' => 'john@example.com',
            'title' => 'Certificate',
            'document_type' => 'invalid_type',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type']);
    }
}
