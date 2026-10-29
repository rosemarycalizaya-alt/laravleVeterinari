<?php

use App\Models\Atencion;
use App\Models\Cita;
use App\Models\Mascota;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql' || DB::connection()->getDatabaseName() !== 'huellita_testing') {
        $this->markTestSkipped('Requiere la base aislada huellita_testing y phpunit.mysql.xml.');
    }
    $this->artisan('migrate', ['--force' => true])->assertSuccessful();
});

/** @param list<string> $operaciones
 * @return list<string>
 */
function ejecutarEnParaleloHuellita(User $veterinario, array $operaciones): array
{
    $connection = config('database.connections.mysql');
    $environment = [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'mysql',
        'DB_DATABASE' => 'huellita_testing',
        'DB_HOST' => $connection['host'],
        'DB_PORT' => (string) $connection['port'],
        'DB_USERNAME' => $connection['username'],
        'DB_PASSWORD' => $connection['password'],
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];
    $processes = [];
    DB::beginTransaction();
    try {
        User::query()->whereKey($veterinario->id)->lockForUpdate()->firstOrFail();
        foreach ($operaciones as $operacion) {
            $code = 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); try { '.$operacion.' echo "creado"; } catch (Illuminate\\Validation\\ValidationException $error) { echo "conflicto"; }';
            $process = new Process([PHP_BINARY, '-r', $code], base_path(), $environment, timeout: 15);
            $process->start();
            $processes[] = $process;
        }
        $deadline = microtime(true) + 8;
        $waiting = 0;
        do {
            foreach ($processes as $process) {
                if (! $process->isRunning()) {
                    throw new RuntimeException('El proceso terminó antes del bloqueo: '.$process->getOutput().' '.$process->getErrorOutput());
                }
            }
            $waiting = (int) DB::selectOne("SELECT COUNT(*) AS total FROM information_schema.PROCESSLIST WHERE DB = 'huellita_testing' AND ID <> CONNECTION_ID() AND COMMAND <> 'Sleep'")->total;
            if ($waiting < count($processes)) {
                usleep(50000);
            }
        } while ($waiting < count($processes) && microtime(true) < $deadline);
        expect($waiting)->toBe(count($processes), implode(' | ', array_map(fn ($process) => $process->getOutput().' '.$process->getErrorOutput(), $processes)));
        DB::commit();
        $resultados = [];
        foreach ($processes as $process) {
            $process->wait();
            expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
            $resultados[] = $process->getOutput();
        }
        sort($resultados);

        return $resultados;
    } finally {
        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if ($process->isRunning()) {
                $process->stop();
            }
        }
    }
}

test('dos solicitudes simultáneas no crean citas superpuestas en MySQL', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $mascota = Mascota::factory()->create();
    $propietario = $mascota->propietario;
    try {
        $datos = ['mascota_id' => $mascota->id, 'veterinario_id' => $veterinario->id, 'registrada_por_id' => $veterinario->id, 'inicio' => '2027-05-10 10:00:00', 'fin' => '2027-05-10 11:00:00', 'estado' => 'programada', 'canal' => 'presencial'];
        $operacion = 'app(App\\Services\\CitaService::class)->guardar('.var_export($datos, true).');';

        $resultados = ejecutarEnParaleloHuellita($veterinario, [$operacion, $operacion]);

        expect($resultados)->toBe(['conflicto', 'creado']);
        expect(Cita::where('veterinario_id', $veterinario->id)->count())->toBe(1);
    } finally {
        Cita::where('veterinario_id', $veterinario->id)->delete();
        $mascota->delete();
        $propietario->delete();
        $veterinario->delete();
    }
});

test('dos solicitudes simultáneas no inician dos pacientes del mismo veterinario en MySQL', function () {
    $veterinario = User::factory()->create(['rol' => 'veterinario']);
    $primera = Atencion::factory()->create(['registrada_por_id' => $veterinario->id]);
    $segunda = Atencion::factory()->create(['registrada_por_id' => $veterinario->id]);
    $pacientes = [$primera->mascota, $segunda->mascota];
    try {
        $operaciones = [];
        foreach ([$primera, $segunda] as $atencion) {
            $operaciones[] = 'app(App\\Services\\AtencionService::class)->comenzar(App\\Models\\Atencion::findOrFail('.$atencion->id.'), App\\Models\\User::findOrFail('.$veterinario->id.'));';
        }

        $resultados = ejecutarEnParaleloHuellita($veterinario, $operaciones);

        expect($resultados)->toBe(['conflicto', 'creado']);
        expect(Atencion::where('veterinario_id', $veterinario->id)->where('estado', 'en_atencion')->count())->toBe(1);
    } finally {
        Atencion::whereIn('id', [$primera->id, $segunda->id])->delete();
        foreach ($pacientes as $paciente) {
            $propietario = $paciente->propietario;
            $paciente->delete();
            $propietario->delete();
        }
        $veterinario->delete();
    }
});
