<?php

namespace App\Models;

use Database\Factories\PropietarioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['nombre_completo', 'documento_identidad', 'telefono', 'correo', 'direccion', 'observaciones'])]
class Propietario extends Model
{
    /** @use HasFactory<PropietarioFactory> */
    use HasFactory;

    protected $table = 'propietarios';

    protected function casts(): array
    {
        return [];
    }

    /** @return HasMany<Mascota, $this> */
    public function mascotas(): HasMany
    {
        return $this->hasMany(Mascota::class, 'propietario_id');
    }
}
