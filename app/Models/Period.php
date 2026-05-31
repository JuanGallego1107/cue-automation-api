<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Period extends Model
{
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_active'  => 'boolean',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submissions(): HasMany
    {
        return $this->hasMany(DocumentSubmission::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to filter only active periods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
