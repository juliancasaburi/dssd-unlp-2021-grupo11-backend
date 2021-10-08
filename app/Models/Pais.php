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
        'continent'
    ];

    /**
     * Obtener los estados.
     */
    public function estados()
    {
        return $this->hasMany(Estado::class);
    }
}
