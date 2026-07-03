<?php

namespace App\Http\Controllers\SuratJalan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PascaKirimController extends Controller
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
        $encryptedId = $id;

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

        $otp = DB::table('T_PascaKirimOTP as otp')
            ->leftJoin(
                'T_KirimSuratJalan as sj',
                'sj.IdSuratJalan',
                '=',
                'otp.IdSuratJalan'
            )
            ->where('otp.IdSuratJalan', $row->IdSuratJalan)
            ->where('otp.IsUsed', 1)
            ->select(
                'otp.*',
                'sj.ACCCustomer',
                'sj.ACCCustomerPasca'
            )
            ->orderByDesc('otp.CreatedAt')
            ->first();

        return view('SuratJalan.pascaKirim', [
            'id_pengiriman' => $idPengiriman,
            'no_po' => $row->No_PO,
            'otp' => $otp,
        ]);
    }

    public function data(Request $request)
    {
        $no_po = $request->no_po;
        $idPengiriman = $request->idPengiriman;

        $data = DB::connection('ConnPublic')
            ->table('T_KirimSuratJalan')
            // ->where('No_PO', $no_po)
            ->where('IDPengiriman', $idPengiriman)
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
                'QtyTempVerifikasi',
                'NotePascaKeCustomer',
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
                'NotePascaKeCustomer' => $data->first()->NotePascaKeCustomer ?? null,
            ]
        ]);
    }

    public function sendOtp(Request $request)
    {
        $request->validate([
            'id_pengiriman' => 'required',
            'email' => 'nullable|email|required_without:phone',
            'phone' => ['nullable', 'required_without:email', 'regex:/^628[0-9]{8,13}$/'],
            'otp_method' => 'required|in:email,sms',
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
        $lastOtp = DB::table('T_PascaKirimOTP')
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

        DB::table('T_PascaKirimOTP')
            ->where('IdSuratJalan', $idSuratJalan)
            ->where('IsUsed', 0)
            ->update([
                'ExpiredAt' => $now
            ]);

        DB::table('T_PascaKirimOTP')->insert([
            'IdSuratJalan' => $idSuratJalan,
            'Email' => $request->email,
            'Phone' => $phone,
            'OTP' => $otp,
            'IsUsed' => 0,
            'ExpiredAt' => $now->copy()->addMinutes(5),
            'CreatedAt' => $now
        ]);

        $message =
            "Kode OTP Approval Surat Jalan Anda: {$otp}\n\n" .
            "OTP berlaku selama 5 menit.";


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
                ->send(new OTPMail($request->email, $otp, 'Approval Pasca Kirim'));


            // if ($request->otp_method === 'whatsapp') {
            //     $response = Http::withHeaders([
            //         'Authorization' => env('WA_TOKEN')
            //     ])->post('https://api.fonnte.com/send', [
            //         'target' => $phone,
            //         'message' => $message
            //     ]);
        } elseif ($request->otp_method === 'sms') {
            // kirim sms
            $response = Http::withHeaders([
                'Authorization' => 'App ' . env('SMSVIRO_API_KEY'),
                'Content-Type' => 'application/json',
            ])->post(
                    'https://api.smsviro.com/restapi/sms/1/text/single',
                    [
                        'from' => env('SMSVIRO_SENDER_ID'),
                        'to' => $phone,
                        'text' => $message
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

            $otp = DB::table('T_PascaKirimOTP')
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
            'otp_id' => 'required|integer',
            'is_sesuai' => 'required|boolean',
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

            if ((int) $data->ACCCustomerPasca === 1) {
                DB::commit();

                return response()->json([
                    'status' => 'ALREADY_APPROVED'
                ]);
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

            DB::table('T_PascaKirimOTP')
                ->where('Id', $request->otp_id)
                ->where('IsUsed', 0)
                ->update([
                    'IsUsed' => 1,
                    'ApprovedAt' => $now
                ]);

            if ($request->is_sesuai == 1) {
                $update = [
                    'ACCCustomerPasca' => 1,
                    'GbrACCCustomer' => $qrBase64
                ];
            } else {
                $update = [
                    'QtyTempVerifikasi' => NULL,
                ];
            }


            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IdSuratJalan', $request->id_surat_jalan)
                ->update($update);

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

        if (!$data->GbrACCCustomer) {
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

            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IdSuratJalan', $data->IdSuratJalan)
                ->update(['GbrACCCustomer' => $qrBase64]);

            $data = DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where('IDPengiriman', $request->id_pengiriman)
                ->first();
        }

        // hanya bisa resend setelah ACC
        if ((int) $data->ACCCustomerPasca !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa resend email karena status belum ACC'
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
                ((int) $data->PascaKirimResendSJCount) + 1;

            DB::connection('ConnPublic')
                ->table('T_KirimSuratJalan')
                ->where(
                    'IDPengiriman',
                    $request->id_pengiriman
                )
                ->update([
                    'PascaKirimResendSJCount' => $resendCount,
                    'PascaKirimLastResendSJAt' => now()
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
        $ttCustomer2 = $formatBase64Image($items->GbrACCCustomer);

        // $otp = DB::table('T_SuratJalanOTP')
        //     ->where('IdSuratJalan', $items->IdSuratJalan)
        //     ->whereNotNull('ApprovedAt')
        //     ->orderByDesc('ApprovedAt')
        //     ->first();

        $otp = DB::table('T_PascaKirimOTP')
            ->where('IdSuratJalan', $items->IdSuratJalan)
            ->where('IsUsed', 1)
            ->latest('CreatedAt')
            ->first();

        $tanggalCustomer = null;

        if ($otp) {
            $tanggalCustomer = $otp->ApprovedAt ?? $otp->CreatedAt;
        }

        $namaCustomer = '-';
        if ($otp) {
            $namaCustomer = DB::connection('ConnPublic')
                ->table('UserPublic')
                ->where(function ($q) use ($otp) {
                    if (!empty($otp->Email)) {
                        $q->where('Email', $otp->Email);
                    }
                    if (!empty($otp->Phone)) {
                        $q->orWhere('NoHP', $otp->Phone);
                    }
                })
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

        $template = 'SuratJalan.SuratJalanPascaACCPDF';

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

        // Subject email
        $subject = "Pasca Kirim Surat Jalan {$idPengiriman}";

        if ($resendCount > 0) {
            $subject .= " - RESEND KE-{$resendCount}";
        }

        Mail::mailer('MailShipment')->send([], [], function ($message) use ($emails, $idPengiriman, $pdf, $subject, $resendCount) {

            $body = "
                Berikut adalah <strong>Pasca Kirim</strong> Surat Jalan dengan nomor <b>{$idPengiriman}</b>.<br>
                Silakan cek dokumen terlampir.
            ";

            if ($resendCount > 0) {
                $body = "
                    Berikut adalah Resend ke-{$resendCount} untuk <strong>Pasca Kirim</strong> Surat Jalan nomor <b>{$idPengiriman}</b>.<br>
                    Silakan cek dokumen terlampir.
                ";
            }

            $message->to($emails)
                ->subject($subject)
                ->html($body)
                ->attachData(
                    $pdf->output(),
                    "Pasca Kirim Surat Jalan {$idPengiriman}.pdf",
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
