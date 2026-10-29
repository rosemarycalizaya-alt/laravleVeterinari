<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CitaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && (in_array($this->user()->rol, ['administrador', 'secretaria'], true));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['mascota_id' => ['integer', 'required', Rule::exists('mascotas', 'id')->where('estado', 'activa')], 'veterinario_id' => ['integer', 'required', Rule::exists('users', 'id')->whereIn('rol', ['administrador', 'veterinario'])->where('activo', true)], 'inicio' => ['required', 'date'], 'fin' => ['required', 'date', 'after:inicio'], 'motivo' => ['nullable', 'string', 'max:255'], 'canal' => ['required', Rule::in(['whatsapp', 'telefono', 'presencial', 'otro'])], 'estado' => ['required', Rule::in(['programada', 'cancelada', 'no_asistio'])], 'observaciones' => ['nullable', 'string', 'max:10000']];
    }

    /** @return array{mascota_id: int|string, veterinario_id: int|string, registrada_por_id?: int, inicio: string, fin: string, motivo?: string|null, canal: string, estado: string, observaciones?: string|null} */
    public function datos(): array
    {
        $this->validated();

        return [
            'mascota_id' => $this->integer('mascota_id'),
            'veterinario_id' => $this->integer('veterinario_id'),
            'inicio' => $this->input('inicio'),
            'fin' => $this->input('fin'),
            'motivo' => $this->input('motivo'),
            'canal' => $this->input('canal'),
            'estado' => $this->input('estado'),
            'observaciones' => $this->input('observaciones'),
        ];
    }
}
