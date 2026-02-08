<?php
/**
 * Create Test Organizations for Fraud Detection
 * Run: php create-test-org.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VerifiedOrganization;

echo "=====================================\n";
echo "Creating Test Organizations\n";
echo "=====================================\n\n";

try {
    // Create Organization 1
    $org1 = VerifiedOrganization::create([
        'organization_name' => 'Test University',
        'registration_number' => 'TEST-UNI-2026-001',
        'country_code' => 'US',
        'api_key' => VerifiedOrganization::generateApiKey(),
        'email' => 'admin@testuniversity.edu',
        'contact_person' => 'Dr. John Smith',
        'address' => '123 University Ave, Boston, MA 02115',
        'status' => 'active',
        'verified_at' => now(),
    ]);

    echo "✓ Organization 1 Created\n";
    echo "  Name: {$org1->organization_name}\n";
    echo "  Status: {$org1->status}\n";
    echo "  API Key: {$org1->api_key}\n\n";

    // Create Organization 2
    $org2 = VerifiedOrganization::create([
        'organization_name' => 'Global Certification Institute',
        'registration_number' => 'GCI-2026-456',
        'country_code' => 'UK',
        'api_key' => VerifiedOrganization::generateApiKey(),
        'email' => 'verify@globalcert.org',
        'contact_person' => 'Sarah Johnson',
        'address' => '45 Oxford Street, London, UK',
        'status' => 'active',
        'verified_at' => now(),
    ]);

    echo "✓ Organization 2 Created\n";
    echo "  Name: {$org2->organization_name}\n";
    echo "  Status: {$org2->status}\n";
    echo "  API Key: {$org2->api_key}\n\n";

    // Create Organization 3 (inactive for testing)
    $org3 = VerifiedOrganization::create([
        'organization_name' => 'Pending Academy',
        'registration_number' => 'PEND-2026-789',
        'country_code' => 'CA',
        'api_key' => VerifiedOrganization::generateApiKey(),
        'email' => 'info@pendingacademy.ca',
        'contact_person' => 'Michael Brown',
        'status' => 'pending',
        'verified_at' => null,
    ]);

    echo "✓ Organization 3 Created (INACTIVE for testing)\n";
    echo "  Name: {$org3->organization_name}\n";
    echo "  Status: {$org3->status}\n";
    echo "  API Key: {$org3->api_key}\n\n";

    echo "=====================================\n";
    echo "SUMMARY\n";
    echo "=====================================\n";
    echo "Total Organizations: " . VerifiedOrganization::count() . "\n";
    echo "Active Organizations: " . VerifiedOrganization::where('status', 'active')->count() . "\n\n";

    echo "=====================================\n";
    echo "USE THESE API KEYS FOR TESTING:\n";
    echo "=====================================\n";
    echo "Organization 1: {$org1->api_key}\n";
    echo "Organization 2: {$org2->api_key}\n";
    echo "(Organization 3 is inactive - won't work)\n\n";

    echo "✓ Success! Organizations created.\n\n";
    echo "Next step: Test in Postman with X-Organization-Key header\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    
    // If organizations already exist, just show existing ones
    if (strpos($e->getMessage(), 'UNIQUE constraint') !== false) {
        echo "\nOrganizations might already exist. Fetching existing...\n\n";
        
        $existing = VerifiedOrganization::where('status', 'active')->get();
        
        if ($existing->count() > 0) {
            echo "=====================================\n";
            echo "EXISTING ACTIVE ORGANIZATIONS:\n";
            echo "=====================================\n";
            
            foreach ($existing as $org) {
                echo "Name: {$org->organization_name}\n";
                echo "API Key: {$org->api_key}\n";
                echo "Status: {$org->status}\n\n";
            }
        } else {
            echo "No active organizations found.\n";
        }
    }
}
