<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'name'                => $this->name,
            'slug'                => $this->slug,
            'allowed_extensions'  => $this->allowed_extensions,
            'max_size_mb'         => $this->max_size_mb,
            'naming_pattern'      => $this->naming_pattern,
            'requires_signature'  => $this->requires_signature,
            'validation_rules'    => $this->validation_rules,
            'created_at'          => $this->created_at?->toISOString(),
            'updated_at'          => $this->updated_at?->toISOString(),
        ];
    }
}
