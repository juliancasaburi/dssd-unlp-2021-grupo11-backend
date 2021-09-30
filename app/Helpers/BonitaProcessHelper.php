<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client as GuzzleClient;
use App\Helpers\URLHelper;


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

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->put($url, [
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function startProcessByName($jsessionid, $xBonitaAPIToken, $name)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/process?s=' . $name);

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            $processId = head($response->json())['id'];

            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/process/' . $processId . '/instantiation');

            $headers = [
                'Content-Type' => 'application/json',
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ];

            $client = new GuzzleClient([
                'headers' => $headers
            ]);

            $response = $client->request('POST', $url);
            $status = $response->getStatusCode();
            $response_body = $response->getBody()->getContents();

            return response()->json(json_decode($response_body), $status);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
