<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('document_id', 66)->unique(); // bytes32 hex for on-chain
            $table->foreignId('issuer_id')->constrained('users')->onDelete('cascade');
            $table->string('holder_email')->nullable()->index();
            $table->string('holder_name')->nullable();
            $table->string('title');
            $table->string('document_type'); // certificate, experience_letter, legal_doc, transcript, other
            $table->string('file_path');
            $table->string('file_hash', 64)->index(); // SHA-256 hex
            $table->string('original_filename');
            $table->unsignedInteger('file_size');
            $table->json('metadata')->nullable(); // AI-extracted metadata
            $table->timestamp('expiry_date')->nullable();
            $table->string('blockchain_tx_hash', 66)->nullable();
            $table->string('blockchain_status')->default('pending'); // pending, confirmed, failed
            $table->unsignedInteger('block_number')->nullable();
            $table->foreignId('previous_version_id')->nullable()->constrained('documents')->onDelete('set null');
            $table->boolean('is_revoked')->default(false);
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index('issuer_id');
            $table->index('blockchain_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
