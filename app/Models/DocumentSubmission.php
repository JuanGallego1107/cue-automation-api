<?php

namespace App\Models;

use App\Enums\SubmissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class DocumentSubmission extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'user_id',
        'document_type_id',
        'subject_id',
        'period_id',
        'original_filename',
        'stored_filename',
        'file_path',
        'file_size_bytes',
        'mime_type',
        'file_hash',
        'status',
        'reviewer_id',
        'reviewer_notes',
        'rejection_reason',
        'reviewed_at',
        'retry_count',
        'last_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'status'        => SubmissionStatus::class,
            'reviewed_at'   => 'datetime',
            'last_retry_at' => 'datetime',
            'retry_count'   => 'integer',
            'file_size_bytes' => 'integer',
        ];
    }

    /**
     * The "booting" method of the model.
     * Automatically assigns a UUID on creation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function ocrResult(): HasOne
    {
        return $this->hasOne(OcrResult::class);
    }

    public function aiAnalysis(): HasOne
    {
        return $this->hasOne(AiAnalysis::class)->latest();
    }

    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    public function validationResult(): HasOne
    {
        return $this->hasOne(ValidationResult::class)->latest();
    }

    public function validationJob(): HasOne
    {
        return $this->hasOne(ValidationJob::class)->latest();
    }

    public function driveStorage(): HasOne
    {
        return $this->hasOne(DriveStorage::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Scope to filter submissions in an active status.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', SubmissionStatus::activeValues());
    }

    /**
     * Scope to filter submissions by coordinator user ID.
     */
    public function scopeByCoordinator($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    // ─── Static Helpers ───────────────────────────────────────────────────────

    /**
     * Check whether the given user already has an active review in progress.
     */
    public static function hasActiveReview(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->whereIn('status', SubmissionStatus::activeValues())
            ->exists();
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    /**
     * Return a human-readable file size (e.g. "2.5 MB").
     */
    public function getFileSizeHumanAttribute(): string
    {
        $bytes = $this->file_size_bytes;
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / (1024 * 1024), 1) . ' MB';
    }
}
