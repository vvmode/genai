<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'document_id', 'issuer_id', 'holder_email', 'holder_name',
        'title', 'document_type', 'file_path', 'file_hash', 'hash', 'original_filename',
        'file_size', 'metadata', 'expiry_date', 'blockchain_tx_hash',
        'blockchain_status', 'block_number', 'previous_version_id',
        'is_revoked', 'revoked_at', 'revoked_reason',
        // New comprehensive fields
        'document_number', 'document_title', 'document_category', 'document_language',
        'issued_date', 'effective_from', 'effective_until', 'is_permanent', 'validity_status',
        'issuer_name', 'issuer_department', 'issuer_country', 'issuer_state', 'issuer_city',
        'issuer_registration_number', 'issuer_contact_email', 'issuer_website', 'issuer_authorized_signatory',
        'holder_full_name', 'holder_id_number', 'holder_date_of_birth', 'holder_nationality',
        'description',
    ];

    protected $casts = [
        'metadata' => 'array',
        'expiry_date' => 'datetime',
        'revoked_at' => 'datetime',
        'is_revoked' => 'boolean',
        'is_permanent' => 'boolean',
        'issued_date' => 'date',
        'effective_from' => 'date',
        'effective_until' => 'date',
        'holder_date_of_birth' => 'date',
    ];

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function newerVersions(): HasMany
    {
        return $this->hasMany(self::class, 'previous_version_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class);
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(DocumentAccessLog::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isOnChain(): bool
    {
        return $this->blockchain_status === 'confirmed';
    }
}
