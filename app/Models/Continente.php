<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Continente extends Model
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
     * Obtener los paises.
     */
    public function pais()
    {
        return $this->hasMany(Pais::class);
    }
}
