
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Title--}}
    <title>Public KRR</title>

    {{-- Logo --}}
    <link rel="icon" type="image/png" href="{{ asset('images/KRR.png') }}">

    <link href="{{ asset('css/DokumenSJ.css') }}" rel="stylesheet">
</head>

<body>

<div class="container">

    @if(!isset($header))
        <div class="alert alert-danger text-center">
            Data tidak ditemukan
        </div>
        @php return; @endphp
    @endif

    {{-- HEADER --}}
    <div class="text-center mb-4">
        <h4 class="fw-bold verified-title">
            Document Verified
            <span class="d-flex align-items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24">
                    <path fill="#28a745"
                        d="M19 9.09V6c0-.55-.45-1-1-1h-3.09L12.7 2.79a.996.996 0 0 0-1.41 0L9.08 5H5.99c-.55 0-1 .45-1 1v3.09L2.78 11.3a.996.996 0 0 0 0 1.41l2.21 2.21v3.09c0 .55.45 1 1 1h3.09l2.21 2.21c.2.2.45.29.71.29s.51-.1.71-.29l2.21-2.21h3.09c.55 0 1-.45 1-1v-3.09l2.21-2.21a.996.996 0 0 0 0-1.41l-2.21-2.21z"/>
                    <path fill="#ffffff"
                        d="m11 12.59-1.29-1.3-1.42 1.42 2.71 2.7 4.71-4.7-1.42-1.42z"/>
                </svg>
            </span>
        </h4>

        <p>No Surat Jalan : <b>{{ $header->IDPengiriman }}</b></p>

        <p>
            Tanggal :
            {{ \Carbon\Carbon::parse($header->TglKirim)->format('d-m-Y') }}
        </p>
    </div>

    {{-- INFO --}}
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body">

            <p>
                <strong>Transporter :</strong>
                {{ $header->NamaExpeditor ?? '-' }}
            </p>

            <p>
                <strong>No Truk :</strong>
                {{ $header->TrukNopol ?? '-' }}
            </p>

            <p class="mb-0">
                <strong>Atas Permintaan :</strong>
            </p>

            <p class="fw-bold">
                {{ $header->NamaCust ?? '-' }}
            </p>

            <p>
                {{ $header->AlamatCustomer ?? '-' }}
            </p>

            <hr>

            <p>
                <strong>No PO :</strong>
                {{ $header->No_PO ?? '-' }}
            </p>

            <p class="mb-0">
                Dikirim Kepada :
            </p>

            <p class="fw-bold">
                {{ $header->NamaCust ?? '-' }}
            </p>

            <p>
                {{ $header->AlamatKirimDO ?? '-' }}
            </p>

        </div>
    </div>

    {{-- TABLE --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">

            <table class="table table-bordered mb-0">

                <thead class="table-light text-center">
                    <tr>
                        <th>NO</th>
                        <th>NAMA BARANG</th>
                        <th>JUMLAH</th>
                        <th>SATUAN</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($data as $i => $item)
                    <tr>
                        <td class="text-center">
                            {{ $i + 1 }}
                        </td>

                        <td class="text-center">
                            {{ $item->NamaType ?? '-' }}
                        </td>

                        <td class="text-center">
                            {{ number_format($item->QtyJual ?? 0) }}
                        </td>

                        <td class="text-center">
                            {{ $item->SatJual ?? '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center">
                            Tidak ada data
                        </td>
                    </tr>
                    @endforelse

                </tbody>

            </table>

        </div>
    </div>

    {{-- FOOTER INFO --}}
    <div class="card mt-4 shadow-sm border-0">
        <div class="card-body">

            {{-- DIKELUARKAN --}}
            @if ($jenisAcc == '')

            @else

            @endif
            <p class="fw-bold mb-1">
                Dikeluarkan Oleh <i>Logistic Manager</i>
            </p>

            <p class="fw-bold mb-0">
                {{ $header->NamaExpeditor ?? 'BELUM ADA EXPEDITOR' }}
            </p>

            <p class="fw-bold">
                {{ \Carbon\Carbon::parse($header->TglKirim)->format('d-m-Y') }}
            </p>

            <br>

            {{-- DIKIRIM --}}
            <p class="fw-bold mb-1">
                <i>Dikirim Oleh</i>
                {{ $header->PengirimNama ?? 'BELUM DIKIRIM' }}
            </p>

            <p class="fw-bold">
                {{ $header->TglPengirim ?? '-' }}
            </p>

            <br>

            {{-- DITERIMA --}}
            <p class="fw-bold mb-1">
                <i>Diterima Oleh</i>
                {{ $header->NamaCust ?? 'BELUM DITERIMA' }}
            </p>

            <p class="fw-bold">
                {{ $header->TglApp ?? '-' }}
            </p>

        </div>
    </div>

</div>

</body>

