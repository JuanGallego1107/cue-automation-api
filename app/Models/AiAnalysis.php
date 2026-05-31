<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAnalysis extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_submission_id',
        'model_used',
        'analysis_type',
        'result',
        'summary',
        'findings',
        'anomalies',
        'recommendations',
        'confidence_score',
        'tokens_used',
        'processing_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'findings'         => 'array',
            'anomalies'        => 'array',
            'confidence_score' => 'decimal:4',
            'tokens_used'      => 'integer',
            'processing_time_ms' => 'integer',
            'created_at'       => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to filter only validation-type analyses.
     */
    public function scopeValidations($query)
    {
        return $query->where('analysis_type', 'validation');
    }
}
