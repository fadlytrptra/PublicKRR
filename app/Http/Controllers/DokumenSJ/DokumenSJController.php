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
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $id)
            ->select([
                'NamaType',
                'Ket',

                DB::raw("
                    CASE
                        WHEN RTRIM(SatJual) = RTRIM(satPrimer) THEN QtyPrimer
                        WHEN RTRIM(SatJual) = RTRIM(satSekunder) THEN QtySekunder
                        WHEN RTRIM(SatJual) = RTRIM(satTritier) THEN QtyTritier
                        ELSE 0
                    END as QtyJual
                "),

                DB::raw("RTRIM(SatJual) as SatJual"),

                // HEADER
                'IDPengiriman',
                'TglKirim',
                'NamaCust',
                'NamaExpeditor',
                'TrukNopol',
                'No_PO',
                'AlamatCustomer',
                'AlamatKirimDO',
                'NamaSatpam',
                'TglAccSatpam',
                'NamaSupir',
                'TglTTSupir',
                'TglAcc'
            ])
            ->get();

        if ($data->isEmpty()) {
            abort(404, 'Data tidak ditemukan');
        }

        $header = $data->first();

        // Format tanggal kirim
        $header->TglKirim = $header->TglKirim
            ? Carbon::parse($header->TglKirim)->format('d-m-Y H:i:s')
            : '-';

        // LOGIC SATPAM ATAU SUPIR (PRIORITAS SATPAM)
        $header->PengirimNama = $header->NamaSatpam ?: $header->NamaSupir;
        $header->TglPengirim  = $header->TglAccSatpam ?: $header->TglTTSupir;

        // FORMAT TANGGAL SATPAM ATAU SUPIR
        $header->TglPengirim = $header->TglPengirim
            ? Carbon::parse($header->TglPengirim)->format('d-m-Y H:i:s')
            : '-';

        // FORMAT TANGGAL DITERIMA
        $header->TglAcc = $header->TglAcc
            ? Carbon::parse($header->TglAcc)->format('d-m-Y H:i:s')
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
