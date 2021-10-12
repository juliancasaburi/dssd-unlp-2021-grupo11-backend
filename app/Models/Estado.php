<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estado extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Obtener el pais.
     */
    public function pais()
    {
        return $this->belongsTo(Pais::class);
    }

    /**
     * Obtener las sociedades anonimas que exportan a este estado.
     */
    public function sociedadesAnonimas()
    {
        return $this->belongsToMany(SociedadAnonima::class, 'sociedades_anonimas_estados', 'estado_id', 'sociedad_anonima_id')->withTimestamps();
    }
}
