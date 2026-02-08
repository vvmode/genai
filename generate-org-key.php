<?php
// Quick script to generate organization API key
// Run: php generate-org-key.php

$apiKey = 'org_' . bin2hex(random_bytes(20));

echo "==============================================\n";
echo "Generated Organization API Key:\n";
echo "==============================================\n";
echo $apiKey . "\n";
echo "==============================================\n\n";

echo "To add to database (Railway terminal):\n\n";
echo "php artisan tinker\n\n";
echo "Then paste:\n\n";
echo "use App\\Models\\VerifiedOrganization;\n";
echo "\$org = VerifiedOrganization::create([\n";
echo "    'organization_name' => 'Test Organization',\n";
echo "    'registration_number' => 'TEST-2026-001',\n";
echo "    'country_code' => 'US',\n";
echo "    'api_key' => '$apiKey',\n";
echo "    'email' => 'admin@testorg.com',\n";
echo "    'contact_person' => 'John Doe',\n";
echo "    'status' => 'active',\n";
echo "    'verified_at' => now(),\n";
echo "]);\n\n";
echo "echo 'Organization created with API Key: ' . \$org->api_key;\n\n";
