<?php

namespace App\Http\Controllers;

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Mascota;
use App\Models\Propietario;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $indicadores = ['Mascotas' => Mascota::count(), 'Propietarios' => Propietario::count(), 'Citas de hoy' => Cita::whereDate('inicio', today())->where('estado', 'programada')->count(), 'En espera' => Atencion::where('estado', 'en_espera')->count(), 'Atenciones de hoy' => Atencion::whereDate('hora_llegada', today())->count()];
        $consultas = $request->user()->rol === 'secretaria' ? collect() : Consulta::with(['expediente.mascota', 'veterinario'])->latest('fecha_consulta')->limit(6)->get();
        $citas = Cita::with(['mascota.propietario', 'veterinario'])->whereDate('inicio', today())->orderBy('inicio')->limit(10)->get();

        return view('huellita.dashboard', compact('indicadores', 'consultas', 'citas'));
    }
}
