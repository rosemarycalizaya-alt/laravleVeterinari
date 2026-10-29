<?php

namespace App\Models;

use Database\Factories\VacunacionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $fecha_aplicacion
 * @property Carbon|null $proxima_dosis
 */
#[Fillable(['expediente_id', 'consulta_id', 'aplicada_por_id', 'vacuna', 'fecha_aplicacion', 'proxima_dosis', 'numero_lote', 'observaciones'])]
class Vacunacion extends Model
{
    /** @use HasFactory<VacunacionFactory> */
    use HasFactory;

    protected $table = 'vacunaciones';

    protected function casts(): array
    {
        return ['fecha_aplicacion' => 'date', 'proxima_dosis' => 'date'];
    }

    /** @return BelongsTo<ExpedienteClinico, $this> */
    public function expediente(): BelongsTo
    {
        return $this->belongsTo(ExpedienteClinico::class, 'expediente_id');
    }

    /** @return BelongsTo<Consulta, $this> */
    public function consulta(): BelongsTo
    {
        return $this->belongsTo(Consulta::class, 'consulta_id');
    }

    /** @return BelongsTo<User, $this> */
    public function aplicadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aplicada_por_id');
    }
}
