<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->string('action'); // viewed, verified, shared, downloaded, attested
            $table->foreignId('actor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('actor_ip')->nullable();
            $table->foreignId('share_link_id')->nullable()->constrained('share_links')->onDelete('set null');
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('document_id');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_access_logs');
    }
};
