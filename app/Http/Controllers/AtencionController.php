<?php

namespace App\Http\Controllers;

use App\Http\Requests\AtencionRequest;
use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Mascota;
use App\Services\AtencionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AtencionController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['fecha' => ['nullable', 'date_format:Y-m-d']]);
        $fecha = $request->input('fecha', today()->toDateString());
        $atenciones = Atencion::with(['mascota.propietario', 'mascota.expediente', 'veterinario', 'consulta'])->where(fn ($q) => $q->whereIn('estado', ['en_espera', 'en_atencion'])->orWhereDate('hora_llegada', $fecha))->orderBy('hora_llegada')->get()->groupBy('estado');

        return view('huellita.atenciones', compact('atenciones', 'fecha'));
    }

    public function create(Request $request): View
    {
        return view('huellita.atencion-form', ['mascotas' => Mascota::with('propietario')->where('estado', 'activa')->orderBy('nombre')->get(), 'citas' => Cita::with('mascota')->where('estado', 'programada')->whereDoesntHave('atencion')->orderBy('inicio')->get(), 'seleccionada' => $request->integer('cita_id')]);
    }

    public function store(AtencionRequest $request, AtencionService $servicio): RedirectResponse
    {
        $servicio->llegada($request->datos(), $request->user());

        return redirect()->route('atenciones.index')->with('status', 'Llegada registrada. El paciente está en espera.');
    }

    public function comenzar(Request $request, Atencion $atencion, AtencionService $servicio): RedirectResponse
    {
        $servicio->comenzar($atencion, $request->user());

        return redirect()->route('expedientes.show', $atencion->mascota->expediente)->with('status', 'Atención iniciada. Revisa el expediente y registra la consulta.');
    }

    public function finalizar(Request $request, Atencion $atencion, AtencionService $servicio): RedirectResponse
    {
        $servicio->finalizar($atencion, $request->user());

        return redirect()->route('atenciones.index')->with('status', 'Atención finalizada.');
    }

    public function cancelar(Atencion $atencion, AtencionService $servicio): RedirectResponse
    {
        $servicio->cancelar($atencion);

        return back()->with('status', 'Llegada cancelada.');
    }
}
