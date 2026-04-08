<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuratJalan\SuratJalanPesananController;
use App\Http\Controllers\DokumenSJ\DokumenSJController;
use App\Http\Controllers\SuratJalan\VerifyDokumenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

#region Auth
Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', function () {
    if (!session('showOtp')) {
        session()->forget(['register_data', 'register_otp', 'register_expired']);
    }
    return view('auth.register');
});

Route::post('/register', [LoginController::class, 'register']);
Route::post('/verify-otp', [LoginController::class, 'verifyOtp']);
#endregion

Route::get('SuratJalan-data', [SuratJalanPesananController::class, 'data'])->name('SuratJalan.data');
Route::get('SuratJalan/list-data', [SuratJalanPesananController::class, 'listData'])->name('SuratJalan.listData');
Route::get('/SuratJalan/detail-modal/{id}', [SuratJalanPesananController::class, 'detailModalSJ']);
Route::get('SuratJalan/get-emails/{id_pengiriman}', [SuratJalanPesananController::class, 'getEmails']);
Route::post('SuratJalan/send-otp', [SuratJalanPesananController::class, 'sendOtp']);
Route::post('SuratJalan/verify-otp', [SuratJalanPesananController::class, 'verifyOtp']);
Route::post('SuratJalan/resend-email', [SuratJalanPesananController::class, 'resendEmail']);
Route::resource('SuratJalan', SuratJalanPesananController::class);

Route::resource('DokumenSJ', DokumenSJController::class);

Route::middleware(['check.login'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

});
