<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'wallet_address',
        'organization_name', 'is_approved', 'approved_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
    ];

    public function issuedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'issuer_id');
    }

    public function attestations(): HasMany
    {
        return $this->hasMany(Attestation::class, 'lawyer_id');
    }

    public function shareLinks(): HasMany
    {
        return $this->hasMany(ShareLink::class, 'created_by');
    }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function isIssuer(): bool { return $this->role === 'issuer'; }
    public function isLawyer(): bool { return $this->role === 'lawyer'; }
    public function isHolder(): bool { return $this->role === 'holder'; }
    public function isApprovedIssuer(): bool { return $this->isIssuer() && $this->is_approved; }
    public function isApprovedLawyer(): bool { return $this->isLawyer() && $this->is_approved; }
}
