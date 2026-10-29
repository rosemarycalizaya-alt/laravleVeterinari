<?php

namespace App\Models;

use Database\Factories\DocumentoClinicoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $fecha_documento
 */
#[Fillable(['expediente_id', 'consulta_id', 'subido_por_id', 'nombre_archivo', 'ruta_privada', 'tipo_documento', 'descripcion', 'fecha_documento'])]
class DocumentoClinico extends Model
{
    /** @use HasFactory<DocumentoClinicoFactory> */
    use HasFactory;

    protected $table = 'documentos_clinicos';

    protected function casts(): array
    {
        return ['fecha_documento' => 'date'];
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
    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por_id');
    }
}
