<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicamentoConsultaRequest;
use App\Models\Atencion;
use App\Models\Consulta;
use App\Services\ConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MedicamentoConsultaController extends Controller
{
    public function store(MedicamentoConsultaRequest $request, Consulta $consulta, ConsultaService $servicio): RedirectResponse
    {
        DB::transaction(function () use ($request, $consulta, $servicio): void {
            $atencion = Atencion::query()->lockForUpdate()->findOrFail($consulta->atencion_id);
            $consulta->setRelation('atencion', $atencion);
            $servicio->verificarEdicion($consulta, $request->user());
            $datos = $request->validated();
            if ($datos['tipo_registro'] === 'recetado') {
                $datos['fecha_hora_administracion'] = null;
                $datos['aplicado_por_id'] = null;
            }
            $consulta->medicamentos()->create($datos);
        }, 5);

        return back()->with('status', 'Medicamento registrado.');
    }
}
