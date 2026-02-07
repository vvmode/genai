<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Verification extends Model
{
    protected $fillable = [
        'document_id', 'verification_hash', 'result', 'verified_by_ip',
        'verified_by_user_id', 'method', 'blockchain_verified', 'details',
    ];

    protected $casts = [
        'blockchain_verified' => 'boolean',
        'details' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
