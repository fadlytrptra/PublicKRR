@extends('layouts.app')

@section('content')

<div class="container">
    <h4 class="mb-4">List Surat Jalan Sudah Verifikasi</h4>

    <!-- FILTER -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('DokumenSJ.search') }}">
                <div class="row">

                    <div class="col-md-4">
                        <label>Cari</label>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="No Surat Jalan / Nama Perusahaan"
                               value="{{ request('search') }}">
                    </div>

                    <div class="col-md-3">
                        <label>Tanggal Awal</label>
                        <input type="date"
                               name="date_from"
                               class="form-control"
                               value="{{ request('date_from') }}">
                    </div>

                    <div class="col-md-3">
                        <label>Tanggal Akhir</label>
                        <input type="date"
                               name="date_to"
                               class="form-control"
                               value="{{ request('date_to') }}">
                    </div>

                   <div class="col-md-2 d-flex align-items-end">
                        <div class="d-flex w-100 gap-2">

                            <button type="submit" class="btn btn-success flex-fill">
                                Cari
                            </button>

                            <a href="{{ route('DokumenSJ.index') }}"
                            class="btn btn-warning flex-fill d-flex align-items-center justify-content-center text-nowrap">
                                Muat Ulang
                            </a>

                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABLE -->
    <table class="table table-bordered">
        <thead class="table-light text-center">
            <tr>
                <th>No</th>
                <th>No Surat Jalan</th>
                <th>Tanggal</th>
                <th>Nama Perusahaan</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
            @forelse($list as $i => $item)
            <tr>
                <td class="text-center">{{ $i + 1 }}</td>
                <td class="text-center">{{ $item->IDPengiriman }}</td>
                <td class="text-center">
                    {{ \Carbon\Carbon::parse($item->TglKirim)->format('d-m-Y') }}
                </td>
                <td class="text-center">{{ $item->NamaCust }}</td>
                <td class="text-center">
                    <a href="{{ route('DokumenSJ.show', $item->encrypted_id) }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="btn btn-sm btn-success">
                        Lihat Dokumen
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">Tidak ada data</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
