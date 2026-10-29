<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MedicamentoConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAtender() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['medicamento_id' => ['integer', 'required', Rule::exists('medicamentos', 'id')->where('activo', true)], 'tipo_registro' => ['required', Rule::in(['administrado', 'recetado'])], 'dosis_cantidad' => ['nullable', 'numeric', 'gt:0', 'max:9999999.999'], 'dosis_unidad' => ['nullable', 'string', 'max:40'], 'via_administracion' => ['nullable', 'string', 'max:80'], 'frecuencia' => ['nullable', 'string', 'max:100'], 'duracion_dias' => ['nullable', 'integer', 'between:1,65535'], 'fecha_hora_administracion' => ['nullable', 'required_if:tipo_registro,administrado', 'prohibited_if:tipo_registro,recetado', 'date', 'before_or_equal:now'], 'aplicado_por_id' => ['integer', 'nullable', 'required_if:tipo_registro,administrado', 'prohibited_if:tipo_registro,recetado', Rule::exists('users', 'id')->whereIn('rol', ['administrador', 'veterinario'])->where('activo', true)], 'indicaciones' => ['nullable', 'string', 'max:10000']];
    }
}
