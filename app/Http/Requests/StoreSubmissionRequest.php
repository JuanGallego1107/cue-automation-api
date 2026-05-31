<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'file'             => 'required|file|mimes:pdf,docx,xlsx,xls|max:10240',
            'document_type_id' => 'required|exists:document_types,id',
            'subject_id'       => 'nullable|exists:subjects,id',
            'period_id'        => 'nullable|exists:periods,id',
        ];
    }

    /**
     * Get the custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required'             => 'Debes adjuntar un archivo.',
            'file.file'                 => 'El campo debe ser un archivo válido.',
            'file.mimes'                => 'El archivo debe ser de tipo: pdf, docx, xlsx o xls.',
            'file.max'                  => 'El archivo no puede superar los 10 MB.',
            'document_type_id.required' => 'El tipo de documento es obligatorio.',
            'document_type_id.exists'   => 'El tipo de documento seleccionado no existe.',
            'subject_id.exists'         => 'La asignatura seleccionada no existe.',
            'period_id.exists'          => 'El período seleccionado no existe.',
        ];
    }
}
