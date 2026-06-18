<?php

namespace App\Http\Controllers\SuratJalan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Encryption\Encrypter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Contracts\Encryption\DecryptException;
use App\Mail\OTPMail;


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
            ->table('T_KirimSuratJalan as sj')

            ->leftJoin('CustomerUserPublic as cup', 'sj.IDCust', '=', 'cup.IDCust')
            ->leftJoin('UserPublic as u', 'cup.IdUser', '=', 'u.IdUser')

            ->where('sj.IDPengiriman', $id)
            ->where('u.IdUser', session('user')->IdUser)
            ->select([
                'sj.SuratPesanan',
                'sj.TglKirim',
                'sj.TrukNopol',
                'sj.SatJual',

                DB::raw("
                    CASE
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satPrimer) THEN sj.QtyPrimer
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satSekunder) THEN sj.QtySekunder
                        WHEN RTRIM(sj.SatJual) = RTRIM(sj.satTritier) THEN sj.QtyTritier
                        ELSE 0
                    END as Qty
                "),

                'sj.NamaType',
                'sj.AlamatKirimCustomer',
                'sj.AlamatCustomer',
                'sj.KotaCustomer',
                'sj.NamaCust',
                'sj.IDPengiriman',
                'sj.No_PO',
                'sj.NoContainer',
                'sj.NoSeal',
                'sj.JnsIdPengiriman',
                'sj.AlamatKirimDO',
                'sj.NamaKelompokUtama',
                'sj.NamaExpeditor',
                'sj.AlamatExpeditor',
                'sj.KotaExpeditor',
                'sj.IdType',
                'sj.Ket',
                'sj.JnsCust',
                'sj.NamaSupir',
                'sj.NamaSatpam',
                'u.NamaPerusahaan'
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

    //list surat jalan
    public function listData(Request $request)
    {
        $user = session('user');

        if (!$user) {
            return response()->json([
                'data' => []
            ]);
        }

        $idCustList = DB::connection('ConnPublic')
            ->table('CustomerUserPublic')
            ->where('IdUser', $user->IdUser)
            ->pluck('IDCust');

        if ($idCustList->isEmpty()) {
            return response()->json([
                'data' => []
            ]);
        }

        $query = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan as sj')
            ->whereIn('sj.IDCust', $idCustList);

        // SEARCH
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('sj.No_PO', 'like', '%' . $search . '%')
                    ->orWhere('sj.NamaType', 'like', '%' . $search . '%');
            });
        }

        // RANGE TANGGAL
        if ($request->filled('date_from')) {
            $query->whereDate('sj.TglKirim', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sj.TglKirim', '<=', $request->date_to);
        }

        $data = $query
            ->select(
                'sj.IDPengiriman',
                'sj.No_PO',
                DB::raw('CONVERT(date, MAX(sj.TglKirim)) as TglKirim'),
                DB::raw('MIN(sj.NamaType) as NamaType')
            )
            ->groupBy(
                'sj.IDPengiriman',
                'sj.No_PO'
            )

            ->havingRaw("
                SUM(CASE
                    WHEN sj.ACCCUSTOMER IS NULL
                    THEN 1 ELSE 0 END
                ) > 0
            ")

            ->orderByDesc(DB::raw('MAX(sj.TglKirim)'))
            ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    public function getContacts($id_pengiriman)
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

        $contacts = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as c')
            ->join('UserPublic as u', 'c.IdUser', '=', 'u.IdUser')
            ->where('c.IDCust', $idCust)
            ->select('u.Email', 'u.NoHP as Phone', 'u.NamaUser')
            ->distinct()
            ->get();

        return response()->json($contacts);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'nullable|email|required_without:phone',
            'phone' => ['nullable', 'required_without:email', 'regex:/^628[0-9]{8,13}$/'],
            'otp_method' => 'required|in:email,phone',
        ], [
            'phone.regex' =>
                'Nomor HP harus menggunakan format 628xxxxxxxxxx',
            'email.required_without' =>
                'Email atau nomor HP wajib diisi',
            'phone.required_without' =>
                'Email atau nomor HP wajib diisi'
        ]);

        $otp = rand(100000, 999999);
        $now = Carbon::now('Asia/Jakarta');
        $phone = $request->phone ? preg_replace('/[^0-9]/', '', $request->phone) : null;

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
            ->where('IsUsed', 0)
            ->where('ExpiredAt', '>', $now)
            ->latest('CreatedAt')
            ->first();

        if ($lastOtp) {
            return response()->json([
                'error' => 'OTP sudah dikirim, tunggu 5 menit'
            ], 400);
        }

        DB::table('T_SuratJalanOTP')
            ->where('IdSuratJalan', $idSuratJalan)
            ->where('IsUsed', 0)
            ->update([
                'ExpiredAt' => $now
            ]);

        DB::table('T_SuratJalanOTP')->insert([
            'IdSuratJalan' => $idSuratJalan,
            'Email' => $request->email,
            'Phone' => $phone,
            'OTP' => $otp,
            'IsUsed' => 0,
            'ExpiredAt' => $now->copy()->addMinutes(5),
            'CreatedAt' => $now
        ]);

        if ($request->otp_method === 'email') {
            // kirim email
            // Mail::mailer('MailNoReply')->raw(
            //     "Kode OTP Approval Surat Jalan Anda: $otp",
            //     function ($message) use ($request) {
            //         $message->to($request->email)
            //             ->subject('OTP Approval Surat Jalan');
            //     }
            // );
            Mail::mailer('MailNoReply')
                ->to($request->email)
                ->send(new OTPMail($request->email, $otp, 'Approval Surat Jalan'));

        } elseif ($request->otp_method === 'phone') {
            // kirim sms
            $response = Http::withHeaders([
                'Authorization' => 'App ' . env('SMSVIRO_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(
                    'https://api.smsviro.com/restapi/sms/1/text/single',
                    [
                        'from' => env('SMSVIRO_SENDER_ID'),
                        'to' => $phone,
                        'text' => "Kode OTP Approval Surat Jalan Anda: $otp"
                    ]
                );

            $dataResponse = $response->json();
            $allowedStatus = [
                'PENDING',
                'ACCEPTED',
                'DELIVERED'
            ];

            if (
                !$response->successful() ||
                !isset($dataResponse['messages'][0]['status']['groupName']) ||
                !in_array(
                    $dataResponse['messages'][0]['status']['groupName'],
                    $allowedStatus
                )
            ) {

                DB::rollBack();
                Log::error('SMS Error: ' . $response->body());
                return response()->json([
                    'error' => 'Gagal mengirim OTP SMS'
                ], 500);
            }
        }

        return response()->json([
            'status' => 'OTP sent'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'nullable|email|required_without:phone',
            'phone' => ['nullable', 'required_without:email', 'regex:/^628[0-9]{8,13}$/'],
            'otp' => 'required|digits:6'
        ], [
            'phone.regex' =>
                'Nomor HP harus menggunakan format 628xxxxxxxxxx',
            'email.required_without' =>
                'Email atau nomor HP wajib diisi',
            'phone.required_without' =>
                'Email atau nomor HP wajib diisi'
        ]);

        $now = Carbon::now('Asia/Jakarta');

        try {
            $phone = $request->phone ? preg_replace('/[^0-9]/', '', $request->phone) : null;

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

                // jika email dipilih
                ->when(
                    $request->filled('email'),
                    function ($q) use ($request) {
                        $q->where('Email', $request->email);
                    }
                )

                // jika phone dipilih
                ->when(
                    $phone,
                    function ($q) use ($phone) {
                        $q->where('Phone', $phone);
                    }
                )

                ->where('OTP', $request->otp)
                ->where('IsUsed', 0)
                ->where('ExpiredAt', '>=', $now)
                ->orderByDesc('CreatedAt')
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
                'type' => $request->filled('email')
                    ? 'email'
                    : 'phone',
                'id_surat_jalan' => $idSuratJalan,
                'otp_id' => $otp->Id
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
            'otp_id' => 'required|integer'
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

            if ((int) $data->ACCCustomer === 1) {
                DB::commit();

                return response()->json([
                    'status' => 'ALREADY_APPROVED'
                ]);
            }

            // validasi qty
            if (!(int) $request->is_sesuai && !$request->qty_temp) {
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

            // $link = url('/DokumenSJ/view/' . $encrypted);
            $link = "https://mykrr.co.id/DokumenSJ/view/$encrypted";
            $qrImage = QrCode::format('svg')
                ->size(150)
                ->generate($link);

            $qrBase64 = base64_encode($qrImage);

            // ==============
            // UPDATE DATA
            // ==============
            if ((int) $request->is_sesuai === 1) {

                // ACC
                $update = [
                    'ACCCustomer' => 1,
                    'GbrACCCustomer' => $qrBase64
                ];

                // update OTP (dengan ApprovedAt)
                DB::table('T_SuratJalanOTP')
                    ->where('Id', $request->otp_id)
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
                    ->where('Id', $request->otp_id)
                    ->where('IsUsed', 0)
                    ->update([
                        'IsUsed' => 1
                    ]);
            }

            //debug
            $affected = DB::table('T_SuratJalanOTP')
                ->where('Id', $request->otp_id)
                ->where('IsUsed', 0)
                ->update([
                    'IsUsed' => 1,
                    'ApprovedAt' => $now
                ]);

            Log::info([
                'otp_id' => $request->otp_id,
                'affected_rows' => $affected
            ]);

            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IdSuratJalan', $request->id_surat_jalan)
                ->update($update);

            // ===========
            // UPDATE OTP
            // ===========
            //    DB::table('T_SuratJalanOTP')
            //         ->where('Id', $request->otp_id)
            //         ->where('IsUsed', 0)
            //         ->update([
            //             'IsUsed' => 1,
            //             'ApprovedAt' => $now
            //         ]);

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
        try {
            $emails = DB::connection('ConnPublic')
                ->table('CustomerUserPublic as c')
                ->join('UserPublic as u', 'c.IdUser', '=', 'u.IdUser')
                ->where('c.IDCust', $data->IDCust)
                ->whereNotNull('u.Email')
                ->pluck('u.Email')
                ->unique()
                ->values()
                ->toArray();

            if (!empty($emails)) {
                $this->sendSuratJalanEmail(
                    $data->IDPengiriman,
                    $emails
                );
            }

        } catch (\Exception $e) {
            Log::error('EMAIL ERROR', [
                'message' => $e->getMessage()
            ]);
        }

        return response()->json([
            'status' => 'TERKIRIM'
        ]);
    }

    public function resendEmail(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required'
        ]);

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

        // hanya bisa resend setelah ACC
        if ((int) $data->ACCCustomer !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa resend email karena status PASCA KIRIM / belum ACC'
            ], 400);
        }

        // ambil email customer dari database
        $emails = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as c')
            ->join(
                'UserPublic as u',
                'c.IdUser',
                '=',
                'u.IdUser'
            )
            ->where('c.IDCust', $data->IDCust)
            ->whereNotNull('u.Email')
            ->where('u.Email', '!=', '')
            ->pluck('u.Email')
            ->unique()
            ->values()
            ->toArray();

        if (empty($emails)) {
            return response()->json([
                'success' => false,
                'message' => 'Email customer tidak ditemukan'
            ], 404);
        }

        DB::beginTransaction();

        try {

            $resendCount =
                ((int) $data->ResendSJCount) + 1;

            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where(
                    'IDPengiriman',
                    $request->id_pengiriman
                )
                ->update([
                    'ResendSJCount' => $resendCount,
                    'LastResendSJAt' => now()
                ]);

            $this->sendSuratJalanEmail(
                $request->id_pengiriman,
                $emails,
                $resendCount
            );

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('RESEND EMAIL ERROR', [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' =>
                'Email resend ke-' .
                $resendCount .
                ' berhasil dikirim ke ' .
                implode(', ', $emails)
        ]);
    }

    public function sendSuratJalanEmail($idPengiriman, array $emails, int $resendCount = 0)
    {
        $items = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            ->where('IDPengiriman', $idPengiriman)
            ->first();

        if (!$items) {
            throw new \Exception('Data Surat Jalan tidak ditemukan');
        }

        // format base64
        $formatBase64Image = function ($base64) {
            if (empty($base64))
                return null;

            $clean = trim(str_replace(["\r", "\n"], '', $base64));
            $binary = base64_decode($clean);

            if ($binary === false)
                return null;

            $mime = 'image/png';

            if (substr($binary, 0, 2) === "\xFF\xD8") {
                $mime = 'image/jpeg';
            }

            return "data:$mime;base64," . $clean;
        };

        $barcodeGudang = $formatBase64Image($items->GbrACCGudang);
        $barcodeSupir = $formatBase64Image($items->GbrACCSupir);
        $ttCustomer = $formatBase64Image($items->GbrACCCustomer);

        $namaCustomer = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as cup')
            ->join('UserPublic as up', 'cup.IdUser', '=', 'up.IdUser')
            ->where('cup.IDCust', $items->IDCust)
            ->value('up.NamaUser') ?? '-';

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
            'namaPengirim' => $namaPengirim,
            'ttdPengirim' => $ttdPengirim,
            'barcodeGudang' => $barcodeGudang,
            'barcodeSupir' => $barcodeSupir,
            'ttCustomer' => $ttCustomer,
            'namaCustomer' => $namaCustomer,
            'namaExpeditor' => $namaExpeditor,
        ])->setPaper('A4', 'portrait');

        // Subject email
        $subject = "Surat Jalan {$idPengiriman}";

        if ($resendCount > 0) {
            $subject .= " - RESEND KE-{$resendCount}";
        }

        Mail::mailer('MailSales')->send([], [], function ($message) use ($emails, $idPengiriman, $pdf, $subject, $resendCount) {

            $body = "
                Berikut adalah Surat Jalan dengan nomor <b>{$idPengiriman}</b>.<br>
                Silakan cek dokumen terlampir.
            ";

            if ($resendCount > 0) {
                $body = "
                    Berikut adalah Resend ke-{$resendCount} dengan Surat Jalan nomor <b>{$idPengiriman}</b>.<br>
                    Silakan cek dokumen terlampir.
                ";
            }

            $message->to($emails)
                ->subject($subject)
                ->html($body)
                ->attachData(
                    $pdf->output(),
                    "Surat Jalan {$idPengiriman}.pdf",
                    ['mime' => 'application/pdf']
                );
        });
    }

    public function previewPdf(Request $request, $idPengiriman)
    {
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

        $namaCustomer = DB::connection('ConnPublic')
            ->table('CustomerUserPublic as cup')
            ->join('UserPublic as up', 'cup.IdUser', '=', 'up.IdUser')
            ->where('cup.IDCust', $items->IDCust)
            ->value('up.NamaUser') ?? '-';

        $namaExpeditor = $items->NamaExpeditor;

        $namaPengirim = null;
        $ttdPengirim = null;

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
            'namaPengirim' => $namaPengirim,
            'ttdPengirim' => $ttdPengirim,
            'barcodeGudang' => $barcodeGudang,
            'barcodeSupir' => $barcodeSupir,
            'ttCustomer' => $ttCustomer,
            'namaCustomer' => $namaCustomer,
            'namaExpeditor' => $namaExpeditor,
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("SuratJalan-{$idPengiriman}.pdf");
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
