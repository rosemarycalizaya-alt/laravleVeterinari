<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExpedienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAtender() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['alergias' => ['nullable', 'string', 'max:10000'], 'enfermedades_cronicas' => ['nullable', 'string', 'max:10000'], 'antecedentes_medicos' => ['nullable', 'string', 'max:10000'], 'observaciones_generales' => ['nullable', 'string', 'max:10000']];
    }
}
