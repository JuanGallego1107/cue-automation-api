<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case PENDING          = 'pending';
    case PROCESSING       = 'processing';
    case PENDING_APPROVAL = 'pending_approval';
    case APPROVED         = 'approved';
    case ISSUES_FOUND     = 'issues_found';
    case FAILED           = 'failed';

    /**
     * Determine if this status is considered "active" (blocks new submissions).
     */
    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING]);
    }

    /**
     * Return an array of string values for all active statuses.
     *
     * @return string[]
     */
    public static function activeValues(): array
    {
        return array_map(
            fn($s) => $s->value,
            array_filter(self::cases(), fn($s) => $s->isActive())
        );
    }

    /**
     * Return all status values as strings.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(fn($s) => $s->value, self::cases());
    }
}
