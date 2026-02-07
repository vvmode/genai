<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->nullable()->constrained('documents')->onDelete('set null');
            $table->string('verification_hash', 64)->index(); // SHA-256 hash that was checked
            $table->string('result'); // valid, invalid, revoked, expired, corrected
            $table->string('verified_by_ip')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('method'); // file_upload, qr_code, verification_id, hash_lookup
            $table->boolean('blockchain_verified')->default(false);
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index('document_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verifications');
    }
};
