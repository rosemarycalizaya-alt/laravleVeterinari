<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MedicamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->activo && (in_array($this->user()->rol, ['administrador', 'veterinario'], true));
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return ['nombre' => ['required', 'string', 'max:160'], 'principio_activo' => ['nullable', 'string', 'max:160'], 'presentacion' => ['nullable', 'string', 'max:100'], 'concentracion' => ['nullable', 'string', 'max:100'], 'activo' => ['required', 'boolean']];
    }
}
