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

class SociedadAnonimaController extends Controller
{
    /**
     * Obtener las sociedad anónimas registradas por el usuario actual.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSociedadesAnonimas(SociedadAnonimaService $service)
    {
        return response()->json($service->getUserSociedadesAnonimasWithSocios(auth()->user()), 200);
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
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Http\Request $idSociedad
     * @return \Illuminate\Http\JsonResponse
     */
    public function patchSociedadAnonima(SociedadAnonimaService $service, Request $request, $idSociedad)
    {
        try {
            $sociedadAnonima = SociedadAnonima::find($idSociedad);

            if ($sociedadAnonima->estado_evaluacion != 'Rechazado por empleado-mesa-de-entradas')
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
     * @param  \Illuminate\Http\Request $request
     * @param  \Illuminate\Http\Request $idSociedad
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateEstatuto(SociedadAnonimaService $service, Request $request, $idSociedad)
    {
        try {
            $sociedadAnonima = SociedadAnonima::find($idSociedad);

            if ($sociedadAnonima->estado_evaluacion != 'Rechazado por escribano-area-legales')
                return response()->json("No puedes modificar el estatuto de esta S.A.", 403);

            $validator = Validator::make($request->all(), [
                'archivo_estatuto' => 'mimes:docx,odt,pdf'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $service->updateEstatuto(
                $request->file('archivo_estatuto'),
                $idSociedad,
            );

            /* Se marca la actividad como completada */
            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');
            $bonitaTaskHelper = new BonitaTaskHelper();
            $userTasksResponse = $bonitaTaskHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $sociedadAnonima->bonita_case_id);
            $bonitaTaskHelper->executeTask($jsessionid, $xBonitaAPIToken, head($userTasksResponse)["id"], true);

            return response()->json("Estatuto actualizado", 200);
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
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
            $sociedadAnonimaValidator = Validator::make($request->all(), [
                'nombre' => 'required|string|between:2,100|unique:sociedades_anonimas',
                'fecha_creacion' => 'required|date|before_or_equal:now',
                'domicilio_legal' => 'required|string|between:2,100',
                'domicilio_real' => 'required|string|between:2,100',
                'email_apoderado' => 'required|string|email',
                'socios' => 'required|json',
                'archivo_estatuto' => 'mimes:docx,odt,pdf'
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
                $sociedadAnonima = $service->storeSocios(
                    $sociedadAnonima,
                    $sociosArray,
                );

                return response()->json("Solicitud creada", 200);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
