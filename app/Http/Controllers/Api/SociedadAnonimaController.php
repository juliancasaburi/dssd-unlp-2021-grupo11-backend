<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\BonitaProcessHelper;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\SociedadAnonimaService;

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
        $sociedadAnonima = $service->getSociedadAnonimaByCaseId($bonitaCaseId);
        return response()->json($sociedadAnonima, 200);
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
                'socios' => 'required|array',
                // TODO: validar datos de cada socio
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            /* TODO: almacenar el archivo del estatuto, que viene en la request */

            $jsessionid = $request->cookie('JSESSIONID');
            $xBonitaAPIToken = $request->cookie('X-Bonita-API-Token');

            /* Se crea la instancia (case) del proceso en Bonita, se asignan variables y
            se marca la primera actividad como completada */
            $bonitaProcessHelper = new BonitaProcessHelper();
            $startProcessResponse = $bonitaProcessHelper->startProcessByName($jsessionid, $xBonitaAPIToken, "Registro");
            $bonitaCaseId = $startProcessResponse->original->caseId;
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "nombre_sociedad", "java.lang.String", $request->input('nombre'));
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "email_apoderado", "java.lang.String", $request->input('email_apoderado'));
            $userTasksResponse = $bonitaProcessHelper->tasksByCaseId($jsessionid, $xBonitaAPIToken, $bonitaCaseId);
            $updateTaskDataArray = [
                "assigned_id" => JWTAuth::user()->bonita_user_id,
                "state" => "completed",
            ];
            $bonitaProcessHelper->updateTask($jsessionid, $xBonitaAPIToken, $userTasksResponse[0]["id"], $updateTaskDataArray);
            $bonitaProcessHelper->updateCaseVariable($jsessionid, $xBonitaAPIToken, $bonitaCaseId, "estado_evaluacion", "java.lang.String", "Pendiente mesa de entradas");


            if ($startProcessResponse->status() == 200) {
                $sociedadAnonima = $service->storeNewSociedadAnonima(
                    $request->input('nombre'),
                    $request->input('fecha_creacion'),
                    $request->input('domicilio_legal'),
                    $request->input('domicilio_real'),
                    $request->input('email_apoderado'),
                    $bonitaCaseId,
                );

                // Guardar socios
                $sociedadAnonima = $service->storeSocios(
                    $sociedadAnonima,
                    $request->input('socios'),
                );

                return response()->json("Solicitud creada", 200);
            }
        } catch (\Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
