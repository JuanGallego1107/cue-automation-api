<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OcrResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'engine'           => $this->engine,
            'extracted_text'   => $this->extracted_text,
            'structured_data'  => $this->structured_data,
            'confidence_score' => $this->confidence_score,
            'pages_processed'  => $this->pages_processed,
            'processing_time_ms' => $this->processing_time_ms,
            'completed_at'     => $this->completed_at?->toISOString(),
        ];
    }
}
