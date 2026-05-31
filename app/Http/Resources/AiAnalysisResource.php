<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiAnalysisResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'model_used'       => $this->model_used,
            'analysis_type'    => $this->analysis_type,
            'result'           => $this->result,
            'summary'          => $this->summary,
            'findings'         => $this->findings,
            'anomalies'        => $this->anomalies,
            'recommendations'  => $this->recommendations,
            'confidence_score' => $this->confidence_score,
            'tokens_used'      => $this->tokens_used,
            'processing_time_ms' => $this->processing_time_ms,
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
