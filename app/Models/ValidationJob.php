<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationJob extends Model
{
    protected $fillable = [
        'document_submission_id',
        'job_id',
        'status',
        'attempts',
        'payload',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload'      => 'array',
            'attempts'     => 'integer',
            'started_at'   => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to filter jobs currently running.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope to filter failed jobs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
