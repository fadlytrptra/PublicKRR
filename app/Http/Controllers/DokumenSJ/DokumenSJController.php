<?php

namespace App\Http\Controllers\DokumenSJ;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class DokumenSJController extends Controller
{
    public function index()
    {
        $list = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->join('CustomerUserPublic as cup', 'cup.IDCust', '=', 'sj.IDCust')

            ->select(
                'sj.IDPengiriman',
                DB::raw('MAX(sj.TglKirim) as TglKirim'),
                DB::raw('MAX(sj.NamaCust) as NamaCust')
            )

            ->groupBy('sj.IDPengiriman')

            ->havingRaw("
                COUNT(*) = SUM(
                    CASE
                        WHEN sj.ACCCustomer = 1
                            AND sj.QtyTemp IS NULL
                        THEN 1
                        ELSE 0
                    END
                )
            ")
            ->orderByDesc('TglKirim')
            ->get()
            ->map(function ($item) {
                $key = env('QR_SHARED_SECRET');
                $cipher = 'AES-256-CBC';
                $encrypter = new Encrypter($key, $cipher);

                $item->encrypted_id = urlencode(
                    $encrypter->encryptString((string) $item->IDPengiriman)
                );

                return $item;
            });

        return view('DokumenSJ.list', compact('list'));
    }

    /**
     * DETAIL DOKUMEN
     */
    public function show($id)
    {
        try {
            $key = env('QR_SHARED_SECRET');
            $cipher = 'AES-256-CBC';

            $encrypter = new Encrypter($key, $cipher);

            $idPengiriman = $encrypter->decryptString(
                urldecode($id)
            );

        } catch (DecryptException $e) {
            abort(404);
        }

        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->join('CustomerUserPublic as cup', 'cup.IDCust', '=', 'sj.IDCust')
            ->leftJoin('T_SuratJalanOTP as otp', function ($join) {
                $join->on('otp.IdSuratJalan', '=', 'sj.IdSuratJalan')
                     ->whereNotNull('otp.ApprovedAt');
            })
            ->where('sj.IDPengiriman', $idPengiriman)
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
            abort(404, 'Data tidak ditemukan atau tidak memiliki akses');
        }

        $header = $data->first();

        // Tgl Kirim
        $header->TglKirim = $header->TglKirim
            ? Carbon::parse($header->TglKirim)->format('d-m-Y H:i:s')
            : '-';

        // Prioritas: Supir > Satpam
        $header->PengirimNama = $header->NamaSupir ?: $header->NamaSatpam;
        $tglPengirim = $header->TglTTSupir ?: $header->TglAccSatpam;

        $header->TglPengirim = $tglPengirim
            ? Carbon::parse($tglPengirim)->format('d-m-Y H:i:s')
            : '-';

        // Tanggal diterima customer
        $header->TglApp = $header->TglApp
            ? Carbon::parse($header->TglApp)->format('d-m-Y H:i:s')
            : '-';

        return view('DokumenSJ.index', compact('header', 'data'));
    }

    public function create() {

    }
    public function store(Request $request) {

    }
    public function edit($id) {

    }
    public function update(Request $request, $id) {

    }
    public function destroy($id) {

    }
}
