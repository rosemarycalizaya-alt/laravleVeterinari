<?php

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\DocumentoClinico;
use App\Models\ExpedienteClinico;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\User;
use App\Services\ExpedienteService;
use Database\Seeders\DemoSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

function pacienteHuellita(): Mascota
{
    $mascota = Mascota::factory()->create();
    app(ExpedienteService::class)->abrir($mascota);

    return $mascota->load('expediente');
}

function consultaHuellita(User $veterinario): Consulta
{
    $mascota = pacienteHuellita();
    $atencion = Atencion::factory()->create(['mascota_id' => $mascota->id, 'veterinario_id' => $veterinario->id, 'estado' => 'en_atencion', 'hora_inicio' => now()]);

    return Consulta::factory()->create(['expediente_id' => $mascota->expediente->id, 'atencion_id' => $atencion->id, 'veterinario_id' => $veterinario->id]);
}

test('el portal y el acceso se muestran sin registro público', function () {
    $this->get(route('home'))->assertOk()->assertSee('Huellita');
    $this->get(route('login'))->assertOk()->assertSee('Iniciar sesión');
    $this->get('/register')->assertNotFound();
});

test('una cuenta inactiva no puede iniciar sesión', function () {
    $usuario = User::factory()->create(['activo' => false]);

    $this->post(route('login.store'), ['email' => $usuario->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('una sesión existente pierde acceso al desactivar la cuenta', function () {
    $usuario = User::factory()->create(['activo' => false]);

    $this->actingAs($usuario)->get(route('dashboard'))->assertRedirect(route('login'));

    $this->assertGuest();
});

test('los invitados no pueden leer expedientes', function () {
    $mascota = pacienteHuellita();

    $this->get(route('expedientes.show', $mascota->expediente))->assertRedirect(route('login'));
});

test('secretaria no puede consultar ni modificar registros clínicos', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $mascota = pacienteHuellita();

    $this->actingAs($secretaria)->get(route('expedientes.show', $mascota->expediente))->assertForbidden();
    $this->put(route('expedientes.update', $mascota->expediente), ['alergias' => 'alterado'])->assertForbidden();
    $this->post(route('consultas.store'), [])->assertForbidden();
    $this->assertDatabaseMissing('expedientes_clinicos', ['alergias' => 'alterado']);
});

test('administrador veterinario gestiona una visita clínica completa', function () {
    Storage::fake('local');
    $administrador = User::factory()->create(['rol' => 'administrador']);
    $mascota = pacienteHuellita();
    $medicamento = Medicamento::factory()->create();

    $this->actingAs($administrador)->get(route('citas.create'))->assertOk()->assertSee($administrador->name);
    $this->get(route('expedientes.show', $mascota->expediente))->assertOk()->assertSee('Actualizar antecedentes');
    $this->put(route('expedientes.update', $mascota->expediente), ['alergias' => 'Antecedente de prueba'])->assertSessionHasNoErrors();
    $this->post(route('citas.store'), ['mascota_id' => $mascota->id, 'veterinario_id' => $administrador->id, 'inicio' => '2027-01-11T10:00', 'fin' => '2027-01-11T11:00', 'canal' => 'presencial', 'estado' => 'programada'])->assertSessionHasNoErrors();
    $cita = Cita::firstOrFail();
    $this->post(route('atenciones.store'), ['mascota_id' => $mascota->id, 'cita_id' => $cita->id])->assertSessionHasNoErrors();
    $atencion = Atencion::firstOrFail();
    $this->get(route('atenciones.index'))->assertOk()->assertSee('Comenzar atención');
    $this->post(route('atenciones.comenzar', $atencion))->assertSessionHasNoErrors();
    $this->post(route('consultas.store'), ['atencion_id' => $atencion->id, 'expediente_id' => $mascota->expediente->id, 'motivo_consulta' => 'Visita de prueba', 'diagnostico' => 'Registro ficticio'])->assertSessionHasNoErrors();
    $consulta = Consulta::firstOrFail();
    $this->get(route('consultas.show', $consulta))->assertOk()->assertSee('Registrar medicamento')->assertViewHas('veterinarios', fn ($profesionales) => $profesionales->contains('id', $administrador->id));
    $this->post(route('medicamentos-consulta.store', $consulta), ['medicamento_id' => $medicamento->id, 'tipo_registro' => 'administrado', 'fecha_hora_administracion' => now()->subMinute()->format('Y-m-d H:i'), 'aplicado_por_id' => $administrador->id])->assertSessionHasNoErrors();
    $this->post(route('vacunaciones.store', $mascota->expediente), ['consulta_id' => $consulta->id, 'vacuna' => 'Vacuna ficticia', 'fecha_aplicacion' => today()->toDateString()])->assertSessionHasNoErrors();
    $this->post(route('documentos.store', $mascota->expediente), ['consulta_id' => $consulta->id, 'archivo' => UploadedFile::fake()->createWithContent('prueba.pdf', "%PDF-1.4\n%%EOF")])->assertSessionHasNoErrors();
    $this->post(route('atenciones.finalizar', $atencion))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('expedientes_clinicos', ['id' => $mascota->expediente->id, 'alergias' => 'Antecedente de prueba']);
    $this->assertDatabaseHas('consultas', ['id' => $consulta->id, 'veterinario_id' => $administrador->id, 'diagnostico' => 'Registro ficticio']);
    $this->assertDatabaseHas('medicamentos_consulta', ['consulta_id' => $consulta->id, 'aplicado_por_id' => $administrador->id]);
    $this->assertDatabaseHas('vacunaciones', ['consulta_id' => $consulta->id, 'aplicada_por_id' => $administrador->id]);
    $this->assertDatabaseHas('documentos_clinicos', ['consulta_id' => $consulta->id, 'subido_por_id' => $administrador->id]);
    Storage::disk('local')->assertExists(DocumentoClinico::firstOrFail()->ruta_privada);
    expect($atencion->fresh()->estado)->toBe('finalizada');
    expect($cita->fresh()->estado)->toBe('atendida');
});

test('veterinario no puede administrar personal', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);

    $this->actingAs($veterinario)->get(route('usuarios.index'))->assertForbidden();
    $this->post(route('usuarios.store'), [])->assertForbidden();
});

test('secretaria registra propietario sin aceptar campos de privilegios', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);

    $this->actingAs($secretaria)->post(route('propietarios.store'), ['nombre_completo' => 'Persona ficticia', 'telefono' => '000-111', 'rol' => 'administrador'])->assertRedirect(route('propietarios.index'));

    $this->assertDatabaseHas('propietarios', ['nombre_completo' => 'Persona ficticia', 'telefono' => '000-111']);
    expect($secretaria->fresh()->rol)->toBe('secretaria');
});

test('se advierten coincidencias sin asumir que dos nombres son la misma persona', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    Propietario::factory()->create(['nombre_completo' => 'Persona Demo']);

    $this->actingAs($secretaria)->post(route('propietarios.store'), ['nombre_completo' => 'Persona Demo'])->assertSessionHasErrors('confirmar_coincidencia')->assertSessionHas('coincidencias');
    $this->assertDatabaseCount('propietarios', 1);
    $this->post(route('propietarios.store'), ['nombre_completo' => 'Persona Demo', 'confirmar_coincidencia' => '1'])->assertRedirect(route('propietarios.index'));
    $this->assertDatabaseCount('propietarios', 2);
});

test('se valida el documento único del propietario', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    Propietario::factory()->create(['documento_identidad' => 'DEMO-1']);

    $this->actingAs($secretaria)->post(route('propietarios.store'), ['nombre_completo' => 'Otra persona', 'documento_identidad' => 'DEMO-1'])->assertSessionHasErrors('documento_identidad');

    $this->assertDatabaseCount('propietarios', 1);
});

test('registrar una mascota abre un solo expediente y admite nacimiento desconocido', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $propietario = Propietario::factory()->create();

    $this->actingAs($secretaria)->post(route('mascotas.store'), ['propietario_id' => $propietario->id, 'nombre' => 'Max Demo', 'especie' => 'Perro', 'sexo' => 'desconocido', 'estado' => 'activa'])->assertSessionHasNoErrors()->assertRedirect();

    $mascota = Mascota::firstOrFail();
    $this->assertDatabaseHas('mascotas', ['id' => $mascota->id, 'fecha_nacimiento' => null]);
    $this->assertDatabaseHas('expedientes_clinicos', ['mascota_id' => $mascota->id]);
    expect($mascota->expediente->codigo)->toMatch('/^HUE-[0-9]{6,}$/');
    app(ExpedienteService::class)->abrir($mascota);
    $this->assertDatabaseCount('expedientes_clinicos', 1);
});

test('la base rechaza un segundo expediente del mismo paciente', function () {
    $mascota = pacienteHuellita();

    expect(fn () => ExpedienteClinico::factory()->create(['mascota_id' => $mascota->id]))->toThrow(QueryException::class);
});

test('archivar un paciente conserva el expediente y no permite eliminar su propietario', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $mascota = pacienteHuellita();

    $this->actingAs($secretaria)->delete(route('mascotas.destroy', $mascota))->assertRedirect(route('mascotas.index'));
    $this->delete(route('propietarios.destroy', $mascota->propietario_id))->assertSessionHasErrors('propietario');

    $this->assertModelExists($mascota->expediente);
    expect($mascota->fresh()->estado)->toBe('inactiva');
    $this->assertDatabaseCount('propietarios', 1);
});

test('se programa una cita y se rechaza superposición del mismo profesional', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $mascota = pacienteHuellita();
    $datos = ['mascota_id' => $mascota->id, 'veterinario_id' => $veterinario->id, 'inicio' => '2027-01-10T10:00', 'fin' => '2027-01-10T11:00', 'canal' => 'telefono', 'estado' => 'programada'];

    $this->actingAs($secretaria)->post(route('citas.store'), $datos)->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('citas.store'), [...$datos, 'inicio' => '2027-01-10T10:30', 'fin' => '2027-01-10T11:30'])->assertSessionHasErrors(['inicio' => 'El veterinario ya tiene una cita en ese horario.']);

    $this->assertDatabaseCount('citas', 1);
});

test('citas contiguas y veterinarios diferentes pueden compartir horarios', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $otro = User::factory()->create(['rol' => 'veterinario']);
    $mascota = pacienteHuellita();
    $datos = ['mascota_id' => $mascota->id, 'veterinario_id' => $veterinario->id, 'inicio' => '2027-01-10T10:00', 'fin' => '2027-01-10T11:00', 'canal' => 'telefono', 'estado' => 'programada'];
    Cita::factory()->create($datos);

    $this->actingAs($secretaria)->post(route('citas.store'), [...$datos, 'inicio' => '2027-01-10T11:00', 'fin' => '2027-01-10T12:00'])->assertSessionHasNoErrors();
    $this->post(route('citas.store'), [...$datos, 'veterinario_id' => $otro->id])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('citas', 3);
});

test('una cita cancelada no bloquea el horario', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $cita = Cita::factory()->create(['estado' => 'cancelada']);

    $this->actingAs($secretaria)->post(route('citas.store'), ['mascota_id' => $cita->mascota_id, 'veterinario_id' => $cita->veterinario_id, 'inicio' => $cita->inicio->format('Y-m-d H:i'), 'fin' => $cita->fin->format('Y-m-d H:i'), 'canal' => 'presencial', 'estado' => 'programada'])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('citas', 2);
});

test('la secretaria registra una atención espontánea', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $mascota = pacienteHuellita();

    $this->actingAs($secretaria)->post(route('atenciones.store'), ['mascota_id' => $mascota->id, 'motivo_ingreso' => 'Revisión ficticia'])->assertRedirect(route('atenciones.index'));

    $this->assertDatabaseHas('atenciones', ['mascota_id' => $mascota->id, 'cita_id' => null, 'estado' => 'en_espera', 'registrada_por_id' => $secretaria->id]);
});

test('la llegada con cita conserva paciente y profesional y no se duplica', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $cita = Cita::factory()->create();

    $this->actingAs($secretaria)->post(route('atenciones.store'), ['mascota_id' => $cita->mascota_id, 'cita_id' => $cita->id])->assertSessionHasNoErrors();
    $this->post(route('atenciones.store'), ['mascota_id' => $cita->mascota_id, 'cita_id' => $cita->id])->assertSessionHasErrors('cita_id');

    $this->assertDatabaseHas('atenciones', ['cita_id' => $cita->id, 'veterinario_id' => $cita->veterinario_id]);
    $this->assertDatabaseCount('atenciones', 1);
});

test('no se acepta una mascota diferente a la de la cita', function () {
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $cita = Cita::factory()->create();
    $otra = Mascota::factory()->create();

    $this->actingAs($secretaria)->post(route('atenciones.store'), ['mascota_id' => $otra->id, 'cita_id' => $cita->id])->assertSessionHasErrors('mascota_id');

    $this->assertDatabaseCount('atenciones', 0);
});

test('un veterinario no comienza dos atenciones a la vez', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $primera = Atencion::factory()->create();
    $segunda = Atencion::factory()->create();
    app(ExpedienteService::class)->abrir($primera->mascota);

    $this->actingAs($veterinario)->post(route('atenciones.comenzar', $primera))->assertSessionHasNoErrors();
    $this->post(route('atenciones.comenzar', $segunda))->assertSessionHasErrors('estado');

    expect($primera->fresh()->estado)->toBe('en_atencion');
    expect($segunda->fresh()->estado)->toBe('en_espera');
});

test('el veterinario registra una consulta en el expediente del paciente y finaliza la cita', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $mascota = pacienteHuellita();
    $cita = Cita::factory()->create(['mascota_id' => $mascota->id, 'veterinario_id' => $veterinario->id]);
    $atencion = Atencion::factory()->create(['mascota_id' => $mascota->id, 'cita_id' => $cita->id, 'veterinario_id' => $veterinario->id, 'estado' => 'en_atencion']);

    $this->actingAs($veterinario)->post(route('consultas.store'), ['expediente_id' => $mascota->expediente->id, 'atencion_id' => $atencion->id, 'motivo_consulta' => 'Visita ficticia', 'diagnostico' => 'Texto de demostración'])->assertSessionHasNoErrors()->assertRedirect();
    $this->post(route('atenciones.finalizar', $atencion))->assertSessionHasNoErrors()->assertRedirect(route('atenciones.index'));

    $this->assertDatabaseHas('consultas', ['atencion_id' => $atencion->id, 'expediente_id' => $mascota->expediente->id, 'diagnostico' => 'Texto de demostración']);
    expect($atencion->fresh()->estado)->toBe('finalizada');
    expect($cita->fresh()->estado)->toBe('atendida');
});

test('la consulta rechaza un expediente de otra mascota sin guardar datos parciales', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $otra = pacienteHuellita();
    $atencion = Atencion::factory()->create(['mascota_id' => $consulta->expediente->mascota_id, 'veterinario_id' => $veterinario->id, 'estado' => 'en_atencion']);

    $this->actingAs($veterinario)->post(route('consultas.store'), ['atencion_id' => $atencion->id, 'expediente_id' => $otra->expediente->id, 'motivo_consulta' => 'Ejemplo'])->assertSessionHasErrors('expediente_id');

    $this->assertDatabaseMissing('consultas', ['atencion_id' => $atencion->id]);
});

test('no se puede registrar dos veces la consulta de una atención', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);

    $this->actingAs($veterinario)->post(route('consultas.store'), ['atencion_id' => $consulta->atencion_id, 'expediente_id' => $consulta->expediente_id, 'motivo_consulta' => 'Duplicación'])->assertSessionHasErrors('atencion_id');

    $this->assertDatabaseCount('consultas', 1);
});

test('un medicamento administrado exige fecha y profesional', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $medicamento = Medicamento::factory()->create();

    $this->actingAs($veterinario)->post(route('medicamentos-consulta.store', $consulta), ['medicamento_id' => $medicamento->id, 'tipo_registro' => 'administrado'])->assertSessionHasErrors(['fecha_hora_administracion', 'aplicado_por_id']);

    $this->assertDatabaseCount('medicamentos_consulta', 0);
});

test('se registra un medicamento administrado con su aplicador y fecha', function () {
    $this->freezeTime();
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $medicamento = Medicamento::factory()->create();

    $this->actingAs($veterinario)->post(route('medicamentos-consulta.store', $consulta), ['medicamento_id' => $medicamento->id, 'tipo_registro' => 'administrado', 'fecha_hora_administracion' => now()->format('Y-m-d H:i'), 'aplicado_por_id' => $veterinario->id, 'dosis_cantidad' => '1.000', 'dosis_unidad' => 'unidad ficticia'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('medicamentos_consulta', ['consulta_id' => $consulta->id, 'tipo_registro' => 'administrado', 'aplicado_por_id' => $veterinario->id]);
});

test('las recetas se guardan sin fecha ni aplicador', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $medicamento = Medicamento::factory()->create();

    $this->actingAs($veterinario)->post(route('medicamentos-consulta.store', $consulta), ['medicamento_id' => $medicamento->id, 'tipo_registro' => 'recetado', 'frecuencia' => 'Texto ficticio'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('medicamentos_consulta', ['consulta_id' => $consulta->id, 'tipo_registro' => 'recetado', 'fecha_hora_administracion' => null, 'aplicado_por_id' => null]);
});

test('una receta no puede presentarse como administración', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $medicamento = Medicamento::factory()->create();

    $this->actingAs($veterinario)->post(route('medicamentos-consulta.store', $consulta), ['medicamento_id' => $medicamento->id, 'tipo_registro' => 'recetado', 'fecha_hora_administracion' => now()->format('Y-m-d H:i'), 'aplicado_por_id' => $veterinario->id])->assertSessionHasErrors(['fecha_hora_administracion', 'aplicado_por_id']);

    $this->assertDatabaseCount('medicamentos_consulta', 0);
});

test('un profesional no altera la consulta de otro veterinario', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $otro = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($otro);

    $this->actingAs($veterinario)->put(route('consultas.update', $consulta), ['motivo_consulta' => 'Alteración'])->assertForbidden();

    expect($consulta->fresh()->motivo_consulta)->toBe('Visita ficticia de prueba');
});

test('una consulta finalizada conserva su contenido', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $consulta->atencion->update(['estado' => 'finalizada']);

    $this->actingAs($veterinario)->put(route('consultas.update', $consulta), ['motivo_consulta' => 'Alteración'])->assertSessionHasErrors('atencion_id');

    expect($consulta->fresh()->motivo_consulta)->toBe('Visita ficticia de prueba');
});

test('una vacuna aplicada pertenece al expediente y a la consulta correctos', function () {
    $this->freezeTime();
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);

    $this->actingAs($veterinario)->post(route('vacunaciones.store', $consulta->expediente), ['consulta_id' => $consulta->id, 'vacuna' => 'Vacuna ficticia', 'fecha_aplicacion' => today()->toDateString(), 'numero_lote' => 'DEMO'])->assertSessionHasNoErrors();

    $this->assertDatabaseHas('vacunaciones', ['expediente_id' => $consulta->expediente_id, 'consulta_id' => $consulta->id, 'aplicada_por_id' => $veterinario->id, 'numero_lote' => 'DEMO']);
});

test('una vacuna no se vincula a una consulta de otro expediente', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $otra = pacienteHuellita();

    $this->actingAs($veterinario)->post(route('vacunaciones.store', $otra->expediente), ['consulta_id' => $consulta->id, 'vacuna' => 'Vacuna ficticia', 'fecha_aplicacion' => today()->toDateString()])->assertSessionHasErrors('consulta_id');

    $this->assertDatabaseCount('vacunaciones', 0);
});

test('los documentos se almacenan en privado y requieren permisos', function () {
    Storage::fake('local');
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $archivo = UploadedFile::fake()->createWithContent('analisis.pdf', "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF");

    $this->actingAs($veterinario)->post(route('documentos.store', $consulta->expediente), ['consulta_id' => $consulta->id, 'archivo' => $archivo])->assertSessionHasNoErrors();
    $documento = DocumentoClinico::firstOrFail();
    Storage::disk('local')->assertExists($documento->ruta_privada);
    $this->get(route('documentos.show', $documento))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->get(route('documentos.show', [$documento, 'descargar' => 1]))->assertDownload('analisis.pdf');
    $secretaria = User::factory()->create(['rol' => 'secretaria']);
    $this->actingAs($secretaria)->get(route('documentos.show', $documento))->assertForbidden();
    $this->get('/storage/'.$documento->ruta_privada)->assertNotFound();
});

test('un documento cruzado revierte el registro y limpia el archivo', function () {
    Storage::fake('local');
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $otra = pacienteHuellita();
    $archivo = UploadedFile::fake()->createWithContent('examen.pdf', "%PDF-1.4\n%%EOF");

    $this->actingAs($veterinario)->post(route('documentos.store', $otra->expediente), ['consulta_id' => $consulta->id, 'archivo' => $archivo])->assertSessionHasErrors('consulta_id');

    $this->assertDatabaseCount('documentos_clinicos', 0);
    expect(Storage::disk('local')->allFiles())->toBeEmpty();
});

test('los archivos ejecutables son rechazados', function () {
    Storage::fake('local');
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $mascota = pacienteHuellita();

    $this->actingAs($veterinario)->post(route('documentos.store', $mascota->expediente), ['archivo' => UploadedFile::fake()->create('malicioso.php', 1, 'application/x-php')])->assertSessionHasErrors('archivo');

    $this->assertDatabaseCount('documentos_clinicos', 0);
});

test('las páginas del personal renderizan sus formularios y datos', function () {
    $administrador = User::factory()->create(['rol' => 'administrador']);
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $medicamento = Medicamento::factory()->create();
    $cita = Cita::factory()->create(['mascota_id' => $consulta->expediente->mascota_id, 'veterinario_id' => $veterinario->id]);

    $this->actingAs($administrador);
    foreach (['dashboard', 'propietarios.index', 'propietarios.create', 'mascotas.index', 'mascotas.create', 'expedientes.index', 'medicamentos.index', 'medicamentos.create', 'citas.index', 'citas.create', 'atenciones.index', 'atenciones.create', 'usuarios.index', 'usuarios.create'] as $nombre) {
        $this->get(route($nombre))->assertOk();
    }
    $this->get(route('expedientes.show', $consulta->expediente))->assertOk();
    $this->get(route('consultas.show', $consulta))->assertOk();
    $this->get(route('mascotas.show', $consulta->expediente->mascota))->assertOk();
    $this->get(route('mascotas.edit', $consulta->expediente->mascota))->assertOk();
    $this->get(route('propietarios.edit', $consulta->expediente->mascota->propietario))->assertOk();
    $this->get(route('medicamentos.edit', $medicamento))->assertOk();
    $this->get(route('usuarios.edit', $veterinario))->assertOk();
    $this->get(route('citas.edit', $cita))->assertOk();
    $this->actingAs($veterinario)->get(route('consultas.edit', $consulta))->assertOk();
});

test('el administrador crea cuentas con contraseña cifrada', function () {
    $administrador = User::factory()->create(['rol' => 'administrador']);

    $this->actingAs($administrador)->post(route('usuarios.store'), ['name' => 'Personal ficticio', 'email' => 'personal@example.test', 'password' => 'clave-segura-de-prueba', 'password_confirmation' => 'clave-segura-de-prueba', 'rol' => 'secretaria', 'activo' => 1])->assertRedirect(route('usuarios.index'));

    $usuario = User::where('email', 'personal@example.test')->firstOrFail();
    expect(Hash::check('clave-segura-de-prueba', $usuario->password))->toBeTrue();
    expect($usuario->password)->not->toBe('clave-segura-de-prueba');
});

test('no se degrada al último administrador', function () {
    $administrador = User::factory()->create(['rol' => 'administrador']);

    $this->actingAs($administrador)->put(route('usuarios.update', $administrador), ['name' => $administrador->name, 'email' => $administrador->email, 'rol' => 'secretaria', 'activo' => 1])->assertSessionHasErrors('rol');

    expect($administrador->fresh()->rol)->toBe('administrador');
});

test('el historial escapa el contenido ingresado', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);
    $consulta->update(['diagnostico' => '<script>alert(1)</script>']);

    $this->actingAs($veterinario)->get(route('consultas.show', $consulta))->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
});

test('una segunda visita conserva paciente y expediente y agrega una consulta', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $primera = consultaHuellita($veterinario);
    $primera->atencion->update(['estado' => 'finalizada']);
    $segundaAtencion = Atencion::factory()->create(['mascota_id' => $primera->expediente->mascota_id, 'veterinario_id' => $veterinario->id, 'estado' => 'en_atencion']);

    $this->actingAs($veterinario)->post(route('consultas.store'), ['expediente_id' => $primera->expediente_id, 'atencion_id' => $segundaAtencion->id, 'motivo_consulta' => 'Segunda visita ficticia'])->assertSessionHasNoErrors();

    $this->assertDatabaseCount('propietarios', 1);
    $this->assertDatabaseCount('mascotas', 1);
    $this->assertDatabaseCount('expedientes_clinicos', 1);
    $this->assertDatabaseCount('consultas', 2);
});

test('los datos de demostración son opcionales e idempotentes', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    $this->assertDatabaseCount('propietarios', 3);
    $this->assertDatabaseCount('mascotas', 5);
    $this->assertDatabaseCount('expedientes_clinicos', 5);
    $this->assertDatabaseCount('consultas', 4);
    $this->assertDatabaseCount('users', 3);
});

test('el último administrador no se elimina desde el perfil', function () {
    $administrador = User::factory()->create(['rol' => 'administrador']);

    $this->actingAs($administrador);
    Livewire::test('settings.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors('password');

    $this->assertModelExists($administrador);
});

test('el personal con registros no se elimina desde el perfil', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    consultaHuellita($veterinario);

    $this->actingAs($veterinario);
    Livewire::test('settings.delete-user-form')->set('password', 'password')->call('deleteUser')->assertHasErrors('password');

    $this->assertModelExists($veterinario);
});

test('el expediente con consultas no se puede eliminar de la base', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $consulta = consultaHuellita($veterinario);

    expect(fn () => $consulta->expediente->delete())->toThrow(QueryException::class);
});
