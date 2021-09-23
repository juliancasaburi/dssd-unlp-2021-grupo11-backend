<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client as GuzzleClient;


class BonitaProcessHelper
{
    /**
     * Obtener datos del proceso con nombre.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function processByName(Request $request, $name)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        if (!$jsessionid)
            return response()->json("No cookies set", 400);

        try {
            $url = env('BONITA_API_URL') . '/API/bpm/process?s='.$name;

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
    
    /**
     * Iniciar el proceso con nombre.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function startProcessByName(Request $request, $name)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
        if (!$jsessionid || !$xBonitaAPIToken)
            return response()->json("No cookies set", 400);

        try {
            $url = env('BONITA_API_URL') . '/API/bpm/process?s='.$name;

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            $processId = $response[0]['id'];

            $url = env('BONITA_API_URL') . '/API/bpm/process/'.$processId . '/instantiation';

            $headers = [
                'Content-Type' => 'application/json',
                'Cookie' => 'JSESSIONID='.$jsessionid.';'.'X-Bonita-API-Token='.$xBonitaAPIToken,
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