<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;
use App\Helpers\BonitaRequestHelper;

class BonitaProcessHelper
{
    /**
     * Obtener datos del proceso con nombre.
     *
     * @param  string $jsessionid
     * @param  string  $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function processByName($jsessionid, $name)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/process?s=' . $name);

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Actualizar una case variable.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int  $caseId
     * @param  string  $variableName
     * @param  string  $type
     * @param  string  $value
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCaseVariable($jsessionid, $xBonitaAPIToken, $caseId, $variableName, $type, $value)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/caseVariable/' . $caseId . '/' . $variableName);

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->put($url, [
                "type" => $type,
                "value" => $value,
            ]);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $name
     * @param  array  $caseData
     * @return \Illuminate\Http\JsonResponse
     */
    public function startProcessByName($jsessionid, $xBonitaAPIToken, $name, $caseData)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/process?s=' . $name);

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            $processId = head($response->json())['id'];
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/case');

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
            $headers = array_merge($bonitaAuthHeaders, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ]);

            /* Register Bonita User */
            $bonitaRegisterResponse = Http::withHeaders($headers)->post($url, [
                "processDefinitionId" => $processId,
                "variables" => [
                    [
                        "name" => "nombre_sociedad",
                        "value" => $caseData['nombre_sociedad']
                    ],
                    [
                        "name" => "email_apoderado",
                        "value" => $caseData['email_apoderado']
                    ],
                    [
                        "name" => "estado_evaluacion",
                        "value" => "Pendiente mesa de entradas"
                    ],
                    ],
            ]);

            return response(json_decode($bonitaRegisterResponse->body(), true), $bonitaRegisterResponse->status());
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
