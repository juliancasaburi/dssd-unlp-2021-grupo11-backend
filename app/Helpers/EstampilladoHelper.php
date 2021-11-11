<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use GuzzleHttp\Client as GuzzleClient;
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

            $client = new GuzzleClient();
            $response = $client->post($url, [
                'form_params' => [
                    'email' => $credentials['email'],
                    'password' => $credentials['password'],
               ],
            ]);

            if ($response->getStatusCode() == 401)
                return response()->json("401 Unauthorized", 401);
        
            return json_decode($response->getBody()->getContents(), true);
        } catch (ConnectionException $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Obtener número de hash.
     *
     * @param string $jwt
     * @param string $estatuto
     * @param string $nombreArchivoEstatuto
     * @param string $numeroExpediente
     * @return \Illuminate\Http\JsonResponse
     */
    public function solicitarEstampillado($jwt, $estatuto, $nombreArchivoEstatuto, $numeroExpediente)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getServicioEstampilladoURL() . '/api/estampillar';

            /* Envío del estatuto, número de expediente y credenciales del escribano que envía a estampillar.
            La respuesta del servicio será un número de hash asociado.*/
            $response = Http::withToken($jwt)->attach(
                "archivo_estatuto", $estatuto, $nombreArchivoEstatuto
            )->post($url, [
                "numero_expediente" => $numeroExpediente,
                "frontend_endpoint" => $urlHelper->getFrontendURL(),
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}