<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'document_id' => '0x' . bin2hex(random_bytes(32)),
            'issuer_id' => User::factory(),
            'holder_email' => fake()->email(),
            'holder_name' => fake()->name(),
            'title' => fake()->randomElement([
                'Bachelor Degree Certificate',
                'Master Degree Certificate',
                'Experience Letter',
                'Academic Transcript',
                'Legal Certificate',
            ]),
            'document_type' => fake()->randomElement([
                'certificate',
                'experience_letter',
                'transcript',
                'legal_document',
                'other',
            ]),
            'file_path' => 'documents/' . Str::random(40) . '.pdf',
            'file_hash' => hash('sha256', Str::random(100)),
            'original_filename' => 'document_' . fake()->randomNumber(5) . '.pdf',
            'file_size' => fake()->numberBetween(50000, 5000000),
            'metadata' => [
                'institution_name' => fake()->company(),
                'issue_date' => fake()->date(),
                'certificate_number' => 'CERT-' . fake()->year() . '-' . fake()->randomNumber(5),
            ],
            'expiry_date' => fake()->optional(0.3)->dateTimeBetween('now', '+5 years'),
            'blockchain_tx_hash' => '0x' . bin2hex(random_bytes(32)),
            'blockchain_status' => fake()->randomElement(['pending', 'confirmed', 'failed']),
            'block_number' => fake()->optional()->randomNumber(8),
            'previous_version_id' => null,
            'is_revoked' => false,
            'revoked_at' => null,
            'revoked_reason' => null,
        ];
    }

    /**
     * Indicate that the document is revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_reason' => fake()->sentence(),
        ]);
    }

    /**
     * Indicate that the document is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expiry_date' => fake()->dateTimeBetween('-2 years', '-1 day'),
        ]);
    }

    /**
     * Indicate that the document is confirmed on blockchain.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'blockchain_status' => 'confirmed',
            'block_number' => fake()->randomNumber(8),
        ]);
    }

    /**
     * Indicate that the document is pending on blockchain.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'blockchain_status' => 'pending',
            'block_number' => null,
        ]);
    }
}
