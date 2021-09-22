<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class ProcessController extends Controller
{
    /**
     * Get processes.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        if (!$jsessionid)
            return response()->json("No cookies set", 400);

        try {
            $url = env('BONITA_API_URL') . '/API/bpm/process?p=0&c=10';

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener datos del proceso de Registro de Sociedad Anónima.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function processRegistroSociedadAnonima(Request $request)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        if (!$jsessionid)
            return response()->json("No cookies set", 400);

        try {
            $url = env('BONITA_API_URL') . '/API/bpm/process?s=Registro';

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

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
}
