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

// Login & Register
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/register', [AuthController::class, 'register']);

// JWT protected routes
Route::group(['middleware' => ['apiJwt']], function () {
    Route::get('users', [UserController::class, 'index']);
});

/* JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute']], function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('process', [ProcessController::class, 'index']);
    Route::get('process/{name}', [ProcessController::class, 'processByName']);
    Route::get('processRegistroSociedadAnonima', [ProcessController::class, 'processRegistroSociedadAnonima']);
    Route::post('process/{name}', [ProcessController::class, 'startProcessByName']); // To be deleted
});

/* Apoderado, JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute', 'apoderadoOnlyRoute']], function () {
    /* Sociedad Anonima */
    Route::get('sociedadesAnonimas', [SociedadAnonimaController::class, 'getUserSociedadesAnonimas']);
    Route::post('sociedadAnonima', [SociedadAnonimaController::class, 'register']);
});

/* Employee, JWT & Bonita token protected routes */
Route::group(['middleware' => ['apiJwt', 'bonitaProtectedRoute', 'employeeOnlyRoute']], function () {
    Route::get('nextEmployeeTask', [TaskController::class, 'nextTask']);
    Route::get('availableEmployeeTasks', [TaskController::class, 'availableTasks']);
    Route::post('assignTask/{id}', [TaskController::class, 'assignTask']);
    Route::get('sociedadAnonimaByCaseId/{id}', [SociedadAnonimaController::class, 'getSociedadAnonimaByCaseId']);
});
