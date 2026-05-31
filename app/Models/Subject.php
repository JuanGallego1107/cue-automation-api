<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = [
        'program_id',
        'name',
        'code',
        'credits',
        'semester',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'credits'   => 'integer',
            'semester'  => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to filter only active subjects.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter subjects by program.
     */
    public function scopeByProgram($query, int $programId)
    {
        return $query->where('program_id', $programId);
    }
}
