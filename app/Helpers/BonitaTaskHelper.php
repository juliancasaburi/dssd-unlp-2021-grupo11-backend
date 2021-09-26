<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;


class BonitaTaskHelper
{
    /**
     * Obtener datos de la próxima tarea a realizar por el usuario autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Illuminate\Support\Collection  $userRoles
     * @return \Illuminate\Http\JsonResponse
     */
    public function nextTask(Request $request, $userRoles)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $taskName = "Revisión de la Solicitud";
            if ($userRoles->contains("escribano-area-legales"))
                $taskName = "Evaluación de estatuto";

            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL("/API/bpm/humanTask?p=0&c=1&f=displayName={$taskName}&f=state=ready");

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
