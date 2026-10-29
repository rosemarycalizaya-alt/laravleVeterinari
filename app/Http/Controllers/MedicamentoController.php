<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicamentoRequest;
use App\Models\Medicamento;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MedicamentoController extends Controller
{
    /** @return array<string, array{0: string, 1: string, 2?: bool, 3?: array<int|string, string>}> */
    private function campos(): array
    {
        return ['nombre' => ['Nombre', 'text', true], 'principio_activo' => ['Principio activo', 'text'], 'presentacion' => ['Presentación', 'text'], 'concentracion' => ['Concentración', 'text'], 'activo' => ['Activo', 'select', true, ['0' => 'No', '1' => 'Sí']]];
    }

    public function index(Request $request): View
    {
        $buscar = mb_substr((string) $request->input('buscar', ''), 0, 150);
        $registros = Medicamento::query()->when($buscar, fn ($q) => $q->where(function ($q) use ($buscar): void {
            $q->where('nombre', 'like', '%'.$buscar.'%');
            $q->orWhere('principio_activo', 'like', '%'.$buscar.'%');
        }))->orderByDesc('id')->paginate(15)->withQueryString();

        return view('huellita.crud-index', ['registros' => $registros, 'titulo' => 'Medicamentos', 'recurso' => 'medicamentos', 'etiqueta' => 'nombre', 'columnas' => ['nombre', 'principio_activo'], 'campos' => $this->campos()]);
    }

    public function create(): View
    {
        return view('huellita.form', ['titulo' => 'Nuevo registro · Medicamentos', 'recurso' => 'medicamentos', 'registro' => new Medicamento, 'campos' => $this->campos()]);
    }

    public function store(MedicamentoRequest $request): RedirectResponse
    {
        Medicamento::create($request->validated());

        return redirect()->route('medicamentos.index')->with('status', 'Registro guardado.');
    }

    public function edit(Medicamento $medicamento): View
    {
        return view('huellita.form', ['titulo' => 'Editar · Medicamentos', 'recurso' => 'medicamentos', 'registro' => $medicamento, 'campos' => $this->campos()]);
    }

    public function update(MedicamentoRequest $request, Medicamento $medicamento): RedirectResponse
    {
        $medicamento->update($request->safe()->except('confirmar_coincidencia'));

        return redirect()->route('medicamentos.index')->with('status', 'Cambios guardados.');
    }

    public function destroy(Medicamento $medicamento): RedirectResponse
    {
        if ($medicamento->registros()->exists()) {
            $medicamento->update(['activo' => false]);
        } else {
            $medicamento->delete();
        }

        return redirect()->route('medicamentos.index')->with('status', 'Operación completada.');
    }
}
