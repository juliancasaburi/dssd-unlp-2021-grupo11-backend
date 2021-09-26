<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\BonitaProcessHelper;
use App\Models\SociedadAnonima;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\SociedadAnonimaService;

class SociedadAnonimaController extends Controller
{
    /**
     * Obtener las sociedad anónimas registradas por el usuario actual.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserSociedadesAnonimas()
    {
        $sociedadesAnonimasUsuarioLogueado = SociedadAnonima::where('created_by', JWTAuth::user()->id);
        return response()->json($sociedadesAnonimasUsuarioLogueado, 200);
    }

    /**
     * Registrar la sociedad anonima.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
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
                return response()->json($validator->errors()->toJson(), 400);
            }

            /* TODO: almacenar el archivo del estatuto, que viene en la request */

            /* Se crea la instancia (case) del proceso en Bonita, se asignan variables y
            se marca la primera actividad como completada */
            $bonitaProcessHelper = new BonitaProcessHelper();
            $startProcessResponse = $bonitaProcessHelper->startProcessByName($request, "Registro");
            $bonitaCaseId = $startProcessResponse->original->caseId;
            $bonitaProcessHelper->updateCaseVariable($request, $bonitaCaseId, "nombre_sociedad", "java.lang.String", $request->input('nombre'));
            $userTasksResponse = $bonitaProcessHelper->tasksByCaseId($request, $bonitaCaseId);
            $updateTaskDataArray = [
                "assigned_id" => JWTAuth::user()->bonita_user_id,
                "state" => "completed",
            ];
            $bonitaProcessHelper->updateTask($request, $userTasksResponse[0]["id"], $updateTaskDataArray);
            $bonitaProcessHelper->updateCaseVariable($request, $bonitaCaseId, "estado_evaluacion", "java.lang.String", "Pendiente mesa de entradas");


            if ($startProcessResponse->status() == 200) {
                $sociedadAnonimaService = new SociedadAnonimaService();
                $sociedadAnonima = $sociedadAnonimaService->storeNewSociedadAnonima(
                    $request->input('nombre'),
                    $request->input('fecha_creacion'),
                    $request->input('domicilio_legal'),
                    $request->input('domicilio_real'),
                    $request->input('email_apoderado'),
                    $bonitaCaseId,
                );

                // Guardar socios
                $sociedadAnonima = $sociedadAnonimaService->storeSocios(
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
