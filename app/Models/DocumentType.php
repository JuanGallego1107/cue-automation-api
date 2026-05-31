<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'allowed_extensions',
        'max_size_mb',
        'naming_pattern',
        'requires_signature',
        'validation_rules',
    ];

    protected function casts(): array
    {
        return [
            'allowed_extensions' => 'array',
            'requires_signature' => 'boolean',
            'validation_rules'   => 'array',
            'max_size_mb'        => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Check if the given extension is allowed for this document type.
     */
    public function allowsExtension(string $extension): bool
    {
        return in_array(strtolower($extension), array_map('strtolower', $this->allowed_extensions ?? []));
    }

    /**
     * Get the max allowed file size in bytes.
     */
    public function getMaxSizeBytesAttribute(): int
    {
        return $this->max_size_mb * 1024 * 1024;
    }
}
