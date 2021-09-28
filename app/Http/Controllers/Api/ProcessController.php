<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\BonitaProcessHelper;

class ProcessController extends Controller
{
    /**
     * Get processes.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $url = env('BONITA_API_URL') . '/API/bpm/process?c=10';

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

        $bonitaProcessHelper = new BonitaProcessHelper();
        return $bonitaProcessHelper->processByName($jsessionid, $name);
    }

    /**
     * Obtener datos del proceso de Registro de Sociedad Anónima.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function processRegistroSociedadAnonima(Request $request)
    {
        $jsessionid = $request->cookie('JSESSIONID');

        $bonitaProcessHelper = new BonitaProcessHelper();
        return $bonitaProcessHelper->processByName($jsessionid, "Registro");
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

        $bonitaProcessHelper = new BonitaProcessHelper();
        return $bonitaProcessHelper->startProcessByName($jsessionid, $xBonitaAPIToken, $name);
    }
}
