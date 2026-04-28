<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SuratJalan\SuratJalanPesananController;
use App\Http\Controllers\DokumenSJ\DokumenSJController;
use App\Http\Controllers\SuratJalan\VerifyDokumenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/


$redirectIfAuthenticated = function () {
    if (Auth::guest())
        return view('auth.login');
    else
        return redirect('/home');
};

Route::get('/', $redirectIfAuthenticated);
Route::get('/logout', $redirectIfAuthenticated);

Route::post('/refresh-csrf', function () {
    session()->regenerateToken();

    return response()->json([
        'success' => true
    ]);
});

#region Auth
//Route::get('/', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/forgot-password', [LoginController::class, 'forgotPassword']);
Route::post('/force-reset-password', [LoginController::class, 'forceResetPassword'])->name('force.reset.password');

Route::get('/register', function () {
    if (!session('showOtp')) {
        session()->forget(['register_data', 'register_otp', 'register_expired']);
    }
    return view('auth.register');
});


Route::post('/register', [LoginController::class, 'register']);
Route::post('/verify-otp', [LoginController::class, 'verifyOtp']);
#endregion

// Dokumen SJ
Route::get('/DokumenSJ/view/{id}', [DokumenSJController::class, 'show'])
    ->where('id', '.*')
    ->name('DokumenSJ.show');

// Surat Jalan
Route::get('/SuratJalan/{id}', [SuratJalanPesananController::class, 'show'])
    ->where('id', '[A-Za-z0-9%]+')
    ->name('SuratJalan.show');

Route::get('SuratJalan-data', [SuratJalanPesananController::class, 'data'])->name('SuratJalan.data');
Route::get('SuratJalan/get-emails/{id_pengiriman}', [SuratJalanPesananController::class, 'getEmails']);
Route::post('SuratJalan/send-otp', [SuratJalanPesananController::class, 'sendOtp']);
Route::get('/dokumen-sj/search', [DokumenSJController::class, 'search'])->name('DokumenSJ.search');
Route::resource('DokumenSJ', DokumenSJController::class)->except(['show']);
Route::post('SuratJalan/verify-otp', [SuratJalanPesananController::class, 'verifyOtp']);
Route::post('SuratJalan/confirm-approval', [SuratJalanPesananController::class, 'confirmApproval']);
Route::post('SuratJalan/resend-email', [SuratJalanPesananController::class, 'resendEmail']);

Route::middleware(['check.login'])->group(function () {
    Route::get('/home', function () {
        return view('home');
    })->name('home');

    Route::resource('profile', UserController::class);
    Route::get('SuratJalan/list-data', [SuratJalanPesananController::class, 'listData'])->name('SuratJalan.listData');
    Route::get('/SuratJalan/detail-modal/{id}', [SuratJalanPesananController::class, 'detailModalSJ']);
    Route::resource('SuratJalan', SuratJalanPesananController::class)->except(['show']);

});
