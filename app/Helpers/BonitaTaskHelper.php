<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;
use App\Helpers\BonitaProcessHelper;


class BonitaTaskHelper
{
    /**
     * Obtener datos de la próxima tarea a realizar por el usuario autenticado.
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  Illuminate\Support\Collection $userRoles
     * @return \Illuminate\Http\JsonResponse
     */
    public function nextTask($jsessionid, $xBonitaAPIToken, $userRoles)
    {
        try {
            $taskName = "Revisión de la Solicitud";
            if ($userRoles->contains("escribano-area-legales"))
                $taskName = "Evaluación de estatuto";

            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL("/API/bpm/humanTask?p=0&c=1&f=displayName={$taskName}&f=state=ready&f=assigned_id=0");

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener las tareas disponibles para el usuario autenticado.
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  Illuminate\Support\Collection $userRoles
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableTasks($jsessionid, $xBonitaAPIToken, $userRoles)
    {
        try {
            $taskName = "Revisión de la Solicitud";
            if ($userRoles->contains("escribano-area-legales"))
                $taskName = "Evaluación de estatuto";

            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL("/API/bpm/humanTask?p=0&f=displayName={$taskName}&f=state=ready&f=assigned_id=0");

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Asignar tarea con id al usuario autenticado.
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @param  int $bonitaUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTask($jsessionid, $xBonitaAPIToken, $taskId, $bonitaUserId)
    {
        try {
            $updateTaskDataArray = [
                "assigned_id" => $bonitaUserId,
            ];

            $bonitaProcessHelper = new BonitaProcessHelper();
            $bonitaProcessHelper->updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
