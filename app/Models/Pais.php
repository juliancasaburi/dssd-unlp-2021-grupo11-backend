<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pais extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'paises';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
    ];

    /**
     * Obtener el Continente.
     */
    public function continente()
    {
        return $this->belongsTo(Continente::class);
    }

    /**
     * Obtener los estados.
     */
    public function estados()
    {
        return $this->hasMany(Estado::class);
    }

    /**
     * Obtener las sociedades anonimas que exportan a este país.
     */
    public function sociedadesAnonimas()
    {
        return $this->belongsToMany(SociedadAnonima::class, 'sociedades_anonimas_paises', 'pais_id', 'sociedad_anonima_id')->withTimestamps();
    }
}
