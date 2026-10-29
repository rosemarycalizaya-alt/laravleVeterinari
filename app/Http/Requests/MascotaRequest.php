<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MascotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && (in_array($this->user()->rol, ['administrador', 'secretaria'], true));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['propietario_id' => ['required', 'integer', 'exists:propietarios,id'], 'nombre' => ['required', 'string', 'max:120'], 'especie' => ['required', 'string', 'max:70'], 'raza' => ['nullable', 'string', 'max:120'], 'sexo' => ['required', Rule::in(['macho', 'hembra', 'desconocido'])], 'fecha_nacimiento' => ['nullable', 'date', 'before_or_equal:today'], 'color' => ['nullable', 'string', 'max:100'], 'esterilizado' => ['nullable', 'boolean'], 'estado' => ['required', Rule::in(['activa', 'inactiva', 'fallecida'])], 'observaciones' => ['nullable', 'string', 'max:10000'], 'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:4096']];
    }
}
