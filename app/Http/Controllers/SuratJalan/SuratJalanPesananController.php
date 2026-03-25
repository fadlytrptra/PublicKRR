<?php

namespace App\Http\Controllers\SuratJalan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;


class SuratJalanPesananController extends Controller
{
    public function index()
    {
        return view('SuratJalan.list');
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        //
    }

    public function show($id_pengiriman)
    {
        $row = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $id_pengiriman)
            ->first();

        if (!$row) {
            abort(404);
        }

        $otp = DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $row->IdSuratJalan)
            ->where('IsUsed', 1)
            ->latest('ApprovedAt')
            ->first();

        return view('SuratJalan.suratJalanPesanan', [
            'id_pengiriman' => $id_pengiriman,
            'no_po' => $row->No_PO,
            'otp' => $otp
        ]);
    }

    public function data(Request $request)
    {
        $no_po = $request->no_po;

        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('No_PO', $no_po)
            ->select([
                'NamaType',

                DB::raw("
                    CASE
                        WHEN RTRIM(SatJual) = RTRIM(satPrimer) THEN QtyPrimer
                        WHEN RTRIM(SatJual) = RTRIM(satSekunder) THEN QtySekunder
                        WHEN RTRIM(SatJual) = RTRIM(satTritier) THEN QtyTritier
                        ELSE 0
                    END as QtyJual
                "),

                DB::raw("RTRIM(SatJual) as SatJual"),
                'NamaCust',
                'SuratPesanan',
                'NamaExpeditor',
                'TrukNopol',
                'No_PO',
                'TglKirim'
            ])
            ->get();

        return response()->json([
            'data' => $data,
            'header' => [
                'No_PO' => $no_po,
                'TglKirim' => $data->first()->TglKirim ?? null,
            ]
        ]);
    }

    public function listData()
    {
        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->select(
                'IDPengiriman',
                'No_PO',
                'NamaCust',
                DB::raw('MIN(NamaType) as NamaType')
            )
            ->groupBy('IDPengiriman', 'No_PO', 'NamaCust')
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    public function getEmails($id_pengiriman)
    {
        $idCust = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $id_pengiriman)
            ->value('IDCust');

        if (!$idCust) {
            return response()->json([
                'error' => 'Customer tidak ditemukan untuk No PO ini'
            ], 404);
        }

        $emails = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as c')
            ->join('UserPublic as u', 'c.IdUser', '=', 'u.IdUser')
            ->where('c.IDCust', $idCust)
            ->select('u.Email', 'u.NamaUser')
            ->distinct()
            ->get();

        return response()->json($emails);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'required|email'
        ]);

        $otp = rand(100000, 999999);
        $now = Carbon::now('Asia/Jakarta');


        $idSuratJalan = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $request->id_pengiriman)
            ->value('IdSuratJalan');

        if (!$idSuratJalan) {
            return response()->json([
                'error' => 'Data pengiriman tidak ditemukan'
            ], 404);
        }
        $lastOtp = DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $idSuratJalan)
            ->where('Email', $request->email)
            ->latest('CreatedAt')
            ->first();

        if ($lastOtp && $now->diffInMinutes($lastOtp->CreatedAt) < 5) {
            return response()->json([
                'error' => 'OTP sudah dikirim, tunggu 5 menit'
            ], 400);
        }

        DB::table('T_SuratJalanOTP')->insert([
            'IdSuratJalan' => $idSuratJalan,
            'Email' => $request->email,
            'OTP' => $otp,
            'ExpiredAt' => $now->copy()->addMinutes(5),
            'CreatedAt' => $now
        ]);

        Mail::raw("Kode OTP Verifikasi Anda: $otp", function ($message) use ($request) {
            $message->to($request->email)
                ->subject('OTP Approval Surat Jalan');
        });

        return response()->json([
            'status' => 'OTP sent'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'required|email',
            'otp' => 'required'
        ]);

        $now = Carbon::now('Asia/Jakarta');

        // 🔥 FIX: mapping ke PK
        $idSuratJalan = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $request->id_pengiriman)
            ->value('IdSuratJalan');

        if (!$idSuratJalan) {
            return response()->json([
                'error' => 'Data tidak ditemukan'
            ], 404);
        }

        $otp = DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $idSuratJalan)
            ->where('Email', $request->email)
            ->where('OTP', $request->otp)
            ->where('IsUsed', 0)
            ->where('ExpiredAt', '>=', $now)
            ->first();

        if (!$otp) {
            return response()->json([
                'error' => 'OTP tidak valid atau sudah expired'
            ], 400);
        }

        DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IdSuratJalan', $idSuratJalan)
            ->update([
                'ACCCustomer' => 1
            ]);

        DB::connection('ConnPublic')
            ->table('T_SuratJalanOTP')
            ->where('Id', $otp->Id)
            ->update([
                'IsUsed' => 1,
                'ApprovedAt' => $now
            ]);

        return response()->json([
            'status' => 'Approved',
            'email' => $request->email,
            'approved_at' => $now
        ]);
    }

    public function edit($id)
    {
        //
    }

    public function update(Request $request, $id)
    {
        //
    }

    public function destroy($id)
    {
        //
    }
}
