<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;

class EstampilladoHelper
{
    /**
     * Obtener número de hash.
     *
     * @param string $numeroExpediente
     * @param array $escribanoCredentials
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitarEstampillado($numeroExpediente, $escribanoCredentials)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getServicioEstampilladoURL();

            /* Envío del estatuto, número de expediente y credenciales del escribano que envía a estampillar.
            La respuesta del servicio será un número de hash asociado.*/
            // TODO: ver Envío del estatuto
            $response = Http::post($url, [
                "numeroExpediente" => $numeroExpediente,
                "email" => $escribanoCredentials["email"],
                "password" => $escribanoCredentials["password"]
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
