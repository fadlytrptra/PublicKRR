<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::guest()) {
            return view('auth.login');
        } else {
            return redirect('/home');
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'Password' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where(function ($query) use ($request) {
                $query->where('Email', $request->login)
                    ->orWhere('NamaUser', $request->login);
            })
            ->first();

        if (!$user) {
            return back()->withErrors(['error' => 'User tidak ditemukan']);
        }

        if (!Hash::check($request->Password, $user->Password)) {
            return back()->withErrors(['error' => 'Password salah']);
        }

        if ($user->Deactivated) {
            return back()->withErrors(['error' => 'Akun tidak aktif']);
        }

        // cek email verification
        if (!$user->EmailVerification) {
            return redirect('/register')
                ->withErrors(['error' => 'Email belum diverifikasi'])
                ->with('showOtp', true)
                ->with('email', $user->Email);
        }

        // cek verification
        if (!$user->Verification) {
            return back()->withErrors([
                'error' => 'Data Anda sedang diverifikasi oleh admin'
            ]);
        }

        session()->regenerate();
        session(['user' => $user]);

        DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('IdUser', $user->IdUser)
            ->update([
                'LastLogin' => now()
            ]);

        return redirect('/home');
    }

    public function logout(Request $request)
    {
        session()->forget('user');
        return redirect('/');
    }

    public function register(Request $request)
    {
        $npwp = preg_replace('/[^0-9]/', '', $request->NPWP);
        $nohp = preg_replace('/[^0-9]/', '', $request->NoHP);

        $request->merge([
            'NPWP' => $npwp,
            'NoHP' => $nohp,
        ]);

        $validator = Validator::make($request->all(), [
            'Email' => 'required|email|unique:ConnPublic.UserPublic,Email',
            'NamaUser' => 'required',
            'NamaPerusahaan' => 'required',
            'AlamatPerusahaan' => 'required',
            'Password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $now = Carbon::now('Asia/Jakarta');
        $otp = rand(100000, 999999);

        // INSERT KE DATABASE
        DB::connection('ConnPublic')->table('T_RegisterOTP')->insert([
            'Email'     => $request->Email,
            'OTP'       => $otp,
            'IsUsed'    => 0,
            'ExpiredAt' => $now->copy()->addMinutes(5),
            'CreatedAt' => $now,
        ]);

        // SESSION (untuk simpan data sementara)
        session([
            'register_data' => [
                'Email' => $request->Email,
                'NamaUser' => $request->NamaUser,
                'NamaPerusahaan' => $request->NamaPerusahaan,
                'AlamatPerusahaan' => $request->AlamatPerusahaan,
                'NoHP' => $nohp,
                'NPWP' => $npwp,
                'Password' => Hash::make($request->Password),
                'raw_password' => $request->Password,
            ],
            'register_otp' => $otp,
            'register_expired' => $now->copy()->addMinutes(5),
        ]);

        // KIRIM EMAIL
        Mail::mailer('MailSales')->raw(
            "Kode OTP verifikasi akun Anda: $otp",
            function ($message) use ($request) {
                $message->to($request->Email)
                    ->from(env('MAILSALES_FROM_ADDRESS'), env('MAILSALES_FROM_NAME'))
                    ->subject('OTP Verifikasi Akun');
            }
        );

        return back()
            ->with('success', 'OTP telah dikirim ke ' . $request->Email)
            ->with('showOtp', true)
            ->with('email', $request->Email)
            ->withInput();
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required'
        ], [
            'otp.required' => 'OTP wajib diisi'
        ]);

        $data = session('register_data');

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->with('showOtp', true)
                ->with('email', $data['Email'] ?? null)
                ->withInput();
        }

        if (!$data) {
            return back()->withErrors([
                'error' => 'Session expired, silakan register ulang'
            ]);
        }

        $now = Carbon::now('Asia/Jakarta');

        // VALIDASI OTP KE DATABASE
        $otpData = DB::connection('ConnPublic')->table('T_RegisterOTP')
            ->where('Email', $data['Email'])
            ->where('OTP', $request->otp)
            ->where('IsUsed', 0)
            ->orderByDesc('CreatedAt')
            ->first();

        if (!$otpData) {
            return back()
                ->withErrors(['error' => 'OTP tidak valid'])
                ->with('showOtp', true)
                ->with('email', $data['Email'])
                ->withInput();
        }

        // CEK EXPIRED
        if ($now->gt(Carbon::parse($otpData->ExpiredAt))) {
            return back()
                ->withErrors(['error' => 'OTP sudah expired'])
                ->with('showOtp', true)
                ->with('email', $data['Email'])
                ->withInput();
        }

        // INSERT USER
        $userId = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->insertGetId([
                'Email' => $data['Email'],
                'NamaUser' => $data['NamaUser'],
                'NamaPerusahaan' => $data['NamaPerusahaan'],
                'AlamatPerusahaan' => $data['AlamatPerusahaan'],
                'NoHP' => $data['NoHP'],
                'NPWP' => $data['NPWP'],
                'Password' => $data['Password'],
                'RegistDate' => $now,
                'Deactivated' => 0,
                'Verification' => 0,
                'EmailVerification' => $now
            ]);

        // UPDATE OTP
        DB::connection('ConnPublic')->table('T_RegisterOTP')
            ->where('Id', $otpData->Id)
            ->update([
                'IsUsed' => 1
            ]);

        session()->forget(['register_data', 'register_otp', 'register_expired']);

        return redirect('/')
            ->with('success', 'Registrasi berhasil, menunggu approval admin');
    }
}
