<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCosto extends Model
{
    // IDs fijos del catálogo (CatalogoSeeder): Normal = gasto presupuestado, Extra = gasto adicional
    public const PRESUPUESTADO = 1;

    public const ADICIONAL = 2;

    public const EXTRA = 'Extra';

    protected $table = 'mglpbc_tipo_costo';

    protected $primaryKey = 'id_tipo_costo';

    public $timestamps = false;

    protected $fillable = ['nombre_tipo_costo'];
}
