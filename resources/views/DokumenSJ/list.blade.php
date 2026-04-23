@extends('layouts.app')

@section('content')

<div class="container">
    <h4 class="mb-4">Daftar Surat Jalan</h4>

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
                <td class="text-center">{{ \Carbon\Carbon::parse($item->TglKirim)->format('d-m-Y') }}</td>
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
