<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $id = $this->route('document_type')?->id;

        return [
            'name'                => 'required|string|max:255',
            'slug'                => "required|string|unique:document_types,slug,{$id}",
            'allowed_extensions'  => 'required|array|min:1',
            'allowed_extensions.*' => 'string',
            'max_size_mb'         => 'required|integer|min:1|max:50',
            'naming_pattern'      => 'nullable|string|max:255',
            'requires_signature'  => 'boolean',
            'validation_rules'    => 'nullable|array',
        ];
    }
}
