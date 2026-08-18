<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Mail\ResetPasswordMail;
use App\Mail\OTPMail;

class LoginController extends Controller
{
    public function index()
    {
        if (Auth::guest()) {
            return view('auth.login');
        } else {
            return redirect()->route('SuratJalan.index');
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required',
            'Password' => 'required'
        ]);

        if ($validator->fails()) {
            return back()->withErrors([
                'error' => 'User / Password tidak ditemukan'
            ])->withInput();
        }

        $user = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where(function ($query) use ($request) {
                $query->where('Email', $request->login);
            })
            ->first();

        if (!$user) {
            return back()->withErrors(['error' => 'User / Password tidak ditemukan']);
        }

        if (!Hash::check($request->Password, $user->Password)) {
            return back()->withErrors(['error' => 'User / Password tidak ditemukan']);
        }

        if ($user->Deactivated) {
            return back()->withErrors(['error' => 'Akun tidak aktif']);
        }

        // cek email verification
        if (!$user->AccountVerification) {
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

        // return redirect('/home')->with('ForgetPassword', $user->ForgetPassword);
        session([
            'user' => $user,
            'ForgetPassword' => $user->ForgetPassword
        ]);

        return redirect()->route('SuratJalan.index');
    }

    public function logout(Request $request)
    {
        session()->forget('user');
        return redirect('/');
    }

    public function sessionexpired(Request $request)
    {
        $previousPath = parse_url(url()->previous(), PHP_URL_PATH);
        // dd([
        //     'current_url' => $request->fullUrl(),
        //     'previous_url' => url()->previous(),
        //     'home_url' => url('/'),
        //     'referer' => $request->headers->get('referer'),
        //     'all_headers' => $request->headers->all(),
        //     url()->previous() == url('/'),
        //     'previous_path' => $previousPath
        // ]);
        session()->forget('user');

        if ($previousPath == '/' || $previousPath == '') {
            return redirect('/');
        } else {
            return redirect('/')->withErrors(['error' => 'Session Expired, Please Login Again!']);
        }
    }

    public function register(Request $request)
    {
        $npwp = preg_replace('/[^0-9]/', '', $request->NPWP);
        $kodeNegara = preg_replace('/[^0-9]/', '', $request->kodeNegara);
        $nomor = preg_replace('/[^0-9]/', '', $request->NoHP);

        $nomor = ltrim($nomor, '0');
        $nohp = $kodeNegara . $nomor;

        $request->merge([
            'NPWP' => $npwp,
            'NoHP' => $nohp,
        ]);

        $validator = Validator::make(
            $request->all(),
            [
                'Email' => ['required', 'email', 'unique:ConnPublic.UserPublic,Email'],
                'NamaUser' => ['required'],
                'NamaPerusahaan' => ['required'],
                'AlamatPerusahaan' => ['required'],
                'kodeNegara' => ['required', 'in:62,60,65,66,84,63,673,1,44,81,82,86,91,61'],
                'NoHP' => ['required', 'regex:/^[0-9]{10,15}$/'],
                'otp_method' => ['required', 'in:email,sms'],
                'Password' => [
                    'required',
                    'min:8',
                    'regex:/[A-Z]/',
                    'regex:/[a-z]/',
                    'regex:/[^A-Za-z0-9]/'
                ]
            ],
            [
                'Password.*' =>
                    'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan spesial karakter (Cth: !, @, #, $, %, &, *)',

                'NoHP.regex' =>
                    'Nomor HP harus menggunakan format internasional yang valid'
            ]
        );


        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $now = Carbon::now('Asia/Jakarta');

        $existingOtp = DB::connection('ConnPublic')
            ->table('T_RegisterOTP')
            ->where('IsUsed', 0)
            ->where(function ($q) use ($request, $nohp) {

                $q->where(
                    'Email',
                    $request->Email
                )
                ->orWhere(
                    'Phone',
                    $nohp
                );

            })
            ->orderByDesc('CreatedAt')
            ->first();


        if ($existingOtp) {
            $expiredAt = Carbon::parse(
                $existingOtp->ExpiredAt,
                'Asia/Jakarta'
            );

            if ($now->lt($expiredAt)) {
                return back()
                    ->withErrors([
                        'error' =>
                            'OTP sudah dikirim. Silakan tunggu 5 menit sebelum request ulang.'
                    ])
                    ->withInput()
                    ->with('showOtp', true);
            }
        }
        $otp = rand(
            100000,
            999999
        );

        DB::connection('ConnPublic')
            ->table('T_RegisterOTP')
            ->where('IsUsed', 0)
            ->where(function ($q) use ($request, $nohp) {
                $q->where(
                    'Email',
                    $request->Email
                )
                ->orWhere(
                    'Phone',
                    $nohp
                );

            })
            ->update([
                'IsUsed' => 1
            ]);

        DB::connection('ConnPublic')
            ->table('T_RegisterOTP')
            ->insert([
                'Email' => $request->Email,
                'OTP' => $otp,
                'IsUsed' => 0,
                'ExpiredAt' => $now->copy()->addMinutes(5),
                'CreatedAt' => $now,
                'Phone' => $nohp,
            ]);


        session([
            'register_data' => [
                'Email' => $request->Email,
                'NamaUser' => $request->NamaUser,
                'NamaPerusahaan' => $request->NamaPerusahaan,
                'AlamatPerusahaan' => $request->AlamatPerusahaan,
                'NoHP' => $nohp,
                'NPWP' => $npwp,
                'Password' => Hash::make($request->Password),
            ],

            'register_otp' => $otp,
            'register_expired' => $now->copy()->addMinutes(5),
        ]);


        $message =
            "Kode OTP Verifikasi akun Anda: {$otp}\n\n" .
            "OTP berlaku selama 5 menit.";


        // =========================================
        // KIRIM OTP VIA EMAIL
        // =========================================
        if ($request->otp_method === 'email') {

            Mail::mailer('MailNoReply')->raw(
                "Kode OTP verifikasi akun Anda: $otp",

                function ($message) use ($request) {

                    $message->to(
                        $request->Email
                    )
                    ->from(
                        env('MAILNOREPLY_FROM_ADDRESS'),
                        env('MAILNOREPLY_FROM_NAME')
                    )
                    ->subject(
                        'OTP Verifikasi Akun'
                    );
                }
            );


            Mail::mailer('MailNoReply')
                ->to($request->Email)
                ->send(
                    new OTPMail(
                        $request->NamaUser,
                        $otp,
                        'Registrasi User'
                    )
                );


            $destination =
                $request->Email;

            $method =
                'Email';
        }


        // =========================================
        // KIRIM OTP VIA SMS
        // =========================================
        else {

            $response = Http::withHeaders([

                'Authorization' =>
                    'App ' . env('SMSVIRO_API_KEY'),

                'Content-Type' =>
                    'application/json',

            ])->post(
                'https://api.smsviro.com/restapi/sms/1/text/single',
                [

                    'from' =>
                        env('SMSVIRO_SENDER_ID'),

                    // NOMOR LENGKAP
                    // CONTOH:
                    // 6281234567890
                    'to' =>
                        $nohp,

                    'text' =>
                        $message
                ]
            );


            $dataResponse = $response->json();
            $allowedStatus = [
                'PENDING',
                'ACCEPTED',
                'DELIVERED'
            ];


            if (
                !$response->successful()
                ||
                !isset(
                    $dataResponse['messages'][0]['status']['groupName']
                )
                ||
                !in_array(
                    $dataResponse['messages'][0]['status']['groupName'],
                    $allowedStatus
                )
            ) {

                \Log::error(
                    $response->body()
                );


                return back()
                    ->withErrors([
                        'error' =>
                            'Gagal mengirim OTP SMS'
                    ])
                    ->withInput();
            }


            $destination =
                $nohp;

            $method =
                'SMS';
        }


        // =========================================
        // RETURN
        // =========================================
        return back()
            ->with(
                'success',
                "OTP telah dikirim melalui {$method} ke {$destination}"
            )
            ->with(
                'showOtp',
                true
            )
            ->with(
                'email',
                $request->Email
            )
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
                'AccountVerification' => $now
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

    public function forgotPassword(Request $request)
    {
        $request->validate([
            'login' => 'required'
        ]);

        $email = $request->login;

        $user = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where(function ($query) use ($email) {
                $query->where('Email', $email);
            })
            ->first();

        if (!$user) {
            // return back()->withErrors([
            //     'forgot_error' => 'Email / Username tidak ditemukan'
            // ]);
            return back()->with('success', 'Link untuk reset password telah dikirim ke email Anda. Silahkan cek inbox email Anda terlebih dahulu, jika tidak ditemukan silahkan cek spam atau junk email Anda.');
        }

        $now = Carbon::now('Asia/Jakarta');
        $otp = rand(100000, 999999);
        $key = env('QR_SHARED_SECRET');
        $cipher = 'AES-256-CBC';
        $encrypter = new Encrypter($key, $cipher);

        $otpEncrypted = urlencode(
            $encrypter->encryptString((string) $otp)
        );
        // dd($email);
        // INSERT KE DATABASE
        DB::connection('ConnPublic')->table('T_ForgotPasswordOTP')->insert([
            'Email' => $email,
            'OTP' => $otp,
            'IsUsed' => 0,
            'ExpiredAt' => $now->copy()->addMinutes(10),
            'CreatedAt' => $now,
            'Phone' => NULL,
        ]);

        // Generate password baru
        // $newPassword = $this->generateStrongPassword();

        // Hash password
        // $hashedPassword = Hash::make($newPassword);

        // Update ke DB
        // DB::connection('ConnPublic')
        //     ->table('UserPublic')
        //     ->where('IdUser', $user->IdUser)
        //     ->update([
        //         'ForgetPassword' => true
        //     ]);

        $link = url('resetPassword?email=' . $email . '&param=' . $otpEncrypted);
        Mail::mailer('MailNoReply')
            ->to($user->Email)
            ->send(new ResetPasswordMail($user, $link));
        return back()->with('success', 'Link untuk reset password telah dikirim ke email Anda. Silahkan cek inbox email Anda terlebih dahulu, jika tidak ditemukan silahkan cek spam atau junk email Anda.');
    }

    public function forceResetPassword(Request $request)
    {
        $request->validate([
            'email',
            'param',
            'password' => [
                'required',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[^A-Za-z0-9]/'
            ]
        ], [
            'password.*' => 'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan spesial karakter'
        ]);

        $email = $request->email;
        $otpEncrypted = $request->param;
        $key = env('QR_SHARED_SECRET');
        $cipher = 'AES-256-CBC';

        $encrypter = new Encrypter($key, $cipher);
        $otpDecrypted = $encrypter->decryptString(
            urldecode($otpEncrypted)
        );
        // VALIDASI OTP KE DATABASE
        $otpData = DB::connection('ConnPublic')->table('T_ForgotPasswordOTP')
            ->where('Email', $email)
            ->where('OTP', $otpDecrypted)
            ->where('IsUsed', 0)
            ->where('ExpiredAt', '>', now())
            ->orderByDesc('CreatedAt')
            ->first();

        if (!$otpData) {
            abort(410);
        }

        if (!$email) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('Email', $email)
            ->update([
                'Password' => Hash::make($request->password),
                'ForgetPassword' => false
            ]);

        // UPDATE OTP
        DB::connection('ConnPublic')->table('T_ForgotPasswordOTP')
            ->where('Id', $otpData->Id)
            ->update([
                'IsUsed' => 1
            ]);

        return redirect('/')->with('success', 'Reset Password berhasil!');
    }

    public function resetPassword(Request $request)
    {
        $email = $request->email;
        $otpEncrypted = $request->param;
        $key = env('QR_SHARED_SECRET');
        $cipher = 'AES-256-CBC';

        $encrypter = new Encrypter($key, $cipher);
        $otpDecrypted = $encrypter->decryptString(
            urldecode($otpEncrypted)
        );
        // VALIDASI OTP KE DATABASE
        $otpData = DB::connection('ConnPublic')->table('T_ForgotPasswordOTP')
            ->where('Email', $email)
            ->where('OTP', $otpDecrypted)
            ->where('IsUsed', 0)
            ->where('ExpiredAt', '>', now())
            ->orderByDesc('CreatedAt')
            ->first();

        if (!$otpData) {
            abort(410);
        }

        $expiredAt = Carbon::parse($otpData->ExpiredAt);

        if (now()->greaterThan($expiredAt)) {
            abort(410);
        }

        return view('auth.resetPassword', compact('otpEncrypted', 'email'));
    }

    public function generateStrongPassword($length = 10)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*()_+=-';

        // pastikan minimal 1 dari masing-masing
        $password = [
            $uppercase[rand(0, strlen($uppercase) - 1)],
            $lowercase[rand(0, strlen($lowercase) - 1)],
            $symbols[rand(0, strlen($symbols) - 1)],
            $numbers[rand(0, strlen($numbers) - 1)],
        ];

        $all = $uppercase . $lowercase . $numbers . $symbols;

        for ($i = 4; $i < $length; $i++) {
            $password[] = $all[rand(0, strlen($all) - 1)];
        }

        return str_shuffle(implode('', $password));
    }
}
