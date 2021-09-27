<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use App\Helpers\BonitaTaskHelper;
use App\Models\SociedadAnonima;
use App\Helpers\URLHelper;
use Illuminate\Support\Facades\Http;

class TaskController extends Controller
{
    /**
     * Primera tarea disponible para el usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function nextTask(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->nextTask($jsessionid, $xBonitaAPIToken, auth()->user()->getRoleNames());

            $responseData = $response[0];
            $sociedad = SociedadAnonima::with(['apoderado', 'socios'])->where('bonita_case_id', $responseData["caseId"])->first();
            $responseData["datosSociedad"] = $sociedad;

            return response()->json($responseData, 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Tareas disponibles para el usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableTasks(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->availableTasks($jsessionid, $xBonitaAPIToken, auth()->user()->getRoleNames());

            return response()->json($response, 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Asignar tarea con id al usuario autenticado.
     *
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTask(Request $request, $taskId)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get($url);

            if ($response["assigned_id"] != 0)
                return response()->json("La tarea ya se encuentra asignada. Primero debe ser liberada.", 403); 

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->assignTask($jsessionid, $xBonitaAPIToken, $taskId, auth()->user()->bonita_user_id);

            return response()->json("Tarea asignada", 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Liberar tarea con id por parte del usuario autenticado.
     *
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unassignTask(Request $request, $taskId)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $urlHelper = new URLHelper();
            $url = $urlHelper->getBonitaEndpointURL('/API/bpm/humanTask/' . $taskId);

            $response = Http::withHeaders([
                'Cookie' => 'JSESSIONID=' . $jsessionid . ';' . 'X-Bonita-API-Token=' . $xBonitaAPIToken,
                'X-Bonita-API-Token' => $xBonitaAPIToken,
            ])->get($url);

            if (!$response["assigned_id"] == auth()->user()->bonita_user_id)
                return response()->json("La tarea se encuentra asignada a otro usuario. No puedes liberarla.", 403); 

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->unassignTask($jsessionid, $xBonitaAPIToken, $taskId, "");

            return response()->json("Tarea liberada", 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
