<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'program_id' => $this->program_id,
            'name'       => $this->name,
            'code'       => $this->code,
            'credits'    => $this->credits,
            'semester'   => $this->semester,
            'is_active'  => $this->is_active,
            'program'    => $this->whenLoaded('program', fn() => [
                'id'   => $this->program->id,
                'name' => $this->program->name,
            ]),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
