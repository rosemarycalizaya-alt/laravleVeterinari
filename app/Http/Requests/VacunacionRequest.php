<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VacunacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAtender() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['consulta_id' => ['integer', 'nullable', 'exists:consultas,id'], 'vacuna' => ['required', 'string', 'max:160'], 'fecha_aplicacion' => ['required', 'date', 'before_or_equal:today'], 'proxima_dosis' => ['nullable', 'date', 'after_or_equal:fecha_aplicacion'], 'numero_lote' => ['nullable', 'string', 'max:100'], 'observaciones' => ['nullable', 'string', 'max:10000']];
    }
}
