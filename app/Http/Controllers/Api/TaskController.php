<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;
use App\Helpers\BonitaTaskHelper;
use App\Models\SociedadAnonima;

class TaskController extends Controller
{
    /**
     * Próxima tarea a realizar por el usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function nextTask(Request $request)
    {
        try {
            $bonitaTaskHelper = new BonitaTaskHelper();
            $response = $bonitaTaskHelper->nextTask($request, auth()->user()->getRoleNames());

            $responseData = $response[0];
            $sociedad = SociedadAnonima::with(['apoderado', 'socios'])->where('bonita_case_id', $responseData["caseId"])->first();
            $responseData["datosSociedad"] = $sociedad;

            return response()->json($responseData, 200);
        } catch (ConnectionException $e) {
            return response()->json("500 Internal Server Error", 500);
        }
    }
}
