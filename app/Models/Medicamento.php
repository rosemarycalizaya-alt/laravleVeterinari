<?php

namespace App\Models;

use Database\Factories\MedicamentoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property bool|null $activo
 */
#[Fillable(['nombre', 'principio_activo', 'presentacion', 'concentracion', 'activo'])]
class Medicamento extends Model
{
    /** @use HasFactory<MedicamentoFactory> */
    use HasFactory;

    protected $table = 'medicamentos';

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    /** @return HasMany<MedicamentoConsulta, $this> */
    public function registros(): HasMany
    {
        return $this->hasMany(MedicamentoConsulta::class, 'medicamento_id');
    }
}
