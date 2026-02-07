<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attestations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->onDelete('cascade');
            $table->foreignId('lawyer_id')->constrained('users')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->string('blockchain_tx_hash', 66)->nullable();
            $table->string('blockchain_status')->default('pending'); // pending, confirmed, failed
            $table->timestamps();

            $table->index('document_id');
            $table->index('lawyer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attestations');
    }
};
