<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use App\Services\SociedadAnonimaService;

class SociedadAnonima extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'fecha_creacion' => $this->fecha_creacion,
            'domicilio_legal' => $this->domicilio_legal,
            'domicilio_real' => $this->domicilio_real,
            'email_apoderado' => $this->email_apoderado,
            'estado_evaluacion' => $this->estado_evaluacion,
            'apoderado_id' => $this->apoderado_id,
            'numero_expediente' => $this->numero_expediente,
            'numero_hash' => $this->numero_hash,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'url_carpeta_estatuto' => $this->when(Auth::user()->getRoleNames()->first() == 'escribano-area-legales', function () {
                $service = new SociedadAnonimaService();
                return $service->getPrivateFolderUrl($this->nombre);
            }),
            'url_carpeta_apoderado' => $this->when($this->estado_evaluacion == 'Sociedad registrada', function () {
                $service = new SociedadAnonimaService();
                return $service->getPublicFolderUrl($this->nombre);
            }),
            'socios' => Socio::Collection($this->socios),
            'geo' => [
                "paises" => Pais::Collection($this->paises),
                "estados" => Estado::Collection($this->estados->sortBy('pais')),
            ],
        ];
    }
}
