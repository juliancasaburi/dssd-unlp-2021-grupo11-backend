<?php

namespace App\Helpers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use App\Helpers\URLHelper;
use App\Helpers\BonitaRequestHelper;

class BonitaTaskHelper
{
    /**
     * Obtener tareas para el caso con id.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int  $bonitaCaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/task?p=0&c=10&f=caseId=' . $bonitaCaseId);

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener datos de la tarea con id.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskDataById($jsessionid, $xBonitaAPIToken, $taskId)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener datos de la tarea aplicando filtros.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskDataFiltered($jsessionid, $xBonitaAPIToken, $filter)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL("/API/bpm/humanTask?{$filter}");

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener datos de la próxima tarea a realizar por el usuario autenticado.
     * 
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

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener las tareas disponibles para el usuario autenticado.
     * 
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

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener las tareas tomadas por el usuario con id.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $bonitaUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTasks($jsessionid, $xBonitaAPIToken, $bonitaUserId)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL("/API/bpm/humanTask?p=0&f=assigned_id={$bonitaUserId}&f=state=ready");

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->get($url);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Actualizar una tarea.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $taskId
     * @param  array $dataArray
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTask($jsessionid, $xBonitaAPIToken, $taskId, $dataArray)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->put($url, [$dataArray]);

            return $response->json();
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Ejecutar una tarea, y opcionalmente asigna.
     *
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $taskId
     * @param bool $assign
     * @return \Illuminate\Http\JsonResponse
     */
    public function executeTask($jsessionid, $xBonitaAPIToken, $taskId, $assign = false)
    {
        try {
            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/userTask/' . $taskId . '/execution');

            if ($assign == true)
                $url = $url . '?assign=true';

            $bonitaRequestHelper = new BonitaRequestHelper();
            $bonitaAuthHeaders = $bonitaRequestHelper->getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

            $response = Http::withHeaders($bonitaAuthHeaders)->withBody(null, 'application/json')->post($url);

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

            $this->updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Liberar tarea con id por parte del usuario autenticado.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unassignTask($jsessionid, $xBonitaAPIToken, $taskId)
    {
        try {
            $updateTaskDataArray = [
                "assigned_id" => "",
            ];

            $this->updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
