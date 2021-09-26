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
        'id_sociedad',
    ];

    /**
     * Obtener la sociedad.
     */
    public function sociedad()
    {
        return $this->belongsTo(SociedadAnonima::class, 'id');
    }
}
