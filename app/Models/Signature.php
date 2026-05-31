<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_submission_id',
        'user_id',
        'hash',
        'signature_type',
        'signature_hash',
        'ip_address',
        'signed_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'signed_at'  => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
