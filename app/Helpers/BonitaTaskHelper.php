<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

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
    public static function tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/task?p=0&c=10&f=caseId=' . $bonitaCaseId);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $response = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw();

        return $response->json();
    }

    /**
     * Obtener datos de la tarea con id.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function taskDataById($jsessionid, $xBonitaAPIToken, $taskId)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $response = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw();

        return $response->json();
    }

    /**
     * Obtener datos de la tarea aplicando filtros.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  string $filter
     * @return \Illuminate\Http\JsonResponse
     */
    public static function taskDataFiltered($jsessionid, $xBonitaAPIToken, $filter)
    {
        $url = URLHelper::getBonitaEndpointURL("/API/bpm/humanTask?{$filter}");

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $response = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw();

        return $response->json();
    }

    /**
     * Obtener las tareas disponibles para el usuario autenticado.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  Illuminate\Support\Collection $userRoles
     * @return \Illuminate\Http\JsonResponse
     */
    public static function availableTasks($jsessionid, $xBonitaAPIToken, $userRoles)
    {
        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);
        $taskData = [];

        if ($userRoles->contains("escribano-area-legales")) {
            $taskName = "Evaluación de estatuto";
            $url = URLHelper::getBonitaEndpointURL("/API/bpm/humanTask?p=0&f=displayName={$taskName}&f=state=ready&f=assigned_id=0");
            $taskData = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw()->json();
        } else {
            $taskNames = ["Revisión de la Solicitud", "Creación de carpeta física"];
            foreach ($taskNames as $taskName) {
                $url = URLHelper::getBonitaEndpointURL("/API/bpm/humanTask?p=0&f=displayName={$taskName}&f=state=ready&f=assigned_id=0");
                $response = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw();
                $taskData = array_merge($taskData, $response->json());
            }
        }

        return $taskData;
    }

    /**
     * Obtener las tareas tomadas por el usuario con id.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $bonitaUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function userTasks($jsessionid, $xBonitaAPIToken, $bonitaUserId)
    {
        $url = URLHelper::getBonitaEndpointURL("/API/bpm/humanTask?p=0&f=assigned_id={$bonitaUserId}&f=state=ready");

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken);

        $response = Http::withHeaders($bonitaAuthHeaders)->get($url)->throw();

        return $response->json();
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
    public static function updateTask($jsessionid, $xBonitaAPIToken, $taskId, $dataArray)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken, true);

        $response = Http::withHeaders($bonitaAuthHeaders)->put($url, [$dataArray])->throw();

        return $response->json();
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
    public static function executeTask($jsessionid, $xBonitaAPIToken, $taskId, $assign = false)
    {
        $url = URLHelper::getBonitaEndpointURL('/API/bpm/userTask/' . $taskId . '/execution');

        if ($assign == true)
            $url = $url . '?assign=true';

        $bonitaAuthHeaders = BonitaRequestHelper::getBonitaAuthHeaders($jsessionid, $xBonitaAPIToken, true);

        $response = Http::withHeaders($bonitaAuthHeaders)->withBody(null, 'application/json')->post($url)->throw();

        return $response->json();
    }

    /**
     * Asignar tarea con id al usuario autenticado.
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @param  int $bonitaUserId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function assignTask($jsessionid, $xBonitaAPIToken, $taskId, $bonitaUserId)
    {
        $updateTaskDataArray = [
            "assigned_id" => $bonitaUserId,
        ];

        Self::updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);
    }

    /**
     * Liberar tarea con id por parte del usuario autenticado.
     * 
     * @param  string $jsessionid
     * @param  string $xBonitaAPIToken
     * @param  int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public static function unassignTask($jsessionid, $xBonitaAPIToken, $taskId)
    {
        $updateTaskDataArray = [
            "assigned_id" => "",
        ];

        Self::updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);
    }
}
