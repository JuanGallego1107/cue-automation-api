<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DriveStorage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'document_submission_id',
        'drive_file_id',
        'drive_folder_id',
        'drive_url',
        'drive_filename',
        'folder_path',
        'file_size_bytes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'integer',
            'created_at'      => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function submission(): BelongsTo
    {
        return $this->belongsTo(DocumentSubmission::class, 'document_submission_id');
    }
}
