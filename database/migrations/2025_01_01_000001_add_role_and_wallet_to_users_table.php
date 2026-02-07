<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('holder')->after('name'); // admin, issuer, lawyer, holder
            $table->string('wallet_address')->nullable()->unique()->after('role');
            $table->string('organization_name')->nullable()->after('wallet_address');
            $table->boolean('is_approved')->default(false)->after('organization_name');
            $table->timestamp('approved_at')->nullable()->after('is_approved');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'wallet_address', 'organization_name', 'is_approved', 'approved_at']);
        });
    }
};
