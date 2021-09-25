<?php

namespace App\Services;

use App\Models\SociedadAnonima;

class SociedadAnonimaService
{
    public function storeNewUser(
        string $nombre,
        string $fecha_creacion,
        string $domicilio_legal,
        string $domicilio_real,
        string $email_apoderado
    ) {
        $sociedadAnonima = new SociedadAnonima();
        $sociedadAnonima->nombre = $nombre;
        $sociedadAnonima->fecha_creacion = $fecha_creacion;
        $sociedadAnonima->domicilio_legal = $domicilio_legal;
        $sociedadAnonima->domicilio_real = $domicilio_real;
        $sociedadAnonima->email_apoderado = $email_apoderado;
        /* TODO: Guardar id del socio que es apoderado
        $sociedadAnonima->id_apoderado = */
        $sociedadAnonima->save();
    }
}
