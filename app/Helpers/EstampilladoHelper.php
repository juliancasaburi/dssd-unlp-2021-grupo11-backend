<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;

class EstampilladoHelper
{
    /**
     * Login.
     *
     * @param array $credentials
     * @return \Illuminate\Http\JsonResponse
     */
    public function login($credentials)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getServicioEstampilladoURL() . '/api/auth/login';
        
            $response = Http::asForm()->post($url, [
                'email' => $credentials["email"],
                'password' => $credentials['password'],
            ]);

            if ($response->status() == 401)
                return response()->json("401 Unauthorized", 401);
        
            return response()->json($response, 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener número de hash.
     *
     * @param string $jwt
     * @param string $numeroExpediente
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitarEstampillado($jwt, $numeroExpediente)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getServicioEstampilladoURL() . '/api/estampillar';

            /* Envío del estatuto, número de expediente y credenciales del escribano que envía a estampillar.
            La respuesta del servicio será un número de hash asociado.*/
            // TODO: ver Envío del estatuto
            $response = Http::withToken($jwt)->post($url, [
                "numeroExpediente" => $numeroExpediente,
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
