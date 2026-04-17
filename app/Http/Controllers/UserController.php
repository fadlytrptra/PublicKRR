<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{

    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id)
    {
        $user = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('IdUser', $id)
            ->first();

        if (!$user) {
            return redirect('/login');
        }

        return back()->with('success', 'Profile berhasil diupdate');
    }

    public function edit($id = null)
    {
        $user = session('user');

        if (!$user) {
            return redirect('/login');
        }

        return view('profile.edit', compact('user'));
    }

    public function update(Request $request, $id = null)
    {
        $request->validate([
            'NamaUser' => 'required|string|max:255',
            'NamaPerusahaan' => 'required|string|max:255',
            'AlamatPerusahaan' => 'required|string|max:255',
            'NoHP' => 'required|string|max:20',
            'NPWP' => 'required|string|max:50',
             'Password' => [
                'nullable',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[^A-Za-z0-9]/'
            ],
        ], [
            'Password.min' => 'Password minimal 8 karakter',
            'Password.regex' => 'Password harus ada huruf besar, kecil, dan simbol',
        ]);

        $user = session('user');

        if (!$user) {
            return redirect('/login');
        }

        $currentUser = DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('IdUser', $user->IdUser)
            ->first();

        $data = [
            'NamaUser' => $request->NamaUser ?: $currentUser->NamaUser,
            'NamaPerusahaan' => $currentUser->NamaPerusahaan,
            'AlamatPerusahaan' => $currentUser->AlamatPerusahaan,
            'NoHP' => $request->NoHP ?: $currentUser->NoHP,
            'NPWP' => $request->NPWP ?: $currentUser->NPWP,
        ];

        // kalau password diisi → update
        if ($request->filled('Password')) {
            $data['Password'] = Hash::make($request->Password);
        }

        DB::connection('ConnPublic')
            ->table('UserPublic')
            ->where('IdUser', $user->IdUser)
            ->update($data);

        // update session
        foreach ($data as $key => $value) {
            $user->$key = $value;
        }

        session(['user' => $user]);

        return redirect()->route('profile.show', $user->IdUser)
            ->with('success', 'Profile berhasil diupdate');
    }

    public function destroy($id)
    {
        //
    }
}
