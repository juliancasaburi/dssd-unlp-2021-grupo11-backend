<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\BonitaProcessHelper;
use App\Services\SociedadAnonimaService;
use App\Helpers\BonitaTaskHelper;
use App\Models\SociedadAnonima;
use Illuminate\Validation\Rule;
use App\Models\User;

class SociedadAnonimaController extends Controller
{
    /**
     * Obtener las sociedad anónimas registradas por el usuario actual.
     *
     * @OA\Get(
     *    path="/api/sociedadesAnonimas",
     *    summary="Sociedades anonimas",
     *    description="Sociedades anonimas del usuario logueado",
     *    operationId="getUserSociedadesAnonimas",
     *    tags={"sociedadAnonima-apoderado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Response(
     *       response=200,
     *       description="JSON con datos de la S.A.",
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSociedadesAnonimas(SociedadAnonimaService $service)
    {
        return response()->json($service->getUserSociedadesAnonimasWithSocios(auth()->user()), 200);
    }

    /**
     * Obtener la sociedad anónima con id.
     *
     * @OA\Get(
     *    path="/api/sociedadAnonima/{id}",
     *    summary="Sociedad anónima",
     *    description="Sociedad anonima con id",
     *    operationId="getUserSociedadAnonima",
     *    tags={"sociedadAnonima-apoderado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="JSON con datos de la S.A.",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *    @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="Forbidden"
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
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSociedadAnonima(Request $request, $id, SociedadAnonimaService $service)
    {
        $sociedadAnonima = $service->getSociedadAnonimaWithSociosById($id);
        if ($request->user()->cannot('view', $sociedadAnonima))
            return response()->json("Forbidden", 403);
        else
            return response()->json($sociedadAnonima, 200);
    }

    /**
     * Obtener la sociedad anónima con bonitaCaseId.
     *
     * @OA\Get(
     *    path="/api/sociedadAnonimaByCaseId/{id}",
     *    summary="Sociedad anonima",
     *    description="Sociedades anonima por caseId de Bonita",
     *    operationId="getSociedadAnonimaByCaseId",
     *    tags={"sociedadAnonima-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *         name="bonitaCaseId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="JSON con datos de las S.A.",
     *       @OA\JsonContent(
     *          example=""
     *       )
     *    ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="No tienes acceso a los datos de esta sociedad.",
     *       @OA\JsonContent(
     *          example="No tienes acceso a los datos de esta sociedad."
     *       )
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
     * @param \Illuminate\Http\Request $request
     * @param int $bonitaCaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSociedadAnonimaByCaseId(Request $request, SociedadAnonimaService $service, $bonitaCaseId)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
        $bonitaTaskHelper = new BonitaTaskHelper();
        $taskData = head($bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId));

        $user = auth()->user();
        if ($taskData["assigned_id"] != $user->bonita_user_id)
            return response()->json("No tienes acceso a los datos de esta sociedad.", 403);

        $sociedadAnonima = $service->getSociedadAnonimaWithSociosByCaseId($bonitaCaseId);

        if ($user->getRoleNames()->first() == 'escribano-area-legales')
            $sociedadAnonima["url_carpeta_estatuto"] = $service->getPrivateFolderUrl($sociedadAnonima->nombre);

        return response()->json($sociedadAnonima, 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Aprobar/Rechazar.
     *
     * @OA\Post(
     *    path="/api/updateSociedadAnonimaStatus/{taskId}",
     *    summary="Sociedad anonima",
     *    description="Aprobar/Rechazar una tarea asignada, del empleado autenticado",
     *    operationId="updateSociedadAnonimaStatus",
     *    tags={"sociedadAnonima-empleado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *         name="taskId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\RequestBody(
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             type="object", 
     *             @OA\Property(
     *                property="aprobado",
     *                type="string"
     *             ),
     *          ),
     *      )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Tarea aprobada/rechazada",
     *       @OA\JsonContent(
     *          example="Tarea aprobada/rechazada"
     *       )
     *    ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="No puedes aprobar/rechazar esta tarea",
     *       @OA\JsonContent(
     *          example="No puedes aprobar/rechazar esta tarea."
     *       )
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
    public function updateSociedadAnonimaStatus(Request $request, SociedadAnonimaService $service, $taskId)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

        $bonitaTaskHelper = new BonitaTaskHelper();
        $response = $bonitaTaskHelper->taskDataById($jsessionid, $xBonitaAPIToken, $taskId);

        if ($response["state"] != "ready" or $response["assigned_id"] != auth()->user()->bonita_user_id)
            return response()->json("No puedes aprobar/rechazar esta tarea.", 403);

        // Actualizar el case de Bonita
        $bonitaCaseId = $response["caseId"];
        $sociedadAnonima = $service->getSociedadAnonimaByCaseId($bonitaCaseId);

        $rol = auth()->user()->getRoleNames()->first();
        $nuevoEstadoEvaluacion = '';
        $bonitaProcessHelper = new BonitaProcessHelper();

        if ($request->input('aprobado') == "true") {
            $nuevoEstadoEvaluacion = "Aprobado por {$rol}";
            // Setear numero_expediente
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "numero_expediente", "java.lang.String", $sociedadAnonima->id);
        } else {
            $nuevoEstadoEvaluacion = "Rechazado por {$rol}";
        }

        // estado_evaluacion
        $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", $nuevoEstadoEvaluacion);
        // Completar la tarea en Bonita
        $bonitaTaskHelper->executeTask($jsessionid, $xBonitaAPIToken, $taskId);
        // Actualizar la SociedadAnonima
        $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
        $sociedadAnonima->save();

        return response()->json("Tarea aprobada/rechazada", 200);
    }

    /**
     * Corregir una SociedadAnonima rechazada por mesa de entradas.
     * 
     * @OA\Patch(
     *    path="/api/sociedadAnonima/{id}",
     *    summary="Corregir Sociedad Anonima rechazada por mesa de entradas",
     *    description="Corregir Sociedad Anonima rechazada por mesa de entradas",
     *    operationId="patchSociedadAnonima",
     *    tags={"sociedadAnonima-apoderado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="S.A. actualizada",
     *       @OA\JsonContent(
     *          example="S.A. actualizada"
     *       )
     *    ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="No puedes corregir esta S.A.",
     *       @OA\JsonContent(
     *          example="No puedes corregir esta S.A."
     *       )
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
     * @param  \Illuminate\Http\Request $idSociedad
     * @return \Illuminate\Http\JsonResponse
     */
    public function patchSociedadAnonima(SociedadAnonimaService $service, Request $request, $idSociedad)
    {
        try {
            $sociedadAnonima = SociedadAnonima::find($idSociedad);

            if ($request->user()->cannot('patch', $sociedadAnonima))
                return response()->json("No puedes corregir esta S.A.", 403);

            $sociedadAnonimaValidator = Validator::make($request->all(), [
                'fecha_creacion' => 'required|date|',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
            ]);

            $sociosArray = json_decode($request->input('socios'), true);
            $sociosValidator = Validator::make($sociosArray, [
                '*.nombre' => 'required|string|between:2,100',
                '*.apellido' => 'required|string|between:2,100',
                '*.porcentaje' => 'required|numeric|between:0.01,100',
                '*.apoderado' => ['required', Rule::in(['true', 'false'])]
                //TODO: validar que el total de aportes entre todos los socios = 100
            ]);

            if ($sociedadAnonimaValidator->fails() || $sociosValidator->fails()) {
                $errors = $sociedadAnonimaValidator->errors()->merge($sociosValidator->errors());
                return response()->json($errors, 400);
            }

            $service->updateSociedadAnonima(
                $sociedadAnonima,
                $request->input('fecha_creacion'),
                $request->input('domicilio_legal'),
                $request->input('domicilio_real'),
                $request->input('email_apoderado'),
            );

            $service->updateSocios(
                $sociedadAnonima,
                $sociosArray,
            );

            /* Se marca la actividad como completada */
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
            $bonitaProcessHelper = new BonitaProcessHelper();
            $bonitaCaseId = $sociedadAnonima->bonita_case_id;
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "email_apoderado", "java.lang.String", $request->input('email_apoderado'));
            $bonitaTaskHelper = new BonitaTaskHelper();
            $userTasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            $bonitaTaskHelper->executeTask($jsessionid, $xBonitaAPIToken, head($userTasksResponse)["id"], true);

            return response()->json("S.A. actualizada", 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Actualizar el estatuto.
     * 
     * @OA\Post(
     *    path="/api/sociedadAnonima/{idSociedad}/estatuto",
     *    summary="Subir un nuevo archivo estatuto para la Sociedad anonima con id",
     *    description="Subir un nuevo archivo estatuto",
     *    operationId="updateEstatuto",
     *    tags={"sociedadAnonima-apoderado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\RequestBody(
     *       @OA\MediaType(
     *          mediaType="multipart/form-data",
     *          @OA\Schema(
     *             type="object", 
     *             @OA\Property(
     *                property="archivo_estatuto",
     *                type="file"
     *             ),
     *          ),
     *      )
     *    ),
     *    @OA\Parameter(
     *         name="idSociedad",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Estatuto actualizado",
     *       @OA\JsonContent(
     *          example="Estatuto actualizado"
     *       )
     *    ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="No puedes modificar el estatuto de esta S.A.",
     *       @OA\JsonContent(
     *          example="No puedes modificar el estatuto de esta S.A."
     *       )
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
     * @param  \Illuminate\Http\Request $idSociedad
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEstatuto(SociedadAnonimaService $service, Request $request, $idSociedad)
    {
        try {
            $sociedadAnonima = SociedadAnonima::find($idSociedad);

            if ($request->user()->cannot('update', $sociedadAnonima))
                return response()->json("No puedes modificar el estatuto de esta S.A.", 403);

            $validator = Validator::make($request->all(), [
                'archivo_estatuto' => 'required|mimes:docx,odt,pdf'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $service->updateEstatuto(
                $request->file('archivo_estatuto'),
                $sociedadAnonima->nombre,
            );

            /* Se marca la actividad como completada */
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
            $bonitaTaskHelper = new BonitaTaskHelper();
            $tasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $sociedadAnonima->bonita_case_id);
            $bonitaTaskHelper->executeTask($jsessionid, $xBonitaAPIToken, head($tasksResponse)["id"], true);

            return response()->json("Estatuto actualizado", 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Registrar la sociedad anonima.
     *
     * @OA\Post(
     *    path="/api/sociedadAnonima",
     *    summary="Solicitar la creación de una Sociedad Anonima",
     *    description="Solicitar la creación de una Sociedad Anonima",
     *    operationId="register",
     *    tags={"sociedadAnonima-apoderado"},
     *    security={{ "apiAuth": {} }},
     *    @OA\Response(
     *       response=200,
     *       description="Solicitud creada",
     *       @OA\JsonContent(
     *          example="Solicitud creada"
     *       )
     *    ),
     *     @OA\Response(
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
    public function register(SociedadAnonimaService $service, Request $request)
    {
        try {
            $sociedadAnonimaValidator = Validator::make($request->all(), [
                'nombre' => 'required|string|between:2,100|unique:sociedades_anonimas',
                'fecha_creacion' => 'required|date|before_or_equal:now',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
                'paises_estados' => 'required|json',
                'archivo_estatuto' => 'required|mimes:docx,odt,pdf'
            ]);

            if ($sociedadAnonimaValidator->fails()){
                $errors = $sociedadAnonimaValidator->errors();
                return response()->json($errors, 400);
            }

            $sociosArray = json_decode($request->input('socios'), true);
            $sociosValidator = Validator::make($sociosArray, [
                '*.nombre' => 'required|string|between:2,100',
                '*.apellido' => 'required|string|between:2,100',
                '*.porcentaje' => 'required|numeric|between:0.01,100',
                '*.apoderado' => ['required', Rule::in(['true', 'false'])]
                //TODO: validar que el total de aportes entre todos los socios = 100
            ]);

            $paisesEstadosArray = json_decode($request->input('paises_estados'), true);
            $paisesValidator = Validator::make($paisesEstadosArray, [
                '*.code' => 'required|string|between:1,5',
                '*.name' => 'required|string|between:2,100',
                '*.continent' => 'required|string|between:2,100',
            ]);

            if ($sociosValidator->fails() || $paisesValidator->fails()) {
                $errors = $sociosValidator->errors()->merge($paisesValidator->errors());
                return response()->json($errors, 400);
            }

            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            /* Se crea la instancia (case) del proceso en Bonita y  se asignan variables */
            $bonitaProcessHelper = new BonitaProcessHelper();
            $caseData = [
                "nombre_sociedad" => $request->input('nombre'),
                "email_apoderado" => $request->input('email_apoderado')
            ];
            $startProcessResponse = $bonitaProcessHelper->startProcessByName($jsessionid, $xBonitaAPIToken, "Registro", $caseData);
            $bonitaCaseId = $startProcessResponse->original["id"];

            /* Se marca la primera actividad como completada */
            $bonitaTaskHelper = new BonitaTaskHelper();
            $userTasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            while (empty($userTasksResponse))
                $userTasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            $bonitaTaskHelper->executeTask($jsessionid, $xBonitaAPIToken, head($userTasksResponse)["id"], true);
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", "Pendiente mesa de entradas");

            if ($startProcessResponse->status() == 200) {
                $sociedadAnonima = $service->storeNewSociedadAnonima(
                    $request->file('archivo_estatuto'),
                    $request->input('nombre'),
                    $request->input('fecha_creacion'),
                    $request->input('domicilio_legal'),
                    $request->input('domicilio_real'),
                    $request->input('email_apoderado'),
                    $bonitaCaseId,
                );

                /* Guardar socios */
                $service->storeSocios(
                    $sociedadAnonima,
                    $sociosArray,
                );

                /* Guardar países */
                $service->storePaisesEstados(
                    $sociedadAnonima,
                    $paisesEstadosArray,
                );

                return response()->json("Solicitud creada", 200);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
