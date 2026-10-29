<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAtender() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['consulta_id' => ['integer', 'nullable', 'exists:consultas,id'], 'archivo' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'extensions:pdf,jpg,jpeg,png,webp', 'max:10240'], 'tipo_documento' => ['nullable', 'string', 'max:100'], 'descripcion' => ['nullable', 'string', 'max:255'], 'fecha_documento' => ['nullable', 'date', 'before_or_equal:today']];
    }
}
