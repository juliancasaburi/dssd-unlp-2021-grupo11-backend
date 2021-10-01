<?php

namespace App\Services;

use App\Models\SociedadAnonima;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;

class SociedadAnonimaService
{
    private function getPrivateFolderPathFromConfig(){
        return config('filesystems.disks.google.private_folder');
    }

    private function getPublicFolderPathFromConfig(){
        return config('filesystems.disks.google.public_folder');
    }

    public function getPrivateFolderUrl(string $nombreSociedad) {
        $metaData = Storage::disk('google')->getDriver()->getAdapter()->getMetaData("{$this->getPrivateFolderPathFromConfig()}/{$nombreSociedad}");
        return "https://drive.google.com/drive/folders" . $metaData["virtual_path"];
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
        $newFolderPath = "{$this->getPrivateFolderPathFromConfig()}/{$nombre}";
        Storage::disk('google')->getDriver()->getAdapter()->createDir($newFolderPath, $config);
        $archivoEstatuto->storeAs($newFolderPath, "estatuto_{$nombre}.{$archivoEstatuto->extension()}", 'google');
        Storage::disk('google')->getDriver()->getAdapter()->setVisibility($newFolderPath, "public");

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
