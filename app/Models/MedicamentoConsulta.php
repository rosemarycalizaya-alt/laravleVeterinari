<?php

namespace App\Models;

use Database\Factories\MedicamentoConsultaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string|null $dosis_cantidad
 * @property Carbon|null $fecha_hora_administracion
 */
#[Fillable(['consulta_id', 'medicamento_id', 'tipo_registro', 'dosis_cantidad', 'dosis_unidad', 'via_administracion', 'frecuencia', 'duracion_dias', 'fecha_hora_administracion', 'aplicado_por_id', 'indicaciones'])]
class MedicamentoConsulta extends Model
{
    /** @use HasFactory<MedicamentoConsultaFactory> */
    use HasFactory;

    protected $table = 'medicamentos_consulta';

    protected function casts(): array
    {
        return ['dosis_cantidad' => 'decimal:3', 'fecha_hora_administracion' => 'datetime'];
    }

    /** @return BelongsTo<Consulta, $this> */
    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    /** @return BelongsTo<Medicamento, $this> */
    public function medicamento(): BelongsTo
    {
        return $this->belongsTo(Medicamento::class, 'medicamento_id');
    }

    /** @return BelongsTo<User, $this> */
    public function aplicadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicado_por_id');
    }
}
