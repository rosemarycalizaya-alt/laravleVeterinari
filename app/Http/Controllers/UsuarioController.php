<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsuarioRequest;
use App\Models\Atencion;
use App\Models\Cita;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UsuarioController extends Controller
{
    /** @return array<string, array{0: string, 1: string, 2?: bool, 3?: array<int|string, string>}> */
    private function campos(): array
    {
        return ['name' => ['Nombre completo', 'text', true], 'email' => ['Correo electrónico', 'email', true], 'password' => ['Contraseña (mínimo 12 caracteres)', 'password'], 'password_confirmation' => ['Confirmar contraseña', 'password'], 'rol' => ['Rol', 'select', true, ['administrador' => 'Administrador', 'secretaria' => 'Secretaria', 'veterinario' => 'Veterinario']], 'activo' => ['Cuenta activa', 'select', true, ['0' => 'No', '1' => 'Sí']]];
    }

    public function index(Request $request): View
    {
        $buscar = mb_substr((string) $request->input('buscar', ''), 0, 150);
        $registros = User::query()->when($buscar, fn ($q) => $q->where(function ($q) use ($buscar): void {
            $q->where('name', 'like', '%'.$buscar.'%');
            $q->orWhere('email', 'like', '%'.$buscar.'%');
        }))->orderByDesc('id')->paginate(15)->withQueryString();

        return view('huellita.crud-index', ['registros' => $registros, 'titulo' => 'Usuarios', 'recurso' => 'usuarios', 'etiqueta' => 'name', 'columnas' => ['name', 'email'], 'campos' => $this->campos()]);
    }

    public function create(): View
    {
        return view('huellita.form', ['titulo' => 'Nuevo registro · Usuarios', 'recurso' => 'usuarios', 'registro' => new User, 'campos' => $this->campos()]);
    }

    public function store(UsuarioRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('usuarios.index')->with('status', 'Registro guardado.');
    }

    public function edit(User $usuario): View
    {
        return view('huellita.form', ['titulo' => 'Editar · Usuarios', 'recurso' => 'usuarios', 'registro' => $usuario, 'campos' => $this->campos()]);
    }

    public function update(UsuarioRequest $request, User $usuario): RedirectResponse
    {
        $datos = $request->validated();
        if (empty($datos['password'])) {
            unset($datos['password']);
        }
        DB::transaction(function () use ($usuario, $datos): void {
            $administradores = User::where('rol', 'administrador')->where('activo', true)->orderBy('id')->lockForUpdate()->get();
            $usuario = User::query()->lockForUpdate()->findOrFail($usuario->id);
            if ($usuario->rol === 'administrador' && $usuario->activo && ($datos['rol'] !== 'administrador' || ! $datos['activo']) && $administradores->count() <= 1) {
                throw ValidationException::withMessages(['rol' => 'Debe quedar al menos un administrador activo.']);
            }
            if ($usuario->id === auth()->id() && (! $datos['activo'] || $datos['rol'] !== 'administrador')) {
                throw ValidationException::withMessages(['rol' => 'No puedes desactivar ni degradar tu propia cuenta.']);
            }
            if ($usuario->puedeAtender() && (! in_array($datos['rol'], ['administrador', 'veterinario'], true) || ! $datos['activo']) && (Atencion::where('veterinario_id', $usuario->id)->whereIn('estado', ['en_espera', 'en_atencion'])->exists() || Cita::where('veterinario_id', $usuario->id)->where('estado', 'programada')->exists())) {
                throw ValidationException::withMessages(['rol' => 'Reasigna o finaliza sus citas y atenciones pendientes antes de cambiar la cuenta.']);
            }
            $usuario->update($datos);
        });

        return redirect()->route('usuarios.index')->with('status', 'Cambios guardados.');
    }

    public function destroy(User $usuario): never
    {
        throw ValidationException::withMessages(['usuario' => 'Conserva las cuentas vinculadas al historial. Puedes desactivarlas desde Editar.']);
    }
}
