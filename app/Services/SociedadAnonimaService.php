<?php

namespace App\Services;

use App\Http\Resources\SociedadAnonimaCollection;
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

    public function getPublicFolderUrl(string $nombreSociedad)
    {
        $metaData = Storage::disk('google')->getDriver()->getAdapter()->getMetaData("{$this->getPublicFolderPathFromConfig()}/{$nombreSociedad}");
        return "https://drive.google.com/drive/folders" . $metaData["virtual_path"];
    }

    private function getSociedadFolderPath($nombreSociedad, $private = true)
    {
        if ($private)
            return "{$this->getPrivateFolderPathFromConfig()}/{$nombreSociedad}/";
        else
            return "{$this->getPublicFolderPathFromConfig()}/{$nombreSociedad}/";
    }

    private function storeEstatutoFile($archivoEstatuto, $nombreSociedad)
    {

        $folderPath = $this->getSociedadFolderPath($nombreSociedad) . Carbon::now('GMT-3')->format('d-m-y-H-i');
        $archivoEstatuto->storeAs($folderPath, "estatuto_{$nombreSociedad}.{$archivoEstatuto->extension()}", 'google');
    }

    private function createSociedadFolder($nombreSociedad, $private = true)
    {
        $config = new Config();
        if ($private)
            $folderPath = $this->getSociedadFolderPath($nombreSociedad);
        else
            $folderPath = $this->getSociedadFolderPath($nombreSociedad, false);

        Storage::disk('google')->getDriver()->getAdapter()->createDir($folderPath, $config);
        Storage::disk('google')->getDriver()->getAdapter()->setVisibility($folderPath, "public");
    }

    private function storePDFFile($pdf, $nombreSociedad)
    {
        $folderPath = $this->getSociedadFolderPath($nombreSociedad, false);
        Storage::disk('google')->put($folderPath."info_publica_{$nombreSociedad}.pdf", $pdf);
    }

    private function storeQRFile($qr, $nombreSociedad)
    {
        $folderPath = $this->getSociedadFolderPath($nombreSociedad, false);
        Storage::disk('google')->put($folderPath."qr_{$nombreSociedad}.png", $qr);
    }

    private function getEstatutoFileData($nombreSociedad){
        $newestFolderData = last(Storage::disk('google')->getDriver()->getAdapter()->listContents($this->getSociedadFolderPath($nombreSociedad, true), false));
        return last(Storage::disk('google')->getDriver()->getAdapter()->listContents($newestFolderData["path"], false));
    }

    public function getEstatutoContents($nombreSociedad)
    {
        $estatutoFileData = $this->getEstatutoFileData($nombreSociedad);
        return Storage::disk('google')->getDriver()->getAdapter()->read("{$estatutoFileData["path"]}")['contents'];
    }

    public function getPublicPDFContents($numeroHash)
    {
        $nombreSociedad = SociedadAnonima::where('numero_hash', $numeroHash)->first()->nombre;
        return Storage::disk('google')->getDriver()->getAdapter()->read($this->getSociedadFolderPath($nombreSociedad, false) . "info_publica_{$nombreSociedad}.pdf")['contents'];
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

    public function storeQR(
        $qr,
        $nombreSociedad
    ) {
        $this->storeQRFile($qr, $nombreSociedad);
    }

    public function storePDF(
        $pdf,
        $nombreSociedad
    ) {
        $this->storePDFFile($pdf, $nombreSociedad);
    }

    public function copyEstatutoToPublico($nombreSociedad) {
        $estatutoFileData = $this->getEstatutoFileData($nombreSociedad);
        $this->createSociedadFolder($nombreSociedad, false);
        Storage::disk('google')->getDriver()->getAdapter()->copy($estatutoFileData["path"], $this->getSociedadFolderPath($nombreSociedad, false)."/estatuto_{$nombreSociedad}.{$estatutoFileData["extension"]}");
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

    public function getUserSociedadesAnonimasWithSociosAndEstados(User $user)
    {
        return SociedadAnonima::with(['socios', 'estados'])->where('created_by', $user->id)->get();
    }

    public function getSociedadAnonimaWithSociosAndEstadosById(int $id)
    {
        return SociedadAnonima::with(['socios', 'estados'])->find($id);
    }

    public function getSociedadAnonimaWithSociosByNumeroHash(string $numeroHash)
    {
        return SociedadAnonima::with('socios')->where('numero_hash', $numeroHash)->first();
    }

    public function getSociedadAnonimaWithSociosAndEstadosByCaseId(int $bonitaCaseId)
    {
        return SociedadAnonima::with(['socios', 'estados'])->where('bonita_case_id', $bonitaCaseId)->first();
    }

    public function getSociedadAnonimaByCaseId(int $bonitaCaseId)
    {
        return SociedadAnonima::where('bonita_case_id', $bonitaCaseId)->first();
    }
}
