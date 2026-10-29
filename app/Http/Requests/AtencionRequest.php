<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtencionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && (in_array($this->user()->rol, ['administrador', 'secretaria'], true));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['mascota_id' => ['integer', 'required', 'exists:mascotas,id'], 'cita_id' => ['integer', 'nullable', 'exists:citas,id'], 'motivo_ingreso' => ['nullable', 'string', 'max:255']];
    }

    /** @return array{mascota_id: int|string, cita_id?: int|string|null, motivo_ingreso?: string|null, veterinario_id?: int} */
    public function datos(): array
    {
        $this->validated();

        return [
            'mascota_id' => $this->integer('mascota_id'),
            'cita_id' => $this->filled('cita_id') ? $this->integer('cita_id') : null,
            'motivo_ingreso' => $this->input('motivo_ingreso'),
        ];
    }
}
