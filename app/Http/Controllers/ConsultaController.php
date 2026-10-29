<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsultaRequest;
use App\Models\Atencion;
use App\Models\Consulta;
use App\Models\Medicamento;
use App\Models\User;
use App\Services\ConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultaController extends Controller
{
    public function create(Atencion $atencion): View
    {
        abort_unless($atencion->veterinario_id === auth()->id() && $atencion->estado === 'en_atencion', 403);
        $atencion->load('mascota.expediente');

        return view('huellita.consulta-form', ['consulta' => new Consulta, 'atencion' => $atencion]);
    }

    public function store(ConsultaRequest $request, ConsultaService $servicio): RedirectResponse
    {
        $consulta = $servicio->guardar($request->datos(), $request->user());

        return redirect()->route('consultas.show', $consulta)->with('status', 'Consulta guardada. Ahora puedes registrar medicamentos, vacunas y documentos.');
    }

    public function show(Consulta $consulta): View
    {
        $consulta->load(['expediente.mascota.propietario', 'atencion', 'veterinario', 'medicamentos.medicamento', 'medicamentos.aplicadoPor', 'vacunaciones', 'documentos']);
        $medicamentos = Medicamento::where('activo', true)->orderBy('nombre')->get();
        $veterinarios = User::whereIn('rol', ['administrador', 'veterinario'])->where('activo', true)->get(['id', 'name']);

        return view('huellita.consulta', compact('consulta', 'medicamentos', 'veterinarios'));
    }

    public function edit(Consulta $consulta, ConsultaService $servicio): View
    {
        $consulta->load('atencion.mascota.expediente');
        $servicio->verificarEdicion($consulta, auth()->user());

        return view('huellita.consulta-form', ['consulta' => $consulta, 'atencion' => $consulta->atencion]);
    }

    public function update(ConsultaRequest $request, Consulta $consulta, ConsultaService $servicio): RedirectResponse
    {
        $servicio->guardar($request->datos(), $request->user(), $consulta);

        return redirect()->route('consultas.show', $consulta)->with('status', 'Consulta actualizada.');
    }
}
