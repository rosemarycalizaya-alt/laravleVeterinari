<?php

namespace App\Http\Controllers;

use App\Http\Requests\VacunacionRequest;
use App\Models\Atencion;
use App\Models\Consulta;
use App\Models\ExpedienteClinico;
use App\Services\ConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VacunacionController extends Controller
{
    public function store(VacunacionRequest $request, ExpedienteClinico $expediente, ConsultaService $servicio): RedirectResponse
    {

        DB::transaction(function () use ($request, $expediente, $servicio): void {
            $datos = $request->safe()->except('archivo');
            if (! empty($datos['consulta_id'])) {
                $consulta = Consulta::query()->findOrFail($request->integer('consulta_id'));
                if ($consulta->expediente_id !== $expediente->id) {
                    throw ValidationException::withMessages(['consulta_id' => 'La consulta no pertenece a este expediente.']);
                }
                $consulta->setRelation('atencion', Atencion::query()->lockForUpdate()->findOrFail($consulta->atencion_id));
                $servicio->verificarEdicion($consulta, $request->user());
            }
            $expediente->vacunaciones()->create([...$datos, 'aplicada_por_id' => $request->user()->id]);
        }, 5);

        return back()->with('status', 'Vacuna registrado.');
    }
}
