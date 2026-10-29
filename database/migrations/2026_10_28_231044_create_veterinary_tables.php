<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propietarios', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre_completo', 160)->index();
            $t->string('documento_identidad', 30)->nullable()->unique();
            $t->string('telefono', 30)->nullable()->index();
            $t->string('correo')->nullable();
            $t->string('direccion')->nullable();
            $t->text('observaciones')->nullable();
            $t->timestamps();
        });
        Schema::create('mascotas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('propietario_id')->constrained('propietarios')->restrictOnDelete();
            $t->string('nombre', 120)->index();
            $t->string('especie', 70);
            $t->string('raza', 120)->nullable();
            $t->enum('sexo', ['macho', 'hembra', 'desconocido'])->default('desconocido');
            $t->date('fecha_nacimiento')->nullable();
            $t->string('color', 100)->nullable();
            $t->string('microchip', 80)->nullable()->unique();
            $t->boolean('esterilizado')->nullable();
            $t->enum('estado', ['activa', 'inactiva', 'fallecida'])->default('activa');
            $t->string('foto_ruta', 500)->nullable();
            $t->text('observaciones')->nullable();
            $t->timestamps();
        });
        Schema::create('expedientes_clinicos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('mascota_id')->unique()->constrained('mascotas')->restrictOnDelete();
            $t->string('codigo', 25)->unique();
            $t->date('fecha_apertura');
            $t->text('alergias')->nullable();
            $t->text('enfermedades_cronicas')->nullable();
            $t->text('antecedentes_medicos')->nullable();
            $t->text('observaciones_generales')->nullable();
            $t->timestamps();
        });
        Schema::create('citas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('mascota_id')->constrained('mascotas')->restrictOnDelete();
            $t->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('registrada_por_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('inicio');
            $t->dateTime('fin');
            $t->string('motivo')->nullable();
            $t->enum('canal', ['whatsapp', 'telefono', 'presencial', 'otro'])->default('presencial');
            $t->enum('estado', ['programada', 'atendida', 'no_asistio', 'cancelada'])->default('programada');
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->index(['veterinario_id', 'inicio', 'fin']);
            $t->index(['estado', 'inicio']);
        });
        Schema::create('atenciones', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('mascota_id')->constrained('mascotas')->restrictOnDelete();
            $t->foreignId('cita_id')->nullable()->unique()->constrained('citas')->restrictOnDelete();
            $t->foreignId('registrada_por_id')->constrained('users')->restrictOnDelete();
            $t->foreignId('veterinario_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('motivo_ingreso')->nullable();
            $t->enum('estado', ['en_espera', 'en_atencion', 'finalizada', 'cancelada'])->default('en_espera');
            $t->dateTime('hora_llegada');
            $t->dateTime('hora_inicio')->nullable();
            $t->dateTime('hora_fin')->nullable();
            $t->timestamps();
            $t->index(['estado', 'hora_llegada']);
            $t->index(['veterinario_id', 'estado']);
        });
        Schema::create('consultas', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('expediente_id')->constrained('expedientes_clinicos')->restrictOnDelete();
            $t->foreignId('atencion_id')->unique()->constrained('atenciones')->restrictOnDelete();
            $t->foreignId('veterinario_id')->constrained('users')->restrictOnDelete();
            $t->dateTime('fecha_consulta');
            $t->string('motivo_consulta');
            $t->text('sintomas')->nullable();
            $t->text('examen_fisico')->nullable();
            $t->decimal('peso_kg', 6, 2)->nullable();
            $t->decimal('temperatura_c', 4, 1)->nullable();
            $t->text('diagnostico')->nullable();
            $t->text('tratamiento')->nullable();
            $t->text('recomendaciones')->nullable();
            $t->date('proximo_control')->nullable();
            $t->timestamps();
            $t->index(['expediente_id', 'fecha_consulta']);
        });
        Schema::create('medicamentos', function (Blueprint $t): void {
            $t->id();
            $t->string('nombre', 160)->index();
            $t->string('principio_activo', 160)->nullable()->index();
            $t->string('presentacion', 100)->nullable();
            $t->string('concentracion', 100)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('medicamentos_consulta', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('consulta_id')->constrained('consultas')->restrictOnDelete();
            $t->foreignId('medicamento_id')->constrained('medicamentos')->restrictOnDelete();
            $t->enum('tipo_registro', ['administrado', 'recetado']);
            $t->decimal('dosis_cantidad', 10, 3)->nullable();
            $t->string('dosis_unidad', 40)->nullable();
            $t->string('via_administracion', 80)->nullable();
            $t->string('frecuencia', 100)->nullable();
            $t->unsignedSmallInteger('duracion_dias')->nullable();
            $t->dateTime('fecha_hora_administracion')->nullable();
            $t->foreignId('aplicado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->text('indicaciones')->nullable();
            $t->timestamps();
        });
        Schema::create('vacunaciones', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('expediente_id')->constrained('expedientes_clinicos')->restrictOnDelete();
            $t->foreignId('consulta_id')->nullable()->constrained('consultas')->restrictOnDelete();
            $t->foreignId('aplicada_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $t->string('vacuna', 160);
            $t->date('fecha_aplicacion');
            $t->date('proxima_dosis')->nullable();
            $t->string('numero_lote', 100)->nullable();
            $t->text('observaciones')->nullable();
            $t->timestamps();
            $t->index(['expediente_id', 'fecha_aplicacion']);
        });
        Schema::create('documentos_clinicos', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('expediente_id')->constrained('expedientes_clinicos')->restrictOnDelete();
            $t->foreignId('consulta_id')->nullable()->constrained('consultas')->restrictOnDelete();
            $t->foreignId('subido_por_id')->constrained('users')->restrictOnDelete();
            $t->string('nombre_archivo');
            $t->string('ruta_privada', 500);
            $t->string('tipo_documento', 100)->nullable();
            $t->string('descripcion')->nullable();
            $t->date('fecha_documento')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['documentos_clinicos', 'vacunaciones', 'medicamentos_consulta', 'medicamentos', 'consultas', 'atenciones', 'citas', 'expedientes_clinicos', 'mascotas', 'propietarios'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
