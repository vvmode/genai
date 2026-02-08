<?php

namespace Database\Seeders;

use App\Models\VerifiedOrganization;
use Illuminate\Database\Seeder;

class VerifiedOrganizationSeeder extends Seeder
{
    /**
     * Seed verified organizations for testing
     */
    public function run(): void
    {
        // Create test organization
        $testOrg = VerifiedOrganization::create([
            'organization_name' => 'Test University',
            'registration_number' => 'TEST-UNI-2025-001',
            'country_code' => 'US',
            'api_key' => VerifiedOrganization::generateApiKey(),
            'email' => 'admin@testuniversity.edu',
            'contact_person' => 'Dr. John Smith',
            'address' => '123 University Ave, Boston, MA 02115',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->command->info('Test Organization created!');
        $this->command->info('API Key: ' . $testOrg->api_key);
        $this->command->line('');

        // Create sample organization 2
        $sampleOrg = VerifiedOrganization::create([
            'organization_name' => 'Global Certification Institute',
            'registration_number' => 'GCI-2025-456',
            'country_code' => 'UK',
            'api_key' => VerifiedOrganization::generateApiKey(),
            'email' => 'verify@globalcert.org',
            'contact_person' => 'Sarah Johnson',
            'address' => '45 Oxford Street, London, UK',
            'status' => 'active',
            'verified_at' => now(),
        ]);

        $this->command->info('Global Certification Institute created!');
        $this->command->info('API Key: ' . $sampleOrg->api_key);
        $this->command->line('');

        // Create pending organization
        $pendingOrg = VerifiedOrganization::create([
            'organization_name' => 'New Academy',
            'registration_number' => 'NEWACAD-2025-789',
            'country_code' => 'CA',
            'api_key' => VerifiedOrganization::generateApiKey(),
            'email' => 'info@newacademy.ca',
            'contact_person' => 'Michael Brown',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        $this->command->info('Pending Organization created (inactive)!');
        $this->command->info('API Key: ' . $pendingOrg->api_key);
        $this->command->line('');

        $this->command->info('✓ Seeded ' . VerifiedOrganization::count() . ' organizations');
    }
}
