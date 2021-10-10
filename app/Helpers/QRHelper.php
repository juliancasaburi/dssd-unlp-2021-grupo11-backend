<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;

class QRHelper
{
    /**
     * Generar código QR.
     * 
     * @param string $content
     * @return \Illuminate\Http\JsonResponse
     */
    public function generarQR($content)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getQRAPIURL();

            /*  se genera un código QR asociado al expediente estampillado que contiene una
            URL que permitirá acceder a la información pública de la sociedad */
            $response = Http::get($url, [
                'size' => '150x150',
                'data' => $content,
            ]);

            return $response->getBody();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
