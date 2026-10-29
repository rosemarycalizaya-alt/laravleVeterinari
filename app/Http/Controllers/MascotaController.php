<?php

namespace App\Http\Controllers;

use App\Http\Requests\MascotaRequest;
use App\Models\Mascota;
use App\Models\Propietario;
use App\Services\ExpedienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MascotaController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = mb_substr((string) $request->input('buscar', ''), 0, 150);
        $mascotas = Mascota::with(['propietario', 'expediente'])->when($buscar, fn ($q) => $q->where(function ($q) use ($buscar): void {
            $q->where('nombre', 'like', "%{$buscar}%")->orWhereHas('propietario', fn ($q) => $q->where('nombre_completo', 'like', "%{$buscar}%")->orWhere('documento_identidad', 'like', "%{$buscar}%"));
        }))->latest('id')->paginate(12)->withQueryString();

        return view('huellita.mascotas', compact('mascotas'));
    }

    private function formulario(Mascota $mascota): View
    {
        return view('huellita.mascota-form', ['mascota' => $mascota, 'propietarios' => Propietario::orderBy('nombre_completo')->get(['id', 'nombre_completo', 'documento_identidad'])]);
    }

    public function create(): View
    {
        return $this->formulario(new Mascota);
    }

    public function edit(Mascota $mascota): View
    {
        return $this->formulario($mascota);
    }

    public function store(MascotaRequest $request, ExpedienteService $expedientes): RedirectResponse
    {
        $datos = $request->safe()->except('foto');
        $ruta = $request->file('foto')?->store('mascotas', 'local');
        try {
            $mascota = DB::transaction(function () use ($datos, $ruta, $expedientes): Mascota {
                $mascota = Mascota::create([...$datos, 'foto_ruta' => $ruta]);
                $expedientes->abrir($mascota);

                return $mascota;
            });
        } catch (\Throwable $error) {
            if ($ruta) {
                Storage::disk('local')->delete($ruta);
            } throw $error;
        }

        return redirect()->route('mascotas.show', $mascota)->with('status', 'Paciente y expediente registrados.');
    }

    public function update(MascotaRequest $request, Mascota $mascota): RedirectResponse
    {
        $datos = $request->safe()->except('foto');
        $ruta = $request->file('foto')?->store('mascotas', 'local');
        $anterior = $mascota->foto_ruta;
        try {
            if ($ruta) {
                $datos['foto_ruta'] = $ruta;
            } $mascota->update($datos);
        } catch (\Throwable $error) {
            if ($ruta) {
                Storage::disk('local')->delete($ruta);
            } throw $error;
        }
        if ($ruta && $anterior) {
            Storage::disk('local')->delete($anterior);
        }

        return redirect()->route('mascotas.show', $mascota)->with('status', 'Paciente actualizado.');
    }

    public function show(Mascota $mascota): View
    {
        $mascota->load(['propietario', 'expediente']);

        return view('huellita.mascota-show', compact('mascota'));
    }

    public function foto(Mascota $mascota): StreamedResponse
    {
        abort_unless($mascota->foto_ruta && Storage::disk('local')->exists($mascota->foto_ruta), 404);

        return Storage::disk('local')->response($mascota->foto_ruta, null, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function destroy(Mascota $mascota): RedirectResponse
    {
        $mascota->update(['estado' => 'inactiva']);

        return redirect()->route('mascotas.index')->with('status', 'Paciente archivado. Se conserva su historial clínico.');
    }
}
