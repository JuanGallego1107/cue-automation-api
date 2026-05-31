<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OcrResult extends Model
{
    protected $fillable = [
        'document_submission_id',
        'engine',
        'extracted_text',
        'structured_data',
        'confidence_score',
        'pages_processed',
        'processing_time_ms',
        'raw_response',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'structured_data'  => 'array',
            'raw_response'     => 'array',
            'confidence_score' => 'decimal:4',
            'pages_processed'  => 'integer',
            'processing_time_ms' => 'integer',
            'completed_at'     => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }
}
