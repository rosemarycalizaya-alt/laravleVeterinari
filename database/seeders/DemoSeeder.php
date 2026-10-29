<?php

namespace Database\Seeders;

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Mascota;
use App\Models\Medicamento;
use App\Models\Propietario;
use App\Models\User;
use App\Services\ExpedienteService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('Los datos ficticios solo se permiten en local o testing.');

            return;
        }
        DB::transaction(function (): void {
            $usuarios = [];
            foreach (['administrador', 'secretaria', 'veterinario'] as $rol) {
                $usuario = User::firstOrCreate(['email' => match ($rol) {
                    'administrador' => 'sofia.ramirez@example.test', 'secretaria' => 'camila.vargas@example.test', 'veterinario' => 'andrea.salazar@example.test'
                }], ['name' => match ($rol) {
                    'administrador' => 'Sofía Ramírez', 'secretaria' => 'Camila Vargas', 'veterinario' => 'Dra. Andrea Salazar'
                }, 'rol' => $rol, 'activo' => true, 'password' => Str::password(24)]);
                $usuarios[$rol] = $usuario;
            }
            $medicamento = Medicamento::firstOrCreate(['nombre' => 'Vetacare — muestra sin uso clínico'], ['principio_activo' => 'No contiene fármaco real', 'activo' => true]);
            foreach (['María Fernanda Rojas', 'Carlos Andrés Mendoza', 'Lucía Valeria Flores'] as $i => $nombre) {
                Propietario::firstOrCreate(['documento_identidad' => 'FIC-'.($i + 1)], ['nombre_completo' => $nombre, 'telefono' => '000-000-000', 'observaciones' => 'Persona completamente ficticia para demostración.']);
            }
            foreach (['Max', 'Luna', 'Milo', 'Nala', 'Coco'] as $i => $nombre) {
                $propietario = Propietario::where('documento_identidad', 'FIC-'.($i % 3 + 1))->firstOrFail();
                $mascota = Mascota::firstOrCreate(['microchip' => 'FIC-CHIP-'.($i + 1)], ['nombre' => $nombre, 'propietario_id' => $propietario->id, 'especie' => $i % 2 ? 'Gato' : 'Perro', 'sexo' => 'desconocido', 'estado' => 'activa', 'observaciones' => 'Paciente ficticio de demostración.']);
                $expediente = app(ExpedienteService::class)->abrir($mascota);
                $atencion = Atencion::firstOrCreate(['mascota_id' => $mascota->id, 'motivo_ingreso' => ['Revisión general', 'Control de peso', 'Revisión de piel y pelaje', 'Seguimiento clínico', 'Primera revisión'][$i]], ['registrada_por_id' => $usuarios['secretaria']->id, 'veterinario_id' => $i === 4 ? null : $usuarios['veterinario']->id, 'hora_llegada' => today()->setHour(8)->addMinutes($i * 15), 'hora_inicio' => $i < 4 ? today()->setHour(9)->addMinutes($i * 15) : null, 'hora_fin' => $i < 3 ? today()->setHour(9)->addMinutes($i * 15 + 10) : null, 'estado' => $i < 3 ? 'finalizada' : ($i === 3 ? 'en_atencion' : 'en_espera')]);
                if ($i < 4) {
                    $consulta = Consulta::firstOrCreate(['atencion_id' => $atencion->id], ['expediente_id' => $expediente->id, 'veterinario_id' => $usuarios['veterinario']->id, 'fecha_consulta' => $atencion->hora_inicio, 'motivo_consulta' => 'Consulta de control y seguimiento', 'diagnostico' => 'Sin diagnóstico registrado.', 'tratamiento' => 'Sin tratamiento farmacológico indicado en este ejemplo.']);
                    $consulta->medicamentos()->firstOrCreate(['medicamento_id' => $medicamento->id, 'tipo_registro' => 'recetado'], ['indicaciones' => 'Registro de referencia, sin pauta farmacológica válida.']);
                    $consulta->medicamentos()->firstOrCreate(['medicamento_id' => $medicamento->id, 'tipo_registro' => 'administrado'], ['fecha_hora_administracion' => $atencion->hora_inicio, 'aplicado_por_id' => $usuarios['veterinario']->id, 'indicaciones' => 'Aplicación simulada para presentación; sin uso clínico.']);
                    $expediente->vacunaciones()->firstOrCreate(['vacuna' => 'Vacuna de referencia', 'consulta_id' => $consulta->id], ['fecha_aplicacion' => today(), 'aplicada_por_id' => $usuarios['veterinario']->id, 'observaciones' => 'Registro ficticio sin validez médica.']);
                }
                Cita::firstOrCreate(['mascota_id' => $mascota->id, 'motivo' => 'Control programado '.($i + 1)], ['veterinario_id' => $usuarios['veterinario']->id, 'registrada_por_id' => $usuarios['secretaria']->id, 'inicio' => today()->addDay()->setHour(9)->addHours($i), 'fin' => today()->addDay()->setHour(9)->addHours($i)->addMinutes(30), 'canal' => 'presencial', 'estado' => 'programada']);
            }
        });
        $this->command?->info('Datos ficticios preparados sin duplicados. Las cuentas de ejemplo tienen contraseñas aleatorias no publicadas. Crea primero tu administrador con huellita:admin; luego gestiona estas cuentas desde el panel.');
    }
}
