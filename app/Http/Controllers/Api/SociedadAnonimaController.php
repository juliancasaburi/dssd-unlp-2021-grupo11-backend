<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\BonitaProcessHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\SociedadAnonimaService;
use App\Helpers\BonitaTaskHelper;

class SociedadAnonimaController extends Controller
{
    /**
     * Obtener las sociedad anónimas registradas por el usuario actual.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSociedadesAnonimas(SociedadAnonimaService $service)
    {
        $sociedadesAnonimasUsuarioLogueado = $service->getUserSociedadesAnonimasWithSocios(auth()->user());
        return response()->json($sociedadesAnonimasUsuarioLogueado, 200);
    }

    /**
     * Obtener la sociedad anónima con bonitaCaseId.
     *
     * @param int $bonitaCaseId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSociedadAnonimaByCaseId(SociedadAnonimaService $service, $bonitaCaseId)
    {
        $sociedadAnonima = $service->getSociedadAnonimaWithSociosByCaseId($bonitaCaseId);
        $sociedadAnonima["url_carpeta_estatuto"] = $service->getPrivateFolderUrl($sociedadAnonima->nombre);
        return response()->json($sociedadAnonima, 200);
    }

    /**
     * Aprobar/Rechazar.
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

        // Completar la tarea en Bonita
        $updateTaskDataArray = [
            "state" => "completed",
        ];
        $bonitaTaskHelper->updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);

        // Actualizar el case de Bonita
        $bonitaCaseId = $response["caseId"];
        $sociedadAnonima = $service->getSociedadAnonimaByCaseId($bonitaCaseId);

        $aprobado = $request->input('aprobado');
        $rol = auth()->user()->getRoleNames()->first();
        $nuevoEstadoEvaluacion = '';
        $bonitaProcessHelper = new BonitaProcessHelper();

        if ($aprobado) {
            $nuevoEstadoEvaluacion = "Aprobado por {$rol}";

            // Setear numero_expediente
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "numero_expediente", "java.lang.String", $sociedadAnonima->id);

        } else {
            $nuevoEstadoEvaluacion = "Rechazado por {$rol}";
        }

        // estado_evaluacion
        $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", $nuevoEstadoEvaluacion);
        $bonitaTaskHelper->updateTask($jsessionid, $xBonitaAPIToken, $taskId, $updateTaskDataArray);

        // Actualizar la SociedadAnonima
        $sociedadAnonima->estado_evaluacion = $nuevoEstadoEvaluacion;
        $sociedadAnonima->save();

        return response()->json("Tarea aprobada/rechazada", 200);
    }

    /**
     * Registrar la sociedad anonima.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(SociedadAnonimaService $service, Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|between:2,100|unique:sociedades_anonimas',
                'fecha_creacion' => 'required|date|',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
                'archivo_estatuto' => 'mimes:pdf,doc,docx'
                // TODO: validar datos de cada socio
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            /* Se crea la instancia (case) del proceso en Bonita y  se asignan variables */
            $bonitaProcessHelper = new BonitaProcessHelper();
            $startProcessResponse = $bonitaProcessHelper->startProcessByName($jsessionid, $xBonitaAPIToken, "Registro");
            $bonitaCaseId = $startProcessResponse->original->caseId;
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "nombre_sociedad", "java.lang.String", $request->input('nombre'));
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "email_apoderado", "java.lang.String", $request->input('email_apoderado'));
            
            /* Se marca la primera actividad como completada */
            $bonitaTaskHelper = new BonitaTaskHelper();
            $userTasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            $updateTaskDataArray = [
                "assigned_id" => JWTAuth::user()->bonita_user_id,
                "state" => "completed",
            ];
            $bonitaTaskHelper->updateTask($jsessionid, $xBonitaAPIToken, $userTasksResponse[0]["id"], $updateTaskDataArray);
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
                $sociedadAnonima = $service->storeSocios(
                    $sociedadAnonima,
                    json_decode($request->input('socios'), true),
                );

                return response()->json("Solicitud creada", 200);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
