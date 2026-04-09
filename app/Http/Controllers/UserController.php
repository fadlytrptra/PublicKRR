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
            'NamaPerusahaan' => 'nullable|string|max:255',
            'AlamatPerusahaan' => 'nullable|string|max:255',
            'NoHP' => 'nullable|string|max:20',
            'NPWP' => 'nullable|string|max:50',
            'Password' => 'nullable|min:6',
        ]);

        $user = session('user');

        if (!$user) {
            return redirect('/login');
        }

        $data = [
            'NamaUser' => $request->NamaUser,
            'NamaPerusahaan' => $request->NamaPerusahaan,
            'AlamatPerusahaan' => $request->AlamatPerusahaan,
            'NoHP' => $request->NoHP,
            'NPWP' => $request->NPWP,
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
