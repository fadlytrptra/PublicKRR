<?php

namespace App\Http\Controllers\DokumenSJ;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DokumenSJController extends Controller
{
    // LIST DATA
    public function index()
    {
        $list = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->select('IDPengiriman', 'TglKirim', 'NamaCust')
            ->groupBy('IDPengiriman', 'TglKirim', 'NamaCust')
            ->orderBy('TglKirim', 'desc')
            ->get();

        return view('DokumenSJ.list', compact('list'));
    }

    // DETAIL DOKUMEN
    public function show($id)
    {
        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->leftJoin('T_SuratJalanOTP as otp', function ($join) {
                $join->on('otp.IdSuratJalan', '=', 'sj.IdSuratJalan')->whereNotNull('otp.ApprovedAt');
            })
            ->where('sj.IDPengiriman', $id)
            ->select([
                'sj.NamaType',
                'sj.Ket',

                DB::raw("
                    CASE
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satPrimer) THEN sj.QtyPrimer
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satSekunder) THEN sj.QtySekunder
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satTritier) THEN sj.QtyTritier
                        ELSE 0
                    END as QtyJual
                "),

                DB::raw("RTRIM(sj.SatJual) as SatJual"),

                // HEADER
                'sj.IDPengiriman',
                'sj.TglKirim',
                'sj.NamaCust',
                'sj.NamaExpeditor',
                'sj.TrukNopol',
                'sj.No_PO',
                'sj.AlamatCustomer',
                'sj.AlamatKirimDO',
                'sj.NamaSatpam',
                'sj.TglAccSatpam',
                'sj.NamaSupir',
                'sj.TglTTSupir',
                'otp.ApprovedAt as TglApp'
            ])
            ->get();

        if ($data->isEmpty()) {
            abort(404, 'Data tidak ditemukan');
        }

        $header = $data->first();

        // tanggal kirim
        $header->TglKirim = $header->TglKirim
            ? Carbon::parse($header->TglKirim)->format('d-m-Y H:i:s')
            : '-';

        // LOGIC SATPAM ATAU SUPIR (PRIORITAS SOPIR)
        $header->PengirimNama = $header->NamaSupir ?: $header->NamaSatpam;
        $header->TglPengirim  = $header->TglTTSupir ?: $header->TglAccSatpam;

        // TANGGAL SATPAM ATAU SUPIR
        $header->TglPengirim = $header->TglPengirim
            ? Carbon::parse($header->TglPengirim)->format('d-m-Y H:i:s')
            : '-';

        // TANGGAL DITERIMA CUSTOMER
        $header->TglApp = $header->TglApp
            ? Carbon::parse($header->TglApp)->format('d-m-Y H:i:s')
            : '-';

        return view('DokumenSJ.index', compact('header', 'data'));
    }

    public function create()
    {

    }

    public function store(Request $request)
    {

    }

    public function edit($id)
    {

    }

    public function update(Request $request, $id)
    {

    }

    public function destroy($id)
    {

    }
}
