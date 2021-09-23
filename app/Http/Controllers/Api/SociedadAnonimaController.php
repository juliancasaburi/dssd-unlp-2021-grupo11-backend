<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Helpers\BonitaProcessHelper;
use App\Models\SociedadAnonima;
use Tymon\JWTAuth\Facades\JWTAuth;

class SociedadAnonimaController extends Controller
{
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
            // TODO: validar datos de socios
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        $bonitaProcessHelper = new BonitaProcessHelper();
        $response = $bonitaProcessHelper->startProcessByName($request, "Registro");

        /* TODO: Setear variables en la instancia de bonita */

        /* TODO: almacenar el archivo del estatuto, que viene en la request */

        // TODO: guardar datos de socios
        if ($response->status() == 200) {
            $sociedadAnonima = new SociedadAnonima();
            $sociedadAnonima->nombre = $request->input('nombre');
            $sociedadAnonima->fecha_creacion = $request->input('fecha_creacion');
            $sociedadAnonima->domicilio_legal = $request->input('domicilio_legal');
            $sociedadAnonima->domicilio_real = $request->input('domicilio_real');
            $sociedadAnonima->email_apoderado = $request->input('email_apoderado');
            $sociedadAnonima->apoderado = JWTAuth::user();
            $sociedadAnonima->save();
        }

        return response()->json("Solicitud creada", 200);
    }
}
