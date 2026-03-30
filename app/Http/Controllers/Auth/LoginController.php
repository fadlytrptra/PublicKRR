<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

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
        $data = [
            'login' => 'required',
            'Password' => 'required'
        ];

        $validator = Validator::make($request->all(), $data);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $currentTime = Carbon::now('Asia/Bangkok');

        // cek user (email atau namauser)
        $user = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where(function ($query) use ($request) {
                $query->where('Email', $request->login)
                    ->orWhere('NamaUser', $request->login);
            })
            ->first();

        if (!$user) {
            return back()->withErrors([
                'error' => 'User tidak ditemukan'
            ]);
        }

        // cek password
        if (!Hash::check($request->Password, $user->Password)) {
            return back()->withErrors([
                'error' => 'Password salah'
            ]);
        }

        if ($user->Deactivated) {
            return back()->withErrors([
                'error' => 'Akun tidak aktif'
            ]);
        }

        session(['user' => $user]);

        DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('IdUser', $user->IdUser)
            ->update([
                'LastLogin' => $currentTime
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
        $data = [
            'Email' => 'required|email',
            'NamaUser' => 'required',
            'NamaPerusahaan' => 'required',
            'AlamatPerusahaan' => 'required',
            'NoHP' => 'required',
            // 'TTCustomer' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'NPWP' => 'required',
            'Password' => 'required|min:6'
        ];

        $validator = Validator::make($request->all(), $data);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $exists = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('Email', $request->Email)
            ->exists();

        if ($exists) {
            return back()->withErrors(['Email' => 'Email sudah terdaftar']);
        }

        // // upload file
        // $file = $request->file('TTCustomer');
        // // convert ke base64
        // $base64 = base64_encode(file_get_contents($file));
        // // ambil mime type (penting biar bisa ditampilkan)
        // $mime = $file->getMimeType();
        // // format final base64 (data:image/png;base64,...)
        // $base64Image = 'data:' . $mime . ';base64,' . $base64;


        DB::connection('ConnPublic')->table('UserPublic')->insert([
            'Email' => $request->Email,
            'NamaUser' => $request->NamaUser,
            'NamaPerusahaan' => $request->NamaPerusahaan,
            'AlamatPerusahaan' => $request->AlamatPerusahaan,
            'NoHP' => $request->NoHP,
            // 'TTCustomer' => $base64Image,
            'NPWP' => $request->NPWP,
            'Password' => Hash::make($request->Password),
            'RegistDate' => Carbon::now('Asia/Jakarta'),
            'Deactivated' => 0
        ]);

        return redirect('/')->with('success', 'Registrasi berhasil');
    }
}
