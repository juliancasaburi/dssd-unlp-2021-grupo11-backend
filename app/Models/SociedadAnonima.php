<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Wildside\Userstamps\Userstamps;

// TODO: Datos de socios
class SociedadAnonima extends Model
{
    use Userstamps;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sociedades_anonimas';


    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'fecha_creacion',
        'domicilio_legal',
        'domicilio_real',
        'email_apoderado',
        'numero_expediente',
        'numero_hash',
        'url_codigo_QR',
        'estado_evaluacion',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'fecha_creacion' => 'datetime',
    ];

    /**
     * Obtener los socios.
     */
    public function socios()
    {
        return $this->hasMany(Socio::class);
    }

    /**
     * Obtener el apoderado.
     */
    public function apoderado()
    {
        return Socio::find($this->idApoderado);
    }
}
