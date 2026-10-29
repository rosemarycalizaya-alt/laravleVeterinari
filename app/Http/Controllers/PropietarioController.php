<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropietarioRequest;
use App\Models\Propietario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PropietarioController extends Controller
{
    /** @return array<string, array{0: string, 1: string, 2?: bool, 3?: array<int|string, string>}> */
    private function campos(): array
    {
        return ['nombre_completo' => ['Nombre completo', 'text', true], 'documento_identidad' => ['Documento de identidad', 'text'], 'telefono' => ['Teléfono', 'text'], 'correo' => ['Correo', 'email'], 'direccion' => ['Dirección', 'text'], 'observaciones' => ['Observaciones', 'textarea']];
    }

    public function index(Request $request): View
    {
        $buscar = mb_substr((string) $request->input('buscar', ''), 0, 150);
        $registros = Propietario::query()->when($buscar, fn ($q) => $q->where(function ($q) use ($buscar): void {
            $q->where('nombre_completo', 'like', '%'.$buscar.'%');
            $q->orWhere('documento_identidad', 'like', '%'.$buscar.'%');
            $q->orWhere('telefono', 'like', '%'.$buscar.'%');
        }))->orderByDesc('id')->paginate(15)->withQueryString();

        return view('huellita.crud-index', ['registros' => $registros, 'titulo' => 'Propietarios', 'recurso' => 'propietarios', 'etiqueta' => 'nombre_completo', 'columnas' => ['nombre_completo', 'documento_identidad', 'telefono'], 'campos' => $this->campos()]);
    }

    public function create(): View
    {
        return view('huellita.form', ['titulo' => 'Nuevo registro · Propietarios', 'recurso' => 'propietarios', 'registro' => new Propietario, 'campos' => $this->campos()]);
    }

    public function store(PropietarioRequest $request): RedirectResponse
    {
        $datos = $request->safe()->except('confirmar_coincidencia');
        $coincidencias = Propietario::where(function ($q) use ($datos): void {
            $q->where('nombre_completo', $datos['nombre_completo']);
            if (! empty($datos['telefono'])) {
                $q->orWhere('telefono', $datos['telefono']);
            }
        })->limit(5)->get();
        if ($coincidencias->isNotEmpty() && ! $request->boolean('confirmar_coincidencia')) {
            return back()->withInput()->with('coincidencias', $coincidencias->map(fn ($p) => $p->nombre_completo.' · '.($p->telefono ?? 'sin teléfono').' · '.($p->documento_identidad ?? 'sin documento'))->all())->withErrors(['confirmar_coincidencia' => 'Revisa las coincidencias y confirma que es otra persona.']);
        }
        Propietario::create($datos);

        return redirect()->route('propietarios.index')->with('status', 'Registro guardado.');
    }

    public function edit(Propietario $propietario): View
    {
        return view('huellita.form', ['titulo' => 'Editar · Propietarios', 'recurso' => 'propietarios', 'registro' => $propietario, 'campos' => $this->campos()]);
    }

    public function update(PropietarioRequest $request, Propietario $propietario): RedirectResponse
    {
        $propietario->update($request->safe()->except('confirmar_coincidencia'));

        return redirect()->route('propietarios.index')->with('status', 'Cambios guardados.');
    }

    public function destroy(Propietario $propietario): RedirectResponse
    {
        if ($propietario->mascotas()->exists()) {
            throw ValidationException::withMessages(['propietario' => 'No se puede eliminar un propietario con mascotas.']);
        } $propietario->delete();

        return redirect()->route('propietarios.index')->with('status', 'Operación completada.');
    }
}
