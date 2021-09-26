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

        /* TODO: setear variables de la instancia (case) de Bonita */
        $bonitaProcessHelper = new BonitaProcessHelper();
        $response = $bonitaProcessHelper->startProcessByName($request, "Registro");

        if ($response->status() == 200) {
            $sociedadAnonimaService = new SociedadAnonimaService();
            $sociedadAnonima = $sociedadAnonimaService->storeNewSociedadAnonima(
                $request->input('nombre'),
                $request->input('fecha_creacion'),
                $request->input('domicilio_legal'),
                $request->input('domicilio_real'),
                $request->input('email_apoderado'),
            );

            // Guardar socios
            $sociedadAnonima = $sociedadAnonimaService->storeSocios(
                $sociedadAnonima,
                $request->input('socios'),
            );

        }

        return response()->json("Solicitud creada", 200);
    }
}
