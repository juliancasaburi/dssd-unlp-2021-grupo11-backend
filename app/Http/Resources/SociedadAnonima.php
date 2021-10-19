<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Estado;
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'url_carpeta_estatuto' => $this->when(Auth::user()->getRoleNames()->first() == 'escribano-area-legales', function () {
                $service = new SociedadAnonimaService();
                return $service->getPrivateFolderUrl($this->nombre);
            }),
            'url_carpeta_apoderado' => $this->when($this->estado_evaluacion == 'Aprobado por escribano-area-legales', function () {
                $service = new SociedadAnonimaService();
                return $service->getPublicFolderUrl($this->nombre);
            }),
            'socios' => $this->socios,
            'estados' => Estado::collection($this->estados->sortBy('pais')),
        ];
    }
}
