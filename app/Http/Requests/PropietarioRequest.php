<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PropietarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && (in_array($this->user()->rol, ['administrador', 'secretaria'], true));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['nombre_completo' => ['required', 'string', 'max:160'], 'documento_identidad' => ['nullable', 'string', 'max:30', Rule::unique('propietarios')->ignore($this->route('propietario'))], 'telefono' => ['nullable', 'string', 'max:30'], 'correo' => ['nullable', 'email', 'max:255'], 'direccion' => ['nullable', 'string', 'max:255'], 'observaciones' => ['nullable', 'string', 'max:10000'], 'confirmar_coincidencia' => ['sometimes', 'accepted']];
    }
}
