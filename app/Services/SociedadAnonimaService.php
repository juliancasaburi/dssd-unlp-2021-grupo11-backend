<?php

namespace App\Services;

use App\Models\SociedadAnonima;
use App\Models\Socio;

class SociedadAnonimaService
{
    public function storeNewSociedadAnonima(
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
        $sociedadAnonima->estado_evaluacion = "Pendiente mesa de entradas";
        $sociedadAnonima->save();
        return $sociedadAnonima;
    }

    public function storeSocios(
        SociedadAnonima $sociedadAnonima,
        array $socios
    ) {
        foreach ($socios as $datosSocio) {
            $socio = new Socio();
            $socio->apellido = $datosSocio["apellido"];
            $socio->nombre = $datosSocio["nombre"];
            $socio->porcentaje = $datosSocio["porcentaje"];
            $socio->id_sociedad = $sociedadAnonima->id;
            $socio->save();
            if ($datosSocio["apoderado"] == "true") {
                $sociedadAnonima->id_apoderado = $socio->id;
            }
        }
        $sociedadAnonima->save();
    }
}
