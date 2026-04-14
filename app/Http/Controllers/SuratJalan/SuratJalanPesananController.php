<?php

namespace App\Http\Controllers\SuratJalan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Encryption\Encrypter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Contracts\Encryption\DecryptException;


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
        $encryptedId = $id_pengiriman;

        $key = env('QR_SHARED_SECRET');
        $cipher = 'AES-256-CBC';

        $encrypter = new Encrypter($key, $cipher);
        try {
            $idPengiriman = $encrypter->decryptString(urldecode($encryptedId));
        } catch (DecryptException $de) {
            abort(404);
        }

        $row = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $idPengiriman)
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
            'id_pengiriman' => $idPengiriman,
            'no_po' => $row->No_PO,
            'otp' => $otp
        ]);
    }

    public function detailModalSJ($id)
    {
        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $id)
            ->select([
                'SuratPesanan',
                'TglKirim',
                'TrukNopol',
                'SatJual',

                DB::raw("
                    CASE
                        WHEN RTRIM(SatJual) = RTRIM(satPrimer) THEN QtyPrimer
                        WHEN RTRIM(SatJual) = RTRIM(satSekunder) THEN QtySekunder
                        WHEN RTRIM(SatJual) = RTRIM(satTritier) THEN QtyTritier
                        ELSE 0
                    END as Qty
                "),

                'NamaType',
                'AlamatKirimCustomer',
                'AlamatCustomer',
                'KotaCustomer',
                'NamaCust',
                'IDPengiriman',
                'No_PO',
                'NoContainer',
                'NoSeal',
                'JnsIdPengiriman',
                'AlamatKirimDO',
                'NamaKelompokUtama',
                'NamaExpeditor',
                'AlamatExpeditor',
                'KotaExpeditor',
                'IdType',
                'Ket',
                'JnsCust',
                'NamaSupir',
                'NamaSatpam'
            ])
            ->get();

        return response()->json([
            'data' => $data
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

        Mail::mailer('MailSales')->raw("Kode OTP Verifikasi Anda: $otp", function ($message) use ($request) {
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
            'otp' => 'required|digits:6'
        ]);

        $now = Carbon::now('Asia/Jakarta');

        try {

            $idSuratJalan = DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IDPengiriman', $request->id_pengiriman)
                ->value('IdSuratJalan');

            if (!$idSuratJalan) {
                return response()->json([
                    'error' => 'Data tidak ditemukan'
                ], 404);
            }

            DB::beginTransaction();

            $otp = DB::table('T_SuratJalanOTP')
                ->where('IdSuratJalan', $idSuratJalan)
                ->where('Email', $request->email)
                ->where('OTP', $request->otp)
                ->where('IsUsed', 0)
                ->where('ExpiredAt', '>=', $now)
                ->lockForUpdate()
                ->first();

            if (!$otp) {
                DB::rollBack();

                return response()->json([
                    'error' => 'OTP tidak valid atau expired'
                ], 400);
            }

            DB::commit();

            return response()->json([
                'status' => 'OTP_VALID',
                'id_surat_jalan' => $idSuratJalan
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('VERIFY OTP ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Terjadi kesalahan server'
            ], 500);
        }
    }

    public function confirmApproval(Request $request)
    {
        $request->validate([
            'id_surat_jalan' => 'required',
            'is_sesuai' => 'required|boolean',
            'qty_temp' => 'nullable|integer|min:1',
            'email' => 'required|email'
        ]);

        $now = Carbon::now('Asia/Jakarta');

        DB::beginTransaction();

        try {

            $data = DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IdSuratJalan', $request->id_surat_jalan)
                ->lockForUpdate()
                ->first();

            if (!$data) {
                throw new \Exception('Data tidak ditemukan');
            }

            if ((int)$data->ACCCustomer === 1) {
                DB::commit();

                return response()->json([
                    'status' => 'ALREADY_APPROVED'
                ]);
            }

            // validasi qty
            if (!(int)$request->is_sesuai && !$request->qty_temp) {
                throw new \Exception('Qty harus diisi');
            }

            // ============
            // GENERATE QR
            // ============
            $key = env('QR_SHARED_SECRET');

            if (!$key || strlen($key) !== 32) {
                throw new \Exception('QR key tidak valid');
            }

            $encrypter = new Encrypter($key, 'AES-256-CBC');

            $encrypted = urlencode(
                $encrypter->encryptString((string) $data->IDPengiriman)
            );

            $link = url('/DokumenSJ/' . $encrypted);

            $qrImage = QrCode::format('svg')
                ->size(150)
                ->generate($link);

            $qrBase64 = base64_encode($qrImage);

            // ==============
            // UPDATE DATA
            // ==============
            if ((int)$request->is_sesuai === 1) {

                // ✅ ACC
                $update = [
                    'ACCCustomer' => 1,
                    'GbrACCCustomer' => $qrBase64
                ];

                // update OTP (dengan ApprovedAt)
                DB::table('T_SuratJalanOTP')
                    ->where('IdSuratJalan', $request->id_surat_jalan)
                    ->where('IsUsed', 0)
                    ->update([
                        'IsUsed' => 1,
                        'ApprovedAt' => $now
                    ]);

            } else {
                // PASCA KIRIM
                $update = [
                    'ACCCustomer' => 0,
                    'QtyTemp' => $request->qty_temp
                ];

                // update OTP TANPA ApprovedAt
                DB::table('T_SuratJalanOTP')
                    ->where('IdSuratJalan', $request->id_surat_jalan)
                    ->where('IsUsed', 0)
                    ->update([
                        'IsUsed' => 1
                    ]);
            }

            // eksekusi update (tetap satu pintu, tidak ubah flow)
            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IdSuratJalan', $request->id_surat_jalan)
                ->update($update);

            // ===========
            // UPDATE OTP
            // ===========
            DB::table('T_SuratJalanOTP')
                ->where('IdSuratJalan', $request->id_surat_jalan)
                ->where('IsUsed', 0)
                ->update([
                    'IsUsed' => 1,
                    'ApprovedAt' => $now
                ]);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('CONFIRM APPROVAL ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }

        // =====
        // EMAIL
        // =====
        if ((int)$request->is_sesuai === 1) {
            try {
                $this->sendSuratJalanEmail(
                    $data->IDPengiriman,
                    [$request->email]
                );
            } catch (\Exception $e) {
                Log::error('EMAIL ERROR', [
                    'message' => $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'status' => 'APPROVED'
        ]);
    }

    public function resendEmail(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'required'
        ]);

        $emails = array_map('trim', explode(',', $request->email));

        $invalidEmails = [];

        foreach ($emails as $email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $invalidEmails[] = $email;
            }
        }

        if (!empty($invalidEmails)) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak valid: ' . implode(', ', $invalidEmails)
            ]);
        }

        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $request->id_pengiriman)
            ->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data Surat Jalan tidak ditemukan'
            ], 404);
        }

        // tidak bisa resend email ketika pasca kirim
        if ((int)$data->ACCCustomer !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa resend email karena status PASCA KIRIM / belum ACC'
            ], 400);
        }


        try {
            $this->sendSuratJalanEmail(
                $request->id_pengiriman,
                $emails
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email berhasil dikirim ke ' . implode(', ', $emails)
        ]);
    }

    public function sendSuratJalanEmail($idPengiriman, array $emails)
    {
        $items = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $idPengiriman)
            ->first();

        if (!$items) {
            throw new \Exception('Data Surat Jalan tidak ditemukan');
        }

        // Helper universal
        $formatBase64Image = function ($base64) {
            if (empty($base64)) return null;

            $clean = trim(str_replace(["\r", "\n"], '', $base64));
            $binary = base64_decode($clean);

            if ($binary === false) return null;

            $mime = 'image/png';
            if (substr($binary, 0, 2) === "\xFF\xD8") {
                $mime = 'image/jpeg';
            }

            return "data:$mime;base64," . $clean;
        };

        $barcodeGudang = $formatBase64Image($items->GbrACCGudang);
        $barcodeSupir  = $formatBase64Image($items->GbrACCSupir);

        // Customer
        $ttCustomer = $formatBase64Image($items->GbrACCCustomer);

        //nama customer
        $namaCustomer = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as cup')
            ->join('UserPublic as up', 'cup.IdUser', '=', 'up.IdUser')
            ->where('cup.IDCust', $items->IDCust)
            ->value('up.NamaUser') ?? '-';

        // Pengirim (supir / satpam)
        $namaPengirim = null;
        $ttdPengirim = null;

        if (!empty($items->NamaSupir) || !empty($items->GbrACCSupir)) {
            $namaPengirim = $items->NamaSupir;
            $ttdPengirim = $barcodeSupir;
        } elseif (!empty($items->NamaSatpam) || !empty($items->GbrACCSatpam)) {
            $namaPengirim = $items->NamaSatpam;
            $ttdPengirim = $formatBase64Image($items->GbrACCSatpam);
        }

        $pdf = Pdf::loadView('SuratJalan.SuratJalanPDF', [
            'items' => $items,
            'namaPengirim' => $namaPengirim,
            'ttdPengirim' => $ttdPengirim,

            // semua ttd
            'barcodeGudang' => $barcodeGudang,
            'barcodeSupir' => $barcodeSupir,
            'ttCustomer' => $ttCustomer,
            'namaCustomer' => $namaCustomer,
        ])->setPaper('A4', 'portrait');

        Mail::mailer('MailSales')->send([], [], function ($message) use ($emails, $idPengiriman, $pdf) {
            $message->to($emails)
                ->subject("Surat Jalan {$idPengiriman}")
                ->html("
                    Berikut adalah Surat Jalan dengan nomor <b>{$idPengiriman}</b>.<br>
                    Silakan cek dokumen terlampir.
                ")
                ->attachData(
                    $pdf->output(),
                    "Surat Jalan {$idPengiriman}.pdf",
                    ['mime' => 'application/pdf']
                );
        });
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
