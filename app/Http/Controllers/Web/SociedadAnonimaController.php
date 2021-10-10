<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\SociedadAnonimaService;

class SociedadAnonimaController extends Controller
{

    /**
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function infoPublicaSA(SociedadAnonimaService $service, $numeroHash)
    {
        // TODO: retornar pdf
        return response()->json($service->getSociedadAnonimaWithSociosByNumeroHash($numeroHash));
    }
}
