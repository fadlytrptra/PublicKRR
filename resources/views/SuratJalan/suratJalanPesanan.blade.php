<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Product Receipt</title>

    <link rel="icon" type="image/png" href="{{ asset('images/KRR.png') }}">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/2.3.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/suratJalanPesanan.css') }}" rel="stylesheet">
</head>
<body>

<div class="container-fluid mt-3">
    <div class="row justify-content-center">
        <div class="col-11">
            <div class="card">

                <div class="card-header fw-bold">Product Receipt</div>

                <div class="card-body">

                    <!-- HEADER + OTP -->
                    <div class="mb-3 d-flex justify-content-between align-items-start">

                        <!-- LEFT -->
                        <div>
                            <div style="font-size:18px">
                                <strong>Nomor PO</strong> : <span id="noPo">-</span>
                            </div>
                            <div style="font-size:18px">
                                <strong>Tanggal Kirim</strong> : <span id="tglKirim">-</span>
                            </div>
                        </div>

                        <!-- RIGHT -->
                        <div style="min-width:250px">

                            @if(!isset($otp) || !$otp)
                                <button id="btnOpenOtp"
                                    class="btn btn-primary w-100 mb-2">
                                    Konfirmasi Product Receipt
                                </button>

                                <div id="otpSection" style="display:none;">

                                    <select id="contactType"
                                        class="form-select mb-2">
                                        <option value="">-- Pilih Metode OTP --</option>
                                        <option value="whatsapp">WhatsApp</option>
                                        <option value="sms">SMS</option>
                                    </select>

                                    <select id="contactSelect"
                                        class="form-select mb-2">
                                        <option value="">
                                            -- Pilih Kontak --
                                        </option>
                                    </select>

                                    <button id="btnSendOtp"
                                        class="btn btn-warning w-100 mb-2">
                                        Send OTP
                                    </button>

                                    <input type="text"
                                        id="otpInput"
                                        class="form-control mb-2"
                                        placeholder="Masukkan OTP">

                                    <button id="btnVerifyOtp"
                                        class="btn btn-success w-100">
                                        Verify OTP
                                    </button>

                                </div>

                            @endif

                            <!-- APPROVAL INFO -->
                            <div id="approvalInfo"
                                 class="mt-2"
                                 style="{{ isset($otp) && $otp ? '' : 'display:none;' }}">

                                <div id="rowStatus" style="display:none;">
                                    <strong id="labelStatus">Status:</strong>
                                    <span id="statusApproval"></span>
                                </div>

                                <div>
                                    <strong id="labelApprovedBy">Approved By:</strong>
                                    <span id="approvedEmail"></span>
                                </div>

                                <div>
                                    <strong id="labelApprovedAt">Approved At:</strong>
                                    <span id="approvedAt"></span>
                                </div>

                                <button id="btnResendEmail"
                                    class="btn w-100 mt-2 fw-bold">
                                    Resend Email
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- TABLE -->
                    <div class="table-responsive">
                        <table id="tableSuratJalan" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nama Barang</th>
                                    <th>Quantity</th>
                                    <th>Satuan</th>
                                    <th>Nama Customer</th>
                                    <th>Surat Pesanan</th>
                                    <th>Nama Ekspedisi</th>
                                    <th>Nomor Polisi</th>
                                </tr>
                            </thead>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PASCA -->
<div class="modal fade" id="modalKonfirmasi" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Penerimaan Barang</h5>
                {{-- <button type="button" class="btn-close" data-bs-dismiss="modal"></button> --}}
            </div>

            <div class="modal-body">

                <!-- STEP 1 -->
                <div id="stepKonfirmasi">
                    <p><strong>Apakah jumlah barang sudah sesuai?</strong></p>

                    <div class="d-flex gap-2">
                        <button id="btnYa" class="btn btn-success w-50">Ya</button>
                        <button id="btnTidak" class="btn btn-danger w-50">Tidak</button>
                    </div>
                </div>

                <!-- STEP 2 -->
                <div id="stepQty" style="display:none;">
                    <p><strong>Berapa jumlah barang yang sudah sesuai?</strong></p>

                    <input type="text" id="qtyInput"
                           class="form-control mb-2"
                           placeholder="Masukkan jumlah">

                    <button id="btnSubmitQty" class="btn btn-primary w-100">
                        Submit
                    </button>
                </div>

                <!-- STEP 3 -->
                <div id="stepFoto" style="display:none;">
                    <hr>

                    <p>
                        <strong>Upload Foto Barang</strong>
                    </p>

                    <small class="text-muted">
                        Total seluruh foto maksimal 50 MB.
                    </small>

                    <div class="mt-3">
                        <input type="file"
                            id="fileFoto"
                            class="form-control"
                            accept="image/*"
                            multiple>
                    </div>

                    <div class="mt-2">
                        <button
                            type="button"
                            id="btnCameraFoto"
                            class="btn btn-success">
                            Kamera
                        </button>
                    </div>

                    <input
                        type="file"
                        id="cameraInput"
                        hidden
                        accept="image/*"
                        capture="environment">

                    <div class="mt-3">
                        <span id="jumlahFotoDipilih">
                            0 foto dipilih
                        </span>
                    </div>

                    <div id="fotoPreview"
                        class="d-flex flex-wrap gap-2 mt-3">
                    </div>

                    <button
                        id="btnUploadFoto"
                        type="button"
                        class="btn btn-primary w-100 mt-3">
                        Upload Foto
                    </button>
                </div>

            </div>

        </div>
    </div>
</div>


<!-- MODAL ACC -->
<div class="modal fade" id="modalACC" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    Upload Foto Barang (Opsional)
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">

                <small class="text-muted">
                    Hanya dapat Upload maksimal 1 foto (5 MB).
                </small>

                <div class="mt-3">
                    <input type="file"
                           id="fileFotoACC"
                           class="form-control"
                           accept="image/*">
                </div>

                <div class="mt-2">
                    <button
                        type="button"
                        id="btnCameraFotoACC"
                        class="btn btn-success">
                        Kamera
                    </button>
                </div>

                <input
                    type="file"
                    id="cameraInputACC"
                    hidden
                    accept="image/*"
                    capture="environment">

                <div class="mt-3">
                    <span id="jumlahFotoDipilihACC">
                        0 foto dipilih
                    </span>
                </div>

                <div id="fotoPreviewACC"
                     class="d-flex flex-wrap gap-2 mt-3">
                </div>

                <button
                    id="btnUploadFotoACC"
                    type="button"
                    class="btn btn-primary w-100 mt-3">
                    Upload Foto
                </button>

                <button
                    type="button"
                    class="btn btn-secondary w-100 mt-2"
                    data-bs-dismiss="modal">
                    Lewati
                </button>

            </div>

        </div>
    </div>
</div>


<div id="cameraModal" class="camera-modal">
    <div class="camera-box">
        <video
            id="cameraVideo"
            autoplay
            playsinline>
        </video>

        <div class="camera-action">
            <button
                id="btnTakePhoto"
                type="button"
                class="btn btn-success">
                Ambil Foto
            </button>

            <button
                id="btnCloseCamera"
                type="button"
                class="btn btn-danger">
                Tutup
            </button>
        </div>
    </div>
</div>

<div class="modal fade" id="imagePreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Preview Foto</h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body text-center">

                <img
                    id="previewModalImage"
                    src=""
                    style="
                        max-width:100%;
                        max-height:75vh;
                        object-fit:contain;
                    "
                >
            </div>
        </div>
    </div>
</div>

<canvas id="cameraCanvas" hidden></canvas>

{{-- Inject data --}}
<script>
    window.appData = {
        idPengiriman: "{{ $id_pengiriman }}",
        noPo: "{{ $no_po }}"
    };

    window.otpData = @json($otp ?? null);
</script>

<script>
    window.wajibUploadFoto =
        @json($wajibUploadFoto);
</script>

<!-- jQuery -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

<!-- DataTables -->
<script src="https://cdn.datatables.net/2.3.7/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.3.7/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom JS -->
<script src="{{ asset('js/suratJalanPesanan.js') }}"></script>

</body>
</html>
