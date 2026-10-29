<?php

namespace App\Http\Controllers;

use App\Http\Requests\CitaRequest;
use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use App\Services\CitaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CitaController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d'], 'veterinario' => ['nullable', 'integer'], 'buscar' => ['nullable', 'string', 'max:150']]);
        $fecha = $request->input('fecha', today()->toDateString());
        $buscar = $request->input('buscar');
        $citas = Cita::with(['mascota.propietario', 'veterinario', 'atencion'])->whereDate('inicio', $fecha)->when($request->filled('veterinario'), fn ($q) => $q->where('veterinario_id', $request->integer('veterinario')))->when($buscar, fn ($q) => $q->whereHas('mascota', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")->orWhereHas('propietario', fn ($q) => $q->where('nombre_completo', 'like', "%{$buscar}%"))))->orderBy('inicio')->paginate(20)->withQueryString();
        $veterinarios = User::whereIn('rol', ['administrador', 'veterinario'])->orderBy('name')->get(['id', 'name']);

        return view('huellita.citas', compact('citas', 'fecha', 'veterinarios'));
    }

    private function formulario(Cita $cita): View
    {
        return view('huellita.cita-form', ['cita' => $cita, 'mascotas' => Mascota::with('propietario')->where('estado', 'activa')->orderBy('nombre')->get(), 'veterinarios' => User::whereIn('rol', ['administrador', 'veterinario'])->where('activo', true)->get(['id', 'name'])]);
    }

    public function create(): View
    {
        return $this->formulario(new Cita);
    }

    public function edit(Cita $cita): View
    {
        return $this->formulario($cita);
    }

    public function store(CitaRequest $request, CitaService $servicio): RedirectResponse
    {
        $cita = $servicio->guardar([...$request->datos(), 'registrada_por_id' => $request->user()->id]);

        return redirect()->route('citas.index', ['fecha' => $cita->inicio->toDateString()])->with('status', 'Cita programada.');
    }

    public function update(CitaRequest $request, Cita $cita, CitaService $servicio): RedirectResponse
    {
        $cita = $servicio->guardar($request->datos(), $cita);

        return redirect()->route('citas.index', ['fecha' => $cita->inicio->toDateString()])->with('status', 'Cita actualizada.');
    }
}
