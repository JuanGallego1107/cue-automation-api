<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubjectRequest extends FormRequest
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
        return [
            'program_id' => 'required|exists:programs,id',
            'name'       => 'required|string|max:255',
            'code'       => 'required|string|max:50',
            'credits'    => 'required|integer|min:1|max:20',
            'semester'   => 'required|integer|min:1|max:12',
        ];
    }
}
