<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->puedeAtender() ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['expediente_id' => ['integer', $this->isMethod('post') ? 'required' : 'sometimes', 'exists:expedientes_clinicos,id'], 'atencion_id' => ['integer', $this->isMethod('post') ? 'required' : 'sometimes', 'exists:atenciones,id'], 'motivo_consulta' => ['required', 'string', 'max:255'], 'sintomas' => ['nullable', 'string', 'max:20000'], 'examen_fisico' => ['nullable', 'string', 'max:20000'], 'peso_kg' => ['nullable', 'numeric', 'gt:0', 'max:9999.99'], 'temperatura_c' => ['nullable', 'numeric', 'between:0,60'], 'diagnostico' => ['nullable', 'string', 'max:20000'], 'tratamiento' => ['nullable', 'string', 'max:20000'], 'recomendaciones' => ['nullable', 'string', 'max:20000'], 'proximo_control' => ['nullable', 'date', 'after_or_equal:today']];
    }

    /** @return array{atencion_id: int|string, expediente_id: int|string, motivo_consulta: string, sintomas?: string|null, examen_fisico?: string|null, peso_kg?: numeric-string|null, temperatura_c?: numeric-string|null, diagnostico?: string|null, tratamiento?: string|null, recomendaciones?: string|null, proximo_control?: string|null} */
    public function datos(): array
    {
        $this->validated();

        return [
            'atencion_id' => $this->integer('atencion_id'),
            'expediente_id' => $this->integer('expediente_id'),
            'motivo_consulta' => $this->input('motivo_consulta'),
            'sintomas' => $this->input('sintomas'),
            'examen_fisico' => $this->input('examen_fisico'),
            'peso_kg' => $this->input('peso_kg'),
            'temperatura_c' => $this->input('temperatura_c'),
            'diagnostico' => $this->input('diagnostico'),
            'tratamiento' => $this->input('tratamiento'),
            'recomendaciones' => $this->input('recomendaciones'),
            'proximo_control' => $this->input('proximo_control'),
        ];
    }
}
