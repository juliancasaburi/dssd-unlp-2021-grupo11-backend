<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProcessController;
use App\Http\Controllers\Api\SociedadAnonimaController;
use App\Http\Controllers\Api\TaskController;

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

/* Auth */
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

/* JWT protected routes */
Route::group(['middleware' => ['apiJwt']], function () {
    Route::get('users', [UserController::class, 'index']);
});

/* JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute']], function () {
    /* Auth */
    Route::post('auth/logout', [AuthController::class, 'logout']);
});

/* Apoderado, JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute', 'apoderadoOnlyRoute']], function () {
    /* Sociedad Anonima */
    Route::get('sociedadesAnonimas', [SociedadAnonimaController::class, 'getUserSociedadesAnonimas']);
    Route::get('sociedadAnonima/{id}', [SociedadAnonimaController::class, 'getUserSociedadAnonima']);
    Route::post('sociedadAnonima', [SociedadAnonimaController::class, 'register']);
    Route::patch('sociedadAnonima', [SociedadAnonimaController::class, 'patchSociedadAnonima']);
    Route::put('estatuto', [SociedadAnonimaController::class, 'updateEstatuto']);
});

/* Employee, JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute', 'employeeOnlyRoute']], function () {
    Route::get('nextEmployeeTask', [TaskController::class, 'nextTask']);
    Route::get('availableEmployeeTasks', [TaskController::class, 'availableTasks']);
    Route::get('employeeTasks', [TaskController::class, 'userTasks']);
    Route::get('employeeTask/{id}', [TaskController::class, 'getTaskSociedadDataById']);
    Route::post('assignTask/{id}', [TaskController::class, 'assignTask']);
    Route::post('unassignTask/{id}', [TaskController::class, 'unassignTask']);
    Route::get('sociedadAnonimaByCaseId/{id}', [SociedadAnonimaController::class, 'getSociedadAnonimaByCaseId']);
    Route::post('updateSociedadAnonimaStatus/{id}', [SociedadAnonimaController::class, 'updateSociedadAnonimaStatus']);
});
