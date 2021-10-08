<?php

namespace App\Services;

use App\Models\Continente;
use App\Models\Estado;
use App\Models\Pais;
use App\Models\SociedadAnonima;
use App\Models\Socio;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Config;
use Illuminate\Support\Carbon;

class SociedadAnonimaService
{
    private function getPrivateFolderPathFromConfig()
    {
        return config('filesystems.disks.google.private_folder');
    }

    private function getPublicFolderPathFromConfig()
    {
        return config('filesystems.disks.google.public_folder');
    }

    public function getPrivateFolderUrl(string $nombreSociedad)
    {
        $metaData = Storage::disk('google')->getDriver()->getAdapter()->getMetaData("{$this->getPrivateFolderPathFromConfig()}/{$nombreSociedad}");
        return "https://drive.google.com/drive/folders" . $metaData["virtual_path"];
    }

    private function getSociedadFolderPath($nombreSociedad)
    {
        return "{$this->getPrivateFolderPathFromConfig()}/{$nombreSociedad}/";
    }

    private function storeEstatutoFile($archivoEstatuto, $nombreSociedad)
    {

        $newFolderPath = $this->getSociedadFolderPath($nombreSociedad) . Carbon::now('GMT-3')->format('d-m-y-H-i');
        $archivoEstatuto->storeAs($newFolderPath, "estatuto_{$nombreSociedad}.{$archivoEstatuto->extension()}", 'google');
    }

    private function createSociedadFolder($nombreSociedad)
    {
        $config = new Config();
        $folderPath = $this->getSociedadFolderPath($nombreSociedad);

        Storage::disk('google')->getDriver()->getAdapter()->createDir($folderPath, $config);
        Storage::disk('google')->getDriver()->getAdapter()->setVisibility($folderPath, "public");
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

        $this->createSociedadFolder($nombre);
        $this->storeEstatutoFile($archivoEstatuto, $nombre);

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
            $socio->sociedad_anonima_id = $sociedadAnonima->id;
            $socio->save();
            if ($datosSocio["apoderado"] == "true") {
                $sociedadAnonima->apoderado_id = $socio->id;
            }
        }
        $sociedadAnonima->save();
    }

    public function storeEstados(
        SociedadAnonima $sociedadAnonima,
        array $paisesEstados
    ) {
        foreach ($paisesEstados as $datosPaisEstados) {
            $continente = Continente::firstOrCreate([
                'name' => $datosPaisEstados["continent"]
            ]);
            $pais = Pais::firstOrCreate([
                'code' => $datosPaisEstados["code"],
                'name' => $datosPaisEstados["name"]
            ]);
            $pais->continente()->associate($continente);
            $pais->save();
            foreach ($datosPaisEstados["estados"] as $datosEstado){
                $estado = Estado::firstOrCreate([
                    'name' => $datosEstado["name"],
                ]);
                $estado->pais()->associate($pais);
                $estado->save();
                $sociedadAnonima->estados()->save($estado);
            }
        }
    }

    public function updateSociedadAnonima(
        SociedadAnonima $sociedadAnonima,
        string $fecha_creacion,
        string $domicilio_legal,
        string $domicilio_real,
        string $email_apoderado
    ) {
        $sociedadAnonima->fecha_creacion = $fecha_creacion;
        $sociedadAnonima->domicilio_legal = $domicilio_legal;
        $sociedadAnonima->domicilio_real = $domicilio_real;
        $sociedadAnonima->email_apoderado = $email_apoderado;
        $sociedadAnonima->estado_evaluacion = "Pendiente mesa de entradas";

        $sociedadAnonima->save();
    }

    public function updateSocios(
        SociedadAnonima $sociedadAnonima,
        array $socios
    ) {
        $sociedadAnonima->socios()->delete();

        foreach ($socios as $datosSocio) {
            $socio = new Socio();
            $socio->apellido = $datosSocio["apellido"];
            $socio->nombre = $datosSocio["nombre"];
            $socio->porcentaje = $datosSocio["porcentaje"];
            $socio->sociedad_anonima_id = $sociedadAnonima->id;
            $socio->save();
            if ($datosSocio["apoderado"] == "true") {
                $sociedadAnonima->apoderado_id = $socio->id;
            }
        }
        
        $sociedadAnonima->save();
    }

    public function updateEstatuto(
        $archivoEstatuto,
        $nombreSociedad
    ) {
        $this->storeEstatutoFile($archivoEstatuto, $nombreSociedad);
    }

    public function getUserSociedadesAnonimasWithSocios(User $user)
    {
        return SociedadAnonima::with('socios')->where('created_by', $user->id)->get();
    }

    public function getSociedadAnonimaWithSociosById(int $id)
    {
        return SociedadAnonima::with('socios')->find($id);
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
