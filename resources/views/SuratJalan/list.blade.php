@extends('layouts.app')

@section('content')

<style>
    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    #tableList th,
    #tableList td {
        padding-left: 12px;
        padding-right: 12px;
    }
</style>


<div class="container">
    <div class="card">
        <div class="card-header">List Surat Jalan</div>

        <div class="card-body">
            <table id="tableList" class="table table-striped">
                <thead>
                    <tr>
                        <th>Nomor PO</th>
                        <th>Tanggal Kirim</th>
                        <th>Nama Barang</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDetail" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Detail Surat Jalan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <!-- INFORMASI SURAT -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label>Nomor PO</label>
                        <input type="text" id="No_PO" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>ID Pengiriman</label>
                        <input type="text" id="IDPengiriman" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Surat Pesanan</label>
                        <input type="text" id="SuratPesanan" class="form-control" readonly>
                    </div>
                    <div class="col-md-3">
                        <label>Tanggal Kirim</label>
                        <input type="text" id="TglKirim" class="form-control" readonly>
                    </div>
                </div>

                 <!-- TABLE -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nama Barang</th>
                                <th>Quantity</th>
                                <th>Satuan</th>
                            </tr>
                        </thead>
                        <tbody id="detailBody"></tbody>
                    </table>
                </div>

                <!-- CUSTOMER -->
                {{-- <div class="card mb-3">
                    <div class="card-header">Customer</div>
                    <div class="card-body row">
                        <div class="col-md-6">
                            <label>Nama</label>
                            <input type="text" id="NamaCust" class="form-control" readonly>
                        </div>
                        <div class="col-md-12 mt-2">
                            <label>Alamat</label>
                            <input type="text" id="AlamatCustomer" class="form-control" readonly>
                        </div>
                    </div>
                </div> --}}

                <!-- EXPEDITOR -->
                <div class="card mb-3">
                    <div class="card-header">Pengiriman</div>
                    <div class="card-body row">
                        <div class="col-md-4">
                            <label>Nama Expeditor</label>
                            <input type="text" id="NamaExpeditor" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>No Polisi</label>
                            <input type="text" id="TrukNopol" class="form-control" readonly>
                        </div>
                        <div class="col-md-4">
                            <label>Supir</label>
                            <input type="text" id="Supir" class="form-control" readonly>
                        </div>
                        <div class="mt-2">
                            <label>Nama PT</label>
                            <input type="text" id="NamaPerusahaan" class="form-control" readonly>
                        </div>
                        <div class="mt-2">
                            <label>Alamat Kirim</label>
                            <input type="text" id="AlamatKirimCustomer" class="form-control" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#tableList').DataTable({
    ajax: '/SuratJalan/list-data',
    columns: [
        { data: 'No_PO', width: '200px' },
        { data: 'TglKirim', className: 'text-start' },
        { data: 'NamaType' },
        {
            data: 'IDPengiriman',
            render: function (data) {
                return `
                    <button class="btn btn-primary btn-sm btn-detail" data-id="${data}">
                        View
                    </button>
                `;
            }
        }
    ]
});

$(document).on('click', '.btn-detail', function () {
    let id = $(this).data('id');

    $.ajax({
        url: `/SuratJalan/detail-modal/${id}`,
        type: 'GET',
        success: function (res) {

            if (!res.data || res.data.length === 0) {
                alert('Data tidak ditemukan');
                return;
            }

            let first = res.data[0];
            let rows = '';

            // TABLE
            res.data.forEach(item => {
                rows += `
                    <tr>
                        <td>${item.NamaType ?? '-'}</td>
                        <td>${item.Qty ?? 0}</td>
                        <td>${item.SatJual ?? '-'}</td>
                    </tr>
                `;
            });

            let tgl = first.TglKirim ? first.TglKirim.split(' ')[0] : '-';

            $('#detailBody').html(rows);

            // ISI FORM
            $('#No_PO').val(first.No_PO ?? '-');
            $('#IDPengiriman').val(first.IDPengiriman ?? '-');
            $('#SuratPesanan').val(first.SuratPesanan ?? '-');
            $('#TglKirim').val(tgl);

            $('#NamaCust').val(first.NamaCust ?? '-');
            $('#JnsCust').val(first.JnsCust ?? '-');
            $('#AlamatCustomer').val(first.AlamatCustomer ?? '-');

            $('#NamaExpeditor').val(first.NamaExpeditor ?? '-');
            $('#TrukNopol').val(first.TrukNopol ?? '-');
            $('#Supir').val(first.NamaSupir ?? first.NamaSatpam ?? '-');

            $('#NamaPerusahaan').val(first.NamaPerusahaan ?? '-');

            $('#NoContainer').val(first.NoContainer ?? '-');
            $('#NoSeal').val(first.NoSeal ?? '-');
            $('#JnsPengiriman').val(first.JnsPengiriman ?? '-');

            $('#AlamatKirimCustomer').val(first.AlamatKirimCustomer ?? '-');
            $('#AlamatKirimDO').val(first.AlamatKirimDO ?? '-');

            $('#Ket').val(first.Ket ?? '-');

            // SHOW MODAL
            let modal = new bootstrap.Modal(document.getElementById('modalDetail'));
            modal.show();
        }
    });
});
</script>

@endsection
