<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Socio extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'apellido',
        'nombre',
        'porcentaje',
        'sociedad_anonima_id',
    ];

    /**
     * Obtener la sociedad anonima.
     */
    public function sociedadAnonima()
    {
        return $this->belongsTo(SociedadAnonima::class);
    }
}
