<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values'  => 'array',
            'new_values'  => 'array',
            'entity_id'   => 'integer',
            'created_at'  => 'datetime',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ─── Static helpers ───────────────────────────────────────────────────────

    /**
     * Quickly log an audit event.
     *
     * @param string      $action      e.g. 'submission.created'
     * @param string      $entityType  e.g. 'DocumentSubmission'
     * @param int|null    $entityId
     * @param array|null  $oldValues
     * @param array|null  $newValues
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): self {
        /** @var \Illuminate\Http\Request $request */
        $request = app('request');

        return self::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
        ]);
    }
}
