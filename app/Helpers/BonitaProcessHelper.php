<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class BonitaProcessHelper
{
    /**
     * Obtener datos del proceso con nombre.
     *
     * @param  string $jsessionid
     * @param  string  $name
     * @return \Illuminate\Http\JsonResponse
     */
    public static function processByName($jsessionid, $name)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/process?s=' . $name);

        $response = Http::withHeaders([
            'Cookie' => 'JSESSIONID=' . $jsessionid,
        ])->get($url)->throw();

        return $response->json();
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
    public static function updateCaseVariable($jsessionid, $xBonitaAPIToken, $caseId, $variableName, $type, $value)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/caseVariable/' . $caseId . '/' . $variableName);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken, true);

        $response = Http::withHeaders($bonitaAuthHeaders)->put($url, [
            "type" => $type,
            "value" => $value,
        ])->throw();

        return $response->json();
    }

    /**
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $name
     * @param  array  $caseData
     * @return \Illuminate\Http\JsonResponse
     */
    public static function startProcessByName($jsessionid, $xBonitaAPIToken, $name, $caseData)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/process?s=' . $name);

        $response = Http::withHeaders([
            'Cookie' => 'JSESSIONID=' . $jsessionid,
        ])->get($url)->throw();

        $processId = head($response->json())['id'];
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/case');

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken, true);
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
        ])->throw();

        return response(json_decode($bonitaRegisterResponse->body(), true), $bonitaRegisterResponse->status());
    }
}
