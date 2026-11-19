<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoGasto extends Model
{
    public const MATERIALES = 1;

    protected $table = 'mglpbc_tipo_gastos';

    protected $primaryKey = 'id_tipo_gasto';

    public $timestamps = false;

    protected $fillable = ['nombre_tipo_gasto'];
}
