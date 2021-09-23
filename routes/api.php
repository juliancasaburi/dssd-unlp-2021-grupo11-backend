<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProcessController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Login & Register
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// JWT protected routes
Route::group(['middleware' => ['apiJwt']], function(){
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('users', [UserController::class, 'index']);

    /* Processes */
    Route::get('processes', [ProcessController::class, 'index']);
    Route::get('process/{name}', [ProcessController::class, 'processByName']);
    Route::get('processRegistroSociedadAnonima', [ProcessController::class, 'processRegistroSociedadAnonima']);
    Route::post('process/{name}', [ProcessController::class, 'startProcessByName']); // To be deleted

    /* Sociedad Anonima */
    Route::post('sociedadAnonima', [SociedadAnonimaController::class, 'register']);
});

