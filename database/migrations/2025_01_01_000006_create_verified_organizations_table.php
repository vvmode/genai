<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('verified_organizations', function (Blueprint $table) {
            $table->id();
            $table->string('organization_name');
            $table->string('registration_number')->unique();
            $table->string('country_code', 2);
            $table->string('api_key')->unique();
            $table->string('email')->unique();
            $table->string('contact_person')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['active', 'suspended', 'pending'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            
            $table->index(['api_key', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verified_organizations');
    }
};
