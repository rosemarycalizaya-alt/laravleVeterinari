<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpedienteRequest;
use App\Models\Atencion;
use App\Models\ExpedienteClinico;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpedienteClinicoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = mb_substr((string) $request->input('buscar', ''), 0, 150);
        $expedientes = ExpedienteClinico::with('mascota.propietario')->when($buscar, fn ($q) => $q->where(fn ($q) => $q->where('codigo', 'like', "%{$buscar}%")->orWhereHas('mascota', fn ($q) => $q->where('nombre', 'like', "%{$buscar}%")->orWhereHas('propietario', fn ($q) => $q->where('nombre_completo', 'like', "%{$buscar}%")))))->latest('id')->paginate(15)->withQueryString();

        return view('huellita.expedientes', compact('expedientes'));
    }

    public function show(ExpedienteClinico $expediente): View
    {
        $expediente->load(['mascota.propietario', 'vacunaciones.aplicadaPor', 'documentos.subidoPor']);
        $consultas = $expediente->consultas()->with(['veterinario', 'medicamentos.medicamento', 'vacunaciones', 'documentos'])->latest('fecha_consulta')->latest('id')->paginate(10);
        $atenciones = Atencion::where('mascota_id', $expediente->mascota_id)->where('veterinario_id', auth()->id())->where('estado', 'en_atencion')->whereDoesntHave('consulta')->get();

        return view('huellita.expediente', compact('expediente', 'consultas', 'atenciones'));
    }

    public function update(ExpedienteRequest $request, ExpedienteClinico $expediente): RedirectResponse
    {
        $expediente->update($request->validated());

        return back()->with('status', 'Antecedentes actualizados.');
    }
}
