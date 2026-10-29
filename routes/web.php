<?php

use App\Http\Controllers\AtencionController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentoClinicoController;
use App\Http\Controllers\ExpedienteClinicoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MascotaController;
use App\Http\Controllers\MedicamentoConsultaController;
use App\Http\Controllers\MedicamentoController;
use App\Http\Controllers\PropietarioController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\VacunacionController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('mascotas/{mascota}/foto', [MascotaController::class, 'foto'])->name('mascotas.foto');
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');
    Route::get('atenciones', [AtencionController::class, 'index'])->name('atenciones.index');

    Route::middleware('rol:administrador,secretaria')->group(function (): void {
        Route::resource('propietarios', PropietarioController::class)->except('show');
        Route::resource('mascotas', MascotaController::class)->except(['index', 'show'])->parameters(['mascotas' => 'mascota']);
        Route::resource('citas', CitaController::class)->only(['create', 'store', 'edit', 'update']);
        Route::get('atenciones/create', [AtencionController::class, 'create'])->name('atenciones.create');
        Route::post('atenciones', [AtencionController::class, 'store'])->name('atenciones.store');
        Route::post('atenciones/{atencion}/cancelar', [AtencionController::class, 'cancelar'])->name('atenciones.cancelar');
    });
    Route::middleware('rol:administrador,veterinario')->group(function (): void {
        Route::resource('medicamentos', MedicamentoController::class)->except('show');
        Route::get('expedientes', [ExpedienteClinicoController::class, 'index'])->name('expedientes.index');
        Route::get('expedientes/{expediente}', [ExpedienteClinicoController::class, 'show'])->name('expedientes.show');
        Route::get('consultas/{consulta}', [ConsultaController::class, 'show'])->name('consultas.show');
        Route::get('documentos/{documento}', [DocumentoClinicoController::class, 'show'])->name('documentos.show');
    });
    Route::middleware('rol:administrador,veterinario')->group(function (): void {
        Route::put('expedientes/{expediente}', [ExpedienteClinicoController::class, 'update'])->name('expedientes.update');
        Route::get('atenciones/{atencion}/consulta', [ConsultaController::class, 'create'])->name('consultas.create');
        Route::post('consultas', [ConsultaController::class, 'store'])->name('consultas.store');
        Route::get('consultas/{consulta}/edit', [ConsultaController::class, 'edit'])->name('consultas.edit');
        Route::put('consultas/{consulta}', [ConsultaController::class, 'update'])->name('consultas.update');
        Route::post('consultas/{consulta}/medicamentos', [MedicamentoConsultaController::class, 'store'])->name('medicamentos-consulta.store');
        Route::post('expedientes/{expediente}/vacunas', [VacunacionController::class, 'store'])->name('vacunaciones.store');
        Route::post('expedientes/{expediente}/documentos', [DocumentoClinicoController::class, 'store'])->name('documentos.store');
        Route::post('atenciones/{atencion}/comenzar', [AtencionController::class, 'comenzar'])->name('atenciones.comenzar');
        Route::post('atenciones/{atencion}/finalizar', [AtencionController::class, 'finalizar'])->name('atenciones.finalizar');
    });
    Route::middleware('rol:administrador')->group(function (): void {
        Route::resource('usuarios', UsuarioController::class)->except('show');
    });
    Route::resource('mascotas', MascotaController::class)->only(['index', 'show']);
});
require __DIR__.'/settings.php';
