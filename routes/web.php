<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    // return view('home');
    abort(403);
});
Route::resource('SuratJalan', App\Http\Controllers\SuratJalan\SuratJalanPesananController::class);
Route::resource('Dokumen', App\Http\Controllers\SuratJalan\VerifyDokumenController::class);
