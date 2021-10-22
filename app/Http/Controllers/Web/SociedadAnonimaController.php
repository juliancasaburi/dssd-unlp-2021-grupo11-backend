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
        $pdfContents = $service->getPublicPDFContents($numeroHash);
        return response($pdfContents, 200, [
            "Content-type"        => "application/pdf",
            "Content-Disposition" => "inline; filename=info_publica_{$numeroHash}.pdf",
        ]);
    }
}
