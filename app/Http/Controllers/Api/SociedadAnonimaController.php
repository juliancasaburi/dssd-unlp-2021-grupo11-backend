<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\SociedadAnonima;
use App\Services\SociedadAnonimaService;
use App\Helpers\BonitaProcessHelper;
use App\Helpers\BonitaTaskHelper;
use App\Http\Resources\SociedadAnonima as SociedadAnonimaResource;
use App\Http\Resources\SociedadAnonimaCollection;
use Exception;
use App\Jobs\ProcessAprobacionSA;
use Illuminate\Support\Facades\DB;
class SociedadAnonimaController extends Controller
{
    /**
    * 
    * @param Illuminate\Support\Facades\Validator $validator
    * @param Array $sociosArray
    */
    private function validarSocios($sociosValidator, $sociosArray){
        $sociosValidator->after(function ($validator) use ($sociosArray) {
            if (collect($sociosArray)->sum('porcentaje') != 100) {
                $validator->errors()->add(
                    'socios.porcentaje', 'La suma del porcentaje de socios debe ser 100.'
                );
            }
        });
    }

    /**
     * Obtener el pdf con la información publica de la SociedadAnonima.
     *
     * @OA\Get(
     *    path="/api/sa/{numeroHash}",
     *    summary="infoPublicaSA",
     *    description="Obtener el pdf con la información publica de la SociedadAnonima.",
     *    operationId="infoPublicaSA",
     *    tags={"sociedadAnonima-publico"},
     *    @OA\Parameter(
     *         name="numeroHash",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Retorna pdf",
     *    ),
     * )
     * 
     * @return \Illuminate\Http\Response
     */
    public function infoPublicaSA(SociedadAnonimaService $service, $numeroHash)
    {
        $nombreSA = SociedadAnonima::where('numero_hash', $numeroHash)->value('nombre');
        try {
            $pdfContents = $service->getPublicPDFContents($numeroHash);
            return response($pdfContents, 200, [
                "Content-type"        => "application/pdf",
                "Content-Disposition" => "attachment; filename=info_publica_{$nombreSA}.pdf",
            ]);
        } catch (Exception $e) {
            return response()->json("No existe la Sociedad Anonima con numero de hash {$numeroHash}", 404);
        }
    }

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
        return response()->json(new SociedadAnonimaCollection($service->getUserSociedadesAnonimasWithSociosAndEstados(auth()->user())), 200);
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
        $sociedadAnonima = $service->getSociedadAnonimaWithSociosAndEstadosById($id);
        if ($request->user()->cannot('view', $sociedadAnonima))
            return response()->json("Forbidden", 403);
        else
            return response()->json(new SociedadAnonimaResource($sociedadAnonima), 200);
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
        $taskData = head(BonitaTaskHelper::tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId));

        $user = auth()->user();
        if ($taskData["assigned_id"] != $user->bonita_user_id)
            return response()->json("No tienes acceso a los datos de esta sociedad.", 403);

        $sociedadAnonima = $service->getSociedadAnonimaWithSociosAndEstadosByCaseId($bonitaCaseId);

        return response()->json(new SociedadAnonimaResource($sociedadAnonima), 200, [], JSON_UNESCAPED_SLASHES);
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

        $response = BonitaTaskHelper::taskDataById($jsessionid, $xBonitaAPIToken, $taskId);

        if ($response["state"] != "ready" or $response["assigned_id"] != auth()->user()->bonita_user_id)
            return response()->json("No puedes aprobar/rechazar esta tarea.", 403);

        // Actualizar el case de Bonita
        $bonitaCaseId = $response["caseId"];
        $sociedadAnonima = $service->getSociedadAnonimaByCaseId($bonitaCaseId);

        $user = auth()->user();
        $rol = $user->getRoleNames()->first();
        $nuevoEstadoEvaluacion = '';

        if ($request->input('aprobado') == "true") {
            $nuevoEstadoEvaluacion = "Aprobado por {$rol}";
            // Setear numero_expediente
            if ($rol == "empleado-mesa-de-entradas") {
                BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "numero_expediente", "java.lang.String", $sociedadAnonima->id);
                $sociedadAnonima->numero_expediente = $sociedadAnonima->id;
                $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
                $sociedadAnonima->save();
            } else {
                ProcessAprobacionSA::dispatch($sociedadAnonima, $user, $bonitaCaseId, $nuevoEstadoEvaluacion);
            }
        } else {
            $nuevoEstadoEvaluacion = "Rechazado por {$rol}";
            $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
            if ($rol == "empleado-mesa-de-entradas"){
                $sociedadAnonima->cantidad_rechazos_mesa_entradas += 1;
                BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "cantidad_rechazos_mesa_entradas", "java.lang.Integer", $sociedadAnonima->cantidad_rechazos_mesa_entradas);
            }
            else{
                $sociedadAnonima->cantidad_rechazos_area_legales += 1;
                BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "cantidad_rechazos_area_legales", "java.lang.Integer", $sociedadAnonima->cantidad_rechazos_area_legales);
            }
            $sociedadAnonima->save();
        }

        // estado_evaluacion
        BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", $nuevoEstadoEvaluacion);

        // Completar la tarea en Bonita
        BonitaTaskHelper::executeTask($jsessionid, $xBonitaAPIToken, $taskId);

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
                'fecha_creacion' => 'required|date|before_or_equal:now',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
                'paises_estados' => 'required|json',
            ]);

            if ($sociedadAnonimaValidator->fails()) {
                $errors = $sociedadAnonimaValidator->errors();
                return response()->json($errors, 400);
            }

            $sociosArray = json_decode($request->input('socios'), true);
            $sociosValidator = Validator::make($sociosArray, [
                '*.nombre' => 'required|string|between:2,100',
                '*.apellido' => 'required|string|between:2,100',
                '*.porcentaje' => 'required|numeric|between:0.01,100',
                '*.apoderado' => ['required', Rule::in(['true', 'false'])]
            ]);
            $this->validarSocios($sociosValidator, $sociosArray);

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

            /* Actualizar paises y estados */
            $sociedadAnonima->paises()->detach();
            $sociedadAnonima->estados()->detach();
            $service->storeEstados(
                $sociedadAnonima,
                $paisesEstadosArray,
            );

            /* Se marca la actividad como completada */
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
            $bonitaCaseId = $sociedadAnonima->bonita_case_id;
            BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "email_apoderado", "java.lang.String", $request->input('email_apoderado'));
            $userTasksResponse = BonitaTaskHelper::tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            BonitaTaskHelper::executeTask($jsessionid, $xBonitaAPIToken, head($userTasksResponse)["id"], true);

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
            // estado_evaluacion
            $bonitaCaseId = $sociedadAnonima->bonita_case_id;
            $nuevoEstadoEvaluacion = "Estatuto corregido por apoderado";
            $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
            $sociedadAnonima->save();
            BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", $nuevoEstadoEvaluacion);
            $tasksResponse = BonitaTaskHelper::tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            BonitaTaskHelper::executeTask($jsessionid, $xBonitaAPIToken, head($tasksResponse)["id"], true);

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
                'nombre' => 'required|string|between:2,100|unique:sociedades_anonimas,nombre,NULL,id,deleted_at,NULL',
                'fecha_creacion' => 'required|date|before_or_equal:now',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
                'paises_estados' => 'required|json',
                'archivo_estatuto' => 'required|mimes:docx,odt,pdf'
            ]);

            if ($sociedadAnonimaValidator->fails()) {
                $errors = $sociedadAnonimaValidator->errors();
                return response()->json($errors, 400);
            }

            $sociosArray = json_decode($request->input('socios'), true);
            $sociosValidator = Validator::make($sociosArray, [
                '*.nombre' => 'required|string|between:2,100',
                '*.apellido' => 'required|string|between:2,100',
                '*.porcentaje' => 'required|numeric|between:0.01,100',
                '*.apoderado' => ['required', Rule::in(['true', 'false'])]
            ]);
            $this->validarSocios($sociosValidator, $sociosArray);

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
            $caseData = [
                "nombre_sociedad" => $request->input('nombre'),
                "email_apoderado" => $request->input('email_apoderado')
            ];
            $startProcessResponse = BonitaProcessHelper::startProcessByName($jsessionid, $xBonitaAPIToken, "Registro", $caseData);
            $bonitaCaseId = $startProcessResponse->original["id"];

            /* Se marca la primera actividad como completada */
            $userTasksResponse = BonitaTaskHelper::tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            while (empty($userTasksResponse))
                $userTasksResponse = BonitaTaskHelper::tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            BonitaTaskHelper::executeTask($jsessionid, $xBonitaAPIToken, head($userTasksResponse)["id"], true);

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

                /* Guardar estados */
                $service->storeEstados(
                    $sociedadAnonima,
                    $paisesEstadosArray,
                );

                return response()->json("Solicitud creada", 200);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    /**
     * Expirar una S.A al finalizar el plazo de subsanación.
     *
     * @OA\Get(
     *    path="/api/expirarSA/{nombreSociedad}",
     *    summary="expirarSA",
     *    description="Expirar una S.A al finalizar el plazo de subsanación.",
     *    operationId="expirarSA",
     *    tags={"sociedadAnonima-bonita"},
     *    @OA\Parameter(
     *         name="nombreSociedad",
     *         in="path",
     *         required=true,
     *         @OA\Schema(
     *           type="string"
     *         )
     *    ),
     *    @OA\Response(
     *       response=200,
     *       description="Se actualizó el estado de la sociedad.",
     *    ),
     * )
     * 
     * @param  \Illuminate\Http\Request $request
     * @param  string $nombreSociedad
     * @return \Illuminate\Http\Response
     */
    public function expirarSA(Request $request, $nombreSociedad)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = auth('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth('api')->user();

        if (!$user->hasRole('admin')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        try {
            $sa = SociedadAnonima::where('nombre', $nombreSociedad)->first();

            $sa->estado_evaluacion = 'Plazo expirado';

            DB::table('sociedades_anonimas_estados')
             ->where('sociedad_anonima_id', $sa->id)
             ->delete();

            $sa->delete();

            return response()->json("Sociedad Actualizada.", 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json("No existe la Sociedad Anonima con nombre {$nombreSociedad}", 404);
        }
    }

    /**
     * Marcar como finalizada la tarea de creación de carpeta física.
     *
     * @OA\Post(
     *    path="/api/carpetaFisica/{taskId}",
     *    summary="Sociedad anonima",
     *    description="Marcar como finalizada la tarea de creación de carpeta física",
     *    operationId="carpetaFisicaFinalizada",
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
     *    @OA\Response(
     *       response=200,
     *       description="Tarea marcada como finalizada exitosamente.",
     *       @OA\JsonContent(
     *          example="Tarea marcada como finalizada exitosamente."
     *       )
     *    ),
     *     @OA\Response(
     *       response=401,
     *       description="Unauthorized"
     *    ),
     *    @OA\Response(
     *       response=403,
     *       description="No puedes realizar esta tarea",
     *       @OA\JsonContent(
     *          example="No puedes realizar esta tarea."
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
    public function carpetaFisicaFinalizada(Request $request, SociedadAnonimaService $service, $taskId)
    {
        $jsessionid = $request->cookie('JSESSIONID');
        $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

        $response = BonitaTaskHelper::taskDataById($jsessionid, $xBonitaAPIToken, $taskId);

        if ($response["state"] != "ready" or $response["assigned_id"] != auth()->user()->bonita_user_id)
            return response()->json("No puedes realizar esta tarea.", 403);

        $bonitaCaseId = $response["caseId"];
        $sociedadAnonima = $service->getSociedadAnonimaByCaseId($bonitaCaseId);

        // estado_evaluacion
        $nuevoEstadoEvaluacion = 'Sociedad registrada';
        BonitaProcessHelper::updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", $nuevoEstadoEvaluacion);
        $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
        $sociedadAnonima->save();
        // Completar la tarea en Bonita
        BonitaTaskHelper::executeTask($jsessionid, $xBonitaAPIToken, $taskId);

        return response()->json("Tarea marcada como finalizada exitosamente.", 200);
    }
}
