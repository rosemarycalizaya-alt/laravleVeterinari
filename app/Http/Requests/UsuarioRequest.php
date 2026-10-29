<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && ($this->user()->rol === 'administrador');
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->route('usuario'))], 'password' => [$this->isMethod('post') ? 'required' : 'nullable', 'string', 'min:12', 'confirmed'], 'rol' => ['required', Rule::in(['administrador', 'secretaria', 'veterinario'])], 'activo' => ['required', 'boolean']];
    }
}
