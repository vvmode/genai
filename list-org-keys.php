<?php
/**
 * List All Organizations and Their API Keys
 * Run: php list-org-keys.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\VerifiedOrganization;

echo "\n";
echo "========================================\n";
echo "VERIFIED ORGANIZATIONS\n";
echo "========================================\n\n";

try {
    $organizations = VerifiedOrganization::all();
    
    if ($organizations->isEmpty()) {
        echo "No organizations found.\n";
        echo "Run: php create-test-org.php to create test organizations\n\n";
    } else {
        foreach ($organizations as $index => $org) {
            $num = $index + 1;
            echo "Organization #{$num}\n";
            echo "----------------------------\n";
            echo "Name:           {$org->organization_name}\n";
            echo "Country:        {$org->country_code}\n";
            echo "Email:          {$org->email}\n";
            echo "Status:         {$org->status}\n";
            echo "API Key:        {$org->api_key}\n";
            echo "Verified At:    " . ($org->verified_at ? $org->verified_at->format('Y-m-d H:i:s') : 'Not verified') . "\n";
            echo "Created At:     {$org->created_at->format('Y-m-d H:i:s')}\n";
            echo "\n";
        }
        
        echo "========================================\n";
        echo "SUMMARY\n";
        echo "========================================\n";
        echo "Total:    {$organizations->count()}\n";
        echo "Active:   " . $organizations->where('status', 'active')->count() . "\n";
        echo "Pending:  " . $organizations->where('status', 'pending')->count() . "\n";
        echo "Suspended:" . $organizations->where('status', 'suspended')->count() . "\n";
        echo "\n";
        
        $active = $organizations->where('status', 'active')->first();
        if ($active) {
            echo "========================================\n";
            echo "QUICK TEST API KEY (Copy This):\n";
            echo "========================================\n";
            echo $active->api_key . "\n";
            echo "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
