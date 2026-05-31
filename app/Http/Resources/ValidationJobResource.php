<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ValidationJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'attempts'     => $this->attempts,
            'started_at'   => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_at'   => $this->created_at?->toISOString(),
        ];
    }
}
