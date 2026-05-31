<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodRequest extends FormRequest
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
            'name'       => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after:start_date',
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',
        ];
    }
}
