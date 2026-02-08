<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class VerifiedOrganization extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_name',
        'registration_number',
        'country_code',
        'api_key',
        'email',
        'contact_person',
        'address',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Generate a unique API key for organization
     */
    public static function generateApiKey(): string
    {
        return 'org_' . Str::random(40);
    }

    /**
     * Check if organization is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Verify organization by API key
     */
    public static function verifyByApiKey(string $apiKey): ?self
    {
        return self::where('api_key', $apiKey)
            ->where('status', 'active')
            ->first();
    }
}
