<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        $user = Auth::user();
        DB::transaction(function () use ($user, $logout): void {
            $administradores = User::where('rol', 'administrador')->where('activo', true)->orderBy('id')->lockForUpdate()->get();
            if ($user->rol === 'administrador' && $user->activo && $administradores->count() <= 1) {
                throw ValidationException::withMessages(['password' => 'No se puede eliminar al último administrador activo.']);
            }
            foreach (['citas' => ['veterinario_id', 'registrada_por_id'], 'atenciones' => ['veterinario_id', 'registrada_por_id'], 'consultas' => ['veterinario_id'], 'medicamentos_consulta' => ['aplicado_por_id'], 'vacunaciones' => ['aplicada_por_id'], 'documentos_clinicos' => ['subido_por_id']] as $tabla => $campos) {
                foreach ($campos as $campo) {
                    if (DB::table($tabla)->where($campo, $user->id)->exists()) {
                        throw ValidationException::withMessages(['password' => 'Esta cuenta está vinculada a registros veterinarios. Solicita su desactivación a administración.']);
                    }
                }
            }
            $logout();
            $user->delete();
        });

        $this->redirect('/', navigate: true);
    }
}
