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
    return view('home');
    // abort(403);
});

Route::get('SuratJalan-data', [App\Http\Controllers\SuratJalan\SuratJalanPesananController::class, 'data'])->name('SuratJalan.data');
Route::get('SuratJalan/list-data', [App\Http\Controllers\SuratJalan\SuratJalanPesananController::class, 'listData'])->name('SuratJalan.listData');
Route::get('SuratJalan/get-emails/{id_pengiriman}', [App\Http\Controllers\SuratJalan\SuratJalanPesananController::class, 'getEmails']);
Route::post('SuratJalan/send-otp', [App\Http\Controllers\SuratJalan\SuratJalanPesananController::class, 'sendOtp']);
Route::post('SuratJalan/verify-otp', [App\Http\Controllers\SuratJalan\SuratJalanPesananController::class, 'verifyOtp']);
Route::resource('SuratJalan', App\Http\Controllers\SuratJalan\SuratJalanPesananController::class);

Route::resource('DokumenSJ', App\Http\Controllers\DokumenSJ\DokumenSJController::class);
Route::resource('Dokumen', App\Http\Controllers\SuratJalan\VerifyDokumenController::class);
