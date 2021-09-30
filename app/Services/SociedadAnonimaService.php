<?php

namespace App\Services;

use App\Models\SociedadAnonima;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;

class SociedadAnonimaService
{
    public function getPrivateFolderUrl(string $nombreSociedad) {
        $metaData = Storage::disk('google')->getDriver()->getAdapter()->getMetaData("DSSD-UNLP-2021-GRUPO11-BACKEND/Privado/{$nombreSociedad}");
        return "https://drive.google.com/drive/folders{$metaData["virtual_path"]}";
    }

    public function storeNewSociedadAnonima(
        $archivoEstatuto,
        string $nombre,
        string $fecha_creacion,
        string $domicilio_legal,
        string $domicilio_real,
        string $email_apoderado,
        string $bonitaCaseId
    ) {
        $sociedadAnonima = new SociedadAnonima();
        $sociedadAnonima->nombre = $nombre;
        $sociedadAnonima->fecha_creacion = $fecha_creacion;
        $sociedadAnonima->domicilio_legal = $domicilio_legal;
        $sociedadAnonima->domicilio_real = $domicilio_real;
        $sociedadAnonima->email_apoderado = $email_apoderado;
        $sociedadAnonima->estado_evaluacion = "Pendiente mesa de entradas";
        $sociedadAnonima->bonita_case_id = $bonitaCaseId;

        $config = new Config();
        Storage::disk('google')->getDriver()->getAdapter()->createDir("DSSD-UNLP-2021-GRUPO11-BACKEND/Privado/{$nombre}", $config);
        $archivoEstatuto->storeAs("DSSD-UNLP-2021-GRUPO11-BACKEND/Privado/{$nombre}", "estatuto_{$nombre}.{$archivoEstatuto->extension()}", 'google');
        Storage::disk('google')->getDriver()->getAdapter()->setVisibility("DSSD-UNLP-2021-GRUPO11-BACKEND/Privado/{$nombre}", "public");

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

    public function getUserSociedadesAnonimasWithSocios(User $user)
    {
        return SociedadAnonima::with('socios')->where('created_by', $user->id)->get();
    }

    public function getSociedadAnonimaWithSociosByCaseId(int $bonitaCaseId)
    {
        return SociedadAnonima::with('socios')->where('bonita_case_id', $bonitaCaseId)->first();
    }

    public function getSociedadAnonimaByCaseId(int $bonitaCaseId)
    {
        return SociedadAnonima::where('bonita_case_id', $bonitaCaseId)->first();
    }
}
