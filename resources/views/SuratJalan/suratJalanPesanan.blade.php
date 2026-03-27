@extends('layouts.app')

@section('content')

<link href="{{ asset('css/suratJalanPesanan.css') }}" rel="stylesheet">

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-11">
            <div class="card">

                <div class="card-header">Product Receipt</div>

                <div class="card-body">

                    {{-- <!-- FILTER -->
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <label class="form-label mb-0">Pilih Satuan:</label>
                        <select id="filterSat" class="form-select w-auto">
                            <option value="primer">Primer</option>
                            <option value="sekunder">Sekunder</option>
                            <option value="tritier">Tritier</option>
                        </select>
                    </div> --}}

                    <!-- HEADER + OTP -->
                    <div class="mb-3 d-flex justify-content-between align-items-start">

                        <!-- LEFT -->
                        <div>
                            <div style="font-size:18px"><strong>No. PO</strong> : <span id="noPo">-</span></div>
                            <div style="font-size:18px"><strong>Tgl Kirim</strong> : <span id="tglKirim">-</span></div>
                        </div>

                        <!-- RIGHT -->
                        <div style="min-width:250px">
                            @if(!isset($otp) || !$otp)

                                <button id="btnOpenOtp" class="btn btn-primary w-100 mb-2">
                                    Send OTP
                                </button>

                                <div id="otpSection" style="display:none;">
                                    <select id="emailSelect" class="form-select mb-2">
                                        <option value="">-- Pilih Email --</option>
                                    </select>

                                    <button id="btnSendOtp" class="btn btn-warning w-100 mb-2">
                                        Kirim OTP
                                    </button>

                                    <input type="text" id="otpInput"
                                        class="form-control mb-2"
                                        placeholder="Masukkan OTP">

                                    <button id="btnVerifyOtp" class="btn btn-success w-100">
                                        Verify OTP
                                    </button>
                                </div>
                            @endif


                            <div id="approvalInfo" class="mt-2 text-success" style="{{ isset($otp) && $otp ? '' : 'display:none;' }}">
                                <div><strong>Approved By:</strong> <span id="approvedEmail"></span></div>
                                <div><strong>Approved At:</strong> <span id="approvedAt"></span></div>

                                <!--Resend Email-->
                                <button id="btnResendEmail"
                                    class="btn w-100 mt-2 fw-bold">
                                    Resend Email
                                </button>
                            </div>
                            <br>
                        </div>
                    </div>

                    <!-- TABLE -->
                    <table id="tableSuratJalan" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Nama Type</th>
                                <th>Qty Jual</th>
                                <th>Sat Jual</th>
                                <th>Nama Customer</th>
                                <th>Surat Pesanan</th>
                                <th>Nama Exp</th>
                                <th>Truk NoPol</th>
                            </tr>
                        </thead>
                    </table>

                </div>
            </div>
        </div>
    </div>
</div>

@if(isset($otp) && $otp)
<script>
    $(function(){
        $('#approvalInfo').show();
        $('#approvedEmail').text("{{ $otp->Email }}");
        $('#approvedAt').text("{{ $otp->ApprovedAt }}");
    });
</script>
@endif

{{-- Inject data --}}
<script>
    window.appData = {
        idPengiriman: "{{ $id_pengiriman }}",
        noPo: "{{ $no_po }}"
    };
</script>

<script src="{{ asset('js/suratJalanPesanan.js') }}"></script>

@endsection
