@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 RDZMobilePaddingLR0">
                <div class="card">
                    <div class="card-header">Product Receipt</div>
                    <div class="card-body">
                        <div style="overflow: auto;">
                            {{-- <h1>silahkan scan qr barcode dari kami</h1> --}}
                            <a href="{{ route('SuratJalan.index') }}" class="btn btn-primary">
                                List Surat Jalan Belum Verifikasi
                            </a>
                            <a href="{{ route('DokumenSJ.index') }}" class="btn btn-success ms-2">
                                List Surat Jalan Sudah Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
