<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use App\Helpers\BonitaTaskHelper;
use App\Models\SociedadAnonima;
use App\Services\SociedadAnonimaService;
use Illuminate\Support\Arr;

class TaskController extends Controller
{
    /**
     * Primera tarea disponible para el usuario autenticado.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function nextTask(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->nextTask($jsessionid, $xBonitaAPIToken, auth()->user()->getRoleNames());

            $responseData = head($response);
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
     * @OA\Get(
     *    path="/api/availableEmployeeTasks",
     *    summary="Tareas disponibles para el empleado autenticado",
     *    description="Tareas disponibles para el empleado autenticado",
     *    operationId="availableTasks",
     *    tags={"tareas-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Response(
     *       response=200,
     *       description="JSON con tareas disponibles, listas para asignar",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * ) 
     * 
     * @param  \Illuminate\Http\Request $request
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
     * Tareas tomadas por el usuario autenticado.
     *
     * @OA\Get(
     *    path="/api/employeeTasks/",
     *    summary="Tareas asignadas al empleado autenticado",
     *    description="Tareas asignadas al empleado autenticado",
     *    operationId="userTasks",
     *    tags={"tareas-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Response(
     *       response=200,
     *       description="JSON con tareas asignadas al empleado autenticado",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * ) 
     * 
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function userTasks(Request $request)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->userTasks($jsessionid, $xBonitaAPIToken, auth()->user()->bonita_user_id);

            return response()->json($response, 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Obtener datos de una task + datos de la SociedadAnonima asociada
     *
     * @OA\Get(
     *    path="/api/employeeTask/{taskId}",
     *    summary="Tarea con id {taskId} y datos de la S.A. asociada",
     *    description="Tarea con id {taskId} y datos de la S.A. asociada",
     *    operationId="getTaskSociedadDataById",
     *    tags={"tareas-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *       name="taskId",
     *       in="path",
     *       required=true,
     *       @OA\Schema(
     *         type="string"
     *       )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="JSON con tarea + datos S.A.",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * ) 
     * 
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTaskSociedadDataById(Request $request, SociedadAnonimaService $service, $taskId)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
        $bonitaTaskHelper = new BonitaTaskHelper();
        $taskData = $bonitaTaskHelper->taskDataById($jsessionid, $xBonitaAPIToken, $taskId);
        $sociedadAnonima = $service->getSociedadAnonimaWithSociosByCaseId($taskData["caseId"]);
        $sociedadAnonima["url_carpeta_estatuto"] = $service->getPrivateFolderUrl($sociedadAnonima->nombre);
        return response()->json([
            "task" => Arr::only($taskData, ['displayName', 'assigned_date', 'dueDate']),
            "sociedadAnonima" => $sociedadAnonima
        ], 200);
    }

    /**
     * Asignar tarea con id al usuario autenticado.
     *
     * @OA\Post(
     *    path="/api/assignTask/{taskId}",
     *    summary="Asignar tarea con id {taskId} al empleado autenticado",
     *    description="Asignar tarea con id {taskId} al empleado autenticado",
     *    operationId="assignTask",
     *    tags={"tareas-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *       name="taskId",
     *       in="path",
     *       required=true,
     *       @OA\Schema(
     *         type="string"
     *       )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Tarea asignada",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * ) 
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTask(Request $request, $taskId)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->taskDataById($jsessionid, $xBonitaAPIToken, $taskId);

            if ($response["assigned_id"] != 0)
                return response()->json("La tarea ya se encuentra asignada. Primero debe ser liberada.", 403); 

            $response = $bonitaTaskHelper->assignTask($jsessionid, $xBonitaAPIToken, $taskId, auth()->user()->bonita_user_id);

            return response()->json("Tarea asignada", 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }

    /**
     * Liberar tarea con id por parte del usuario autenticado.
     *
     * @OA\Post(
     *    path="/api/unassignTask/{taskId}",
     *    summary="Liberar tarea con id {taskId}, que estaba asignada al usuario autenticado",
     *    description="Liberar tarea con id {taskId}, que estaba asignada al usuario autenticado",
     *    operationId="unassignTask",
     *    tags={"tareas-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *       name="taskId",
     *       in="path",
     *       required=true,
     *       @OA\Schema(
     *         type="string"
     *       )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Tarea liberada",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=500,
     *       description="500 internal server error",
     *       @OA\JsonContent(
     *          example="500 internal server error"
     *       )
     *    ),
     * ) 
     * 
     * @param  \Illuminate\Http\Request $request
     * @param int $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unassignTask(Request $request, $taskId)
    {
        try {
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->taskDataById($jsessionid, $xBonitaAPIToken, $taskId);

            if (!$response["assigned_id"] == auth()->user()->bonita_user_id)
                return response()->json("No estás asginado a la tarea. No puedes liberarla.", 403); 

            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->unassignTask($jsessionid, $xBonitaAPIToken, $taskId);

            return response()->json("Tarea liberada", 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
