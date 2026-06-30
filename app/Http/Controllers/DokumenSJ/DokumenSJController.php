<?php

namespace App\Http\Controllers\DokumenSJ;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;

class DokumenSJController extends Controller
{
    public function index()
    {
        $user = session('user');

        $list = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->join('CustomerUserPublic as cup', 'cup.IDCust', '=', 'sj.IDCust')

            ->where('cup.IdUser', $user->IdUser)

            ->select(
                'sj.IDPengiriman',
                DB::raw('MAX(sj.TglKirim) as TglKirim'),
                DB::raw('MAX(sj.No_PO) as No_PO'),
                DB::raw('MAX(sj.NamaType) as NamaType')
            )

            ->groupBy('sj.IDPengiriman')

            ->havingRaw("
                SUM(
                    CASE
                        WHEN sj.ACCCUSTOMER = 1
                            OR (sj.ACCCUSTOMER = 0 AND sj.QtyTemp IS NOT NULL)
                        THEN 1
                        ELSE 0
                    END
                ) > 0
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

    public function show($id)
    {
        $jenisACC = '';
        try {
            $key = env('QR_SHARED_SECRET');
            $cipher = 'AES-256-CBC';

            $encrypter = new Encrypter($key, $cipher);
            $hasilDecrypt = $encrypter->decryptString(
                urldecode($id)
            );

            if (str_contains($hasilDecrypt, 'jenisAcc')) {
                $idPengiriman = str_pad(explode('=', explode('&', $hasilDecrypt)[0])[1], 10, 0, STR_PAD_LEFT);
                $jenisACC = explode('=', explode('&', $hasilDecrypt)[1])[1];
            } else {
                $idPengiriman = $hasilDecrypt;
            }
        } catch (DecryptException $e) {
            abort(404);
        }

        $lastOtp = DB::table('T_SuratJalanOTP')
            ->selectRaw('MAX(Id) as Id, IdSuratJalan')
            ->groupBy('IdSuratJalan');

        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('CustomerUserPublic as cup')
                    ->whereColumn('cup.IDCust', 'sj.IDCust');
            })
            ->leftJoinSub($lastOtp, 'lastOtp', function ($join) {
                $join->on('lastOtp.IdSuratJalan', '=', 'sj.IdSuratJalan');
            })
            ->leftJoin('T_SuratJalanOTP as otp', 'otp.Id', '=', 'lastOtp.Id')
            ->where('sj.IDPengiriman', $idPengiriman)
            ->select([
                'sj.NamaType',
                'sj.Ket',

                DB::raw("
                    CASE
                        WHEN sj.ACCCUSTOMER = 0
                            AND sj.QtyTemp IS NOT NULL
                        THEN sj.QtyTemp

                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satPrimer)
                        THEN sj.QtyPrimer

                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satSekunder)
                        THEN sj.QtySekunder

                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satTritier)
                        THEN sj.QtyTritier

                        ELSE 0
                    END as QtyJual
                "),

                DB::raw("
                    CASE
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satPrimer)
                        THEN sj.QtyPrimer

                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satSekunder)
                        THEN sj.QtySekunder

                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satTritier)
                        THEN sj.QtyTritier

                        ELSE 0
                    END as QtyAsli
                "),

                'sj.QtyTemp',
                'sj.ACCCUSTOMER',

                DB::raw("RTRIM(sj.SatJual) as SatJual"),

                // HEADER
                'sj.IDPengiriman',
                'sj.IdSuratJalan',
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

        $otp = DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $header->IdSuratJalan)
            ->where('IsUsed', 1)
            ->latest('CreatedAt')
            ->first();

       $header->TglApp = $otp
            ? Carbon::parse(
                $otp->ApprovedAt ?? $otp->CreatedAt
            )->format('d-m-Y H:i:s')
            : '-';

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

        foreach ($data as $item) {
            $item->SatJual = $this->formatSatuan($item->SatJual);
        }

        return view('DokumenSJ.index', compact('header', 'data', 'jenisACC'));
    }

    public function formatSatuan($satuan)
    {
        $map = [
            'TABUNG' => 'TABUNG',
            'SET' => 'SET',
            'KGM' => 'KILOGRAM',
            'RP' => 'RP',
            'BALL' => 'BALL',
            'LBR' => 'LEMBAR',
            'PC' => 'POTONG',
            'YARDS' => 'YARD',
            'MTR²' => 'METER PERSEGI',
            'ROLL' => 'ROLL',
            'DRUM' => 'DRUM',
            'LJR' => 'LONJOR',
            'MTR' => 'METER',
            'UNIT' => 'UNIT',
        ];

        return $map[trim($satuan)] ?? $satuan;
    }

    public function search(Request $request)
    {
        $user = session('user');

        $query = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->join('CustomerUserPublic as cup', 'cup.IDCust', '=', 'sj.IDCust')
            ->where('cup.IdUser', $user->IdUser);

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('sj.IDPengiriman', 'like', '%' . $search . '%')
                    ->orWhere('sj.NamaCust', 'like', '%' . $search . '%');
            });
        }

        // RANGE TANGGAL
        if ($request->filled('date_from')) {
            $query->whereDate('sj.TglKirim', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sj.TglKirim', '<=', $request->date_to);
        }

        $list = $query
            ->select(
                'sj.IDPengiriman',
                DB::raw('MAX(sj.TglKirim) as TglKirim'),
                DB::raw('MAX(sj.NamaCust) as NamaCust')
            )
            ->groupBy('sj.IDPengiriman')
            ->havingRaw("
                SUM(
                    CASE
                        WHEN sj.ACCCUSTOMER = 1
                            OR (sj.ACCCUSTOMER = 0 AND sj.QtyTemp IS NOT NULL)
                        THEN 1
                        ELSE 0
                    END
                ) > 0
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

    public function downloadPdf($id)
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

        $items = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $idPengiriman)
            ->first();

        if (!$items) {
            abort(404, 'Data Surat Jalan tidak ditemukan');
        }

        $formatBase64Image = function ($base64) {

            if (empty($base64)) {
                return null;
            }

            $clean = trim(str_replace(["\r", "\n"], '', $base64));

            $binary = base64_decode($clean);

            if ($binary === false) {
                return null;
            }

            $mime = 'image/png';

            if (substr($binary, 0, 2) === "\xFF\xD8") {
                $mime = 'image/jpeg';
            }

            return "data:$mime;base64," . $clean;
        };

        $barcodeGudang = $formatBase64Image($items->GbrACCGudang);
        $barcodeSupir = $formatBase64Image($items->GbrACCSupir);
        $ttCustomer = $formatBase64Image($items->GbrACCCustomer);
        $ttCustomer2 = $formatBase64Image($items->GbrACCCustomer);
        $otp = DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $items->IdSuratJalan)
            ->where('IsUsed', 1)
            ->latest('CreatedAt')
            ->first();

        $tanggalCustomer = null;

        if ($otp) {
            $tanggalCustomer = $otp->ApprovedAt ?? $otp->CreatedAt;
        }

        $namaCustomer = '-';

        if ($otp && !empty($otp->Phone)) {

            $phone = preg_replace('/[^0-9]/', '', $otp->Phone);

            $namaCustomer = DB::connection('ConnPublic')
                ->table('UserPublic')
                ->where('NoHP', $phone)
                ->value('NamaUser') ?? '-';
        }

        $namaPengirim = null;
        $ttdPengirim = null;

        $namaExpeditor = $items->NamaExpeditor;

        if (!empty($items->NamaSupir) || !empty($items->GbrACCSupir)) {

            $namaPengirim = $items->NamaSupir;
            $ttdPengirim = $barcodeSupir;

        } elseif (!empty($items->NamaSatpam) || !empty($items->GbrACCSatpam)) {

            $namaPengirim = $items->NamaSatpam;
            $ttdPengirim = $formatBase64Image($items->GbrACCSatpam);
        }

        $template = ((int)$items->ACCCustomer === 1)
            ? 'SuratJalan.SuratJalanPDF'
            : 'SuratJalan.SuratJalanPascaPDF';

        $pdf = Pdf::loadView($template, [
            'items' => $items,
            'otp' => $otp,
            'namaPengirim' => $namaPengirim,
            'ttdPengirim' => $ttdPengirim,
            'barcodeGudang' => $barcodeGudang,
            'barcodeSupir' => $barcodeSupir,
            'ttCustomer' => $ttCustomer,
            'ttCustomer2' => $ttCustomer2,
            'namaCustomer' => $namaCustomer,
            'tanggalCustomer' => $tanggalCustomer,
            'namaExpeditor' => $namaExpeditor,
        ])->setPaper('A4', 'portrait');

        return $pdf->download(
            'Surat Jalan ' . $idPengiriman . '.pdf'
        );
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
