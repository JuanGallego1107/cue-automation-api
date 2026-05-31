<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidationResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'passed'             => $this->passed,
            'score'              => $this->score,
            'checks_performed'   => $this->checks_performed,
            'checks_passed'      => $this->checks_passed,
            'checks_failed'      => $this->checks_failed,
            'inconsistencies'    => $this->inconsistencies,
            'recommendations'    => $this->recommendations,
            'processing_time_ms' => $this->processing_time_ms,
            'created_at'         => $this->created_at?->toISOString(),
        ];
    }
}
