<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationResult extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_submission_id',
        'passed',
        'score',
        'checks_performed',
        'checks_passed',
        'checks_failed',
        'inconsistencies',
        'recommendations',
        'processing_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'passed'            => 'boolean',
            'score'             => 'decimal:2',
            'checks_performed'  => 'array',
            'checks_passed'     => 'array',
            'checks_failed'     => 'array',
            'inconsistencies'   => 'array',
            'processing_time_ms' => 'integer',
            'created_at'        => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }
}
