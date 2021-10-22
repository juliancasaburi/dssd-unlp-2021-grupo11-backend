<?php

use Illuminate\Support\Facades\Route;
use App\Mail\WelcomeMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Web\SociedadAnonimaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);
Route::get('/', [App\Http\Controllers\Web\HomeController::class, 'index'])->name('web.index');

/*Email example */
Route::get('/emailTest', function(){
    $email = new WelcomeMailable;
    Mail::to('someEmailAddress@domain')->send($email);
});

/* Info pública S.A */
Route::get('/sa/{numeroHash}', [SociedadAnonimaController::class, 'infoPublicaSA']);