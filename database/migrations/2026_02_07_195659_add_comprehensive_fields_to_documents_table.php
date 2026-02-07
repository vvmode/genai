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
        Schema::table('documents', function (Blueprint $table) {
            // Document fields
            $table->string('document_type')->nullable()->after('hash');
            $table->string('document_number')->nullable()->after('document_type');
            $table->string('document_title')->nullable()->after('document_number');
            $table->string('document_category')->nullable()->after('document_title');
            $table->string('document_language', 10)->default('en')->after('document_category');
            
            // Validity fields
            $table->date('issued_date')->nullable()->after('document_language');
            $table->date('effective_from')->nullable()->after('issued_date');
            $table->date('effective_until')->nullable()->after('effective_from');
            $table->date('expiry_date')->nullable()->after('effective_until');
            $table->boolean('is_permanent')->default(false)->after('expiry_date');
            $table->string('validity_status')->default('active')->after('is_permanent');
            
            // Issuer fields
            $table->string('issuer_name')->nullable()->after('validity_status');
            $table->string('issuer_department')->nullable()->after('issuer_name');
            $table->string('issuer_country', 2)->nullable()->after('issuer_department');
            $table->string('issuer_state')->nullable()->after('issuer_country');
            $table->string('issuer_city')->nullable()->after('issuer_state');
            $table->string('issuer_registration_number')->nullable()->after('issuer_city');
            $table->string('issuer_contact_email')->nullable()->after('issuer_registration_number');
            $table->string('issuer_website')->nullable()->after('issuer_contact_email');
            $table->string('issuer_authorized_signatory')->nullable()->after('issuer_website');
            
            // Holder fields
            $table->string('holder_full_name')->nullable()->after('issuer_authorized_signatory');
            $table->string('holder_id_number')->nullable()->after('holder_full_name');
            $table->date('holder_date_of_birth')->nullable()->after('holder_id_number');
            $table->string('holder_nationality', 2)->nullable()->after('holder_date_of_birth');
            $table->string('holder_email')->nullable()->after('holder_nationality');
            
            // Metadata fields
            $table->text('description')->nullable()->after('holder_email');
            $table->json('metadata')->nullable()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'document_type', 'document_number', 'document_title', 'document_category', 'document_language',
                'issued_date', 'effective_from', 'effective_until', 'expiry_date', 'is_permanent', 'validity_status',
                'issuer_name', 'issuer_department', 'issuer_country', 'issuer_state', 'issuer_city',
                'issuer_registration_number', 'issuer_contact_email', 'issuer_website', 'issuer_authorized_signatory',
                'holder_full_name', 'holder_id_number', 'holder_date_of_birth', 'holder_nationality', 'holder_email',
                'description', 'metadata'
            ]);
        });
    }
};
