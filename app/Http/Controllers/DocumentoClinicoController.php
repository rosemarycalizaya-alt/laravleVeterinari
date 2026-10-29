<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentoRequest;
use App\Models\Atencion;
use App\Models\Consulta;
use App\Models\DocumentoClinico;
use App\Models\ExpedienteClinico;
use App\Services\ConsultaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentoClinicoController extends Controller
{
    public function store(DocumentoRequest $request, ExpedienteClinico $expediente, ConsultaService $servicio): RedirectResponse
    {
        $ruta = $request->file('archivo')->store('documentos', 'local');
        if ($ruta === false) {
            throw new \RuntimeException('No se pudo guardar el documento.');
        }
        try {
            DB::transaction(function () use ($request, $expediente, $servicio, $ruta): void {
                $datos = $request->safe()->except('archivo');
                if (! empty($datos['consulta_id'])) {
                    $consulta = Consulta::query()->findOrFail($request->integer('consulta_id'));
                    if ($consulta->expediente_id !== $expediente->id) {
                        throw ValidationException::withMessages(['consulta_id' => 'La consulta no pertenece a este expediente.']);
                    }
                    $consulta->setRelation('atencion', Atencion::query()->lockForUpdate()->findOrFail($consulta->atencion_id));
                    $servicio->verificarEdicion($consulta, $request->user());
                }
                $expediente->documentos()->create([...$datos, 'ruta_privada' => $ruta, 'nombre_archivo' => $request->file('archivo')->getClientOriginalName(), 'subido_por_id' => $request->user()->id]);
            }, 5);
        } catch (\Throwable $error) {
            Storage::disk('local')->delete($ruta);
            throw $error;
        }

        return back()->with('status', 'Documento registrado.');
    }

    public function show(Request $request, DocumentoClinico $documento): StreamedResponse
    {
        abort_unless(Storage::disk('local')->exists($documento->ruta_privada), 404);
        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];

        return $request->boolean('descargar') ? Storage::disk('local')->download($documento->ruta_privada, $documento->nombre_archivo, $headers) : Storage::disk('local')->response($documento->ruta_privada, $documento->nombre_archivo, $headers);
    }
}
