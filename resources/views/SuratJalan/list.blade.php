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
        <div class="card-header">List Surat Jalan Belum Verifikasi</div>

        <div class="card-body">
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row">

                        <div class="col-md-4">
                            <label>Cari</label>
                            <input type="text" id="searchText"
                                class="form-control"
                                placeholder="Nomor PO / Nama Barang">
                        </div>

                        <div class="col-md-3">
                            <label>Tanggal Awal</label>
                            <input type="date" id="dateFrom" class="form-control">
                        </div>

                        <div class="col-md-3">
                            <label>Tanggal Akhir</label>
                            <input type="date" id="dateTo" class="form-control">
                        </div>

                        <div class="col-md-2 d-flex align-items-end">
                            <div class="d-flex w-100 gap-2">

                                <button id="btnFilter" class="btn btn-primary flex-fill">
                                    Cari
                                </button>

                                <button id="btnReset" class="btn btn-warning w-100">
                                    Muat Ulang
                                </button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <table id="tableList" class="table table-striped">
                <thead>
                    <tr>
                        <th>Nomor PO</th>
                        <th>Tanggal Kirim</th>
                        <th>Nama Barang</th>
                        <th>Status</th>
                        <th>Aksi</th>
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
//list
let table = $('#tableList').DataTable({
    ajax: {
        url: '/SuratJalan/list-data',
        data: function (d) {
            d.search = $('#searchText').val();
            d.date_from = $('#dateFrom').val();
            d.date_to = $('#dateTo').val();
        }
    },

    searching: false,
    order: [],
    language: {
        lengthMenu: "_MENU_ baris per halaman"
    },

    columns: [
        { data: 'No_PO', width: '200px' },
        {
            data: 'TglKirim',
            className: 'text-start',
            render: function (data) {
                if (!data) return '-';

                let date = new Date(data);

                let day = String(date.getDate()).padStart(2, '0');
                let month = String(date.getMonth() + 1).padStart(2, '0');
                let year = date.getFullYear();

                return `${month}-${day}-${year}`;
            }
        },
        { data: 'NamaType' },
        {
            data: null,
            render: function (data) {

                // ACCCUSTOMER = NULL
                if (Number(data.CanProductReceipt) === 1) {
                    return `
                        <span class="badge bg-warning text-dark">
                            Belum Diterima
                        </span>
                    `;
                }

                // ACCCUSTOMER = False / 0
                return `
                    <span class="badge bg-danger">
                        Pending
                    </span>
                `;
            }
        },
        {
            data: null,
            render: function (data) {

                let button = `
                    <button class="btn btn-primary btn-sm btn-detail"
                        data-id="${data.IDPengiriman}">
                        Lihat
                    </button>
                `;

                // Product Receipt hanya jika ACCCUSTOMER = NULL
                if (Number(data.CanProductReceipt) === 1) {
                    button += `
                        <a
                            href="/SuratJalan/product-receipt/${data.IDPengiriman}"
                            target="_blank"
                            class="btn btn-warning btn-sm">
                            Product Receipt
                        </a>
                    `;
                }
                return button;
            }
        }
    ]
});

// ===============================
// FILTER
// ===============================

let searchTimer;

// Tombol Cari
$('#btnFilter').on('click', function () {
    table.ajax.reload(null, false);
});


// Auto Search
$('#searchText').on('input', function () {

    clearTimeout(searchTimer);

    searchTimer = setTimeout(function () {

        table.ajax.reload(null, false);

    }, 500);
});


// Muat Ulang
$('#btnReset').on('click', function () {

    $('#searchText').val('');
    $('#dateFrom').val('');
    $('#dateTo').val('');

    table.ajax.reload(null, false);
});

// detail
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
            const formatSatuan = (satuan) => {
            const satuanMap = {
                'TABUNG': 'TABUNG',
                'SET': 'SET',
                'KGM': 'KILOGRAM',
                'RP': 'RP',
                'BALL': 'BALL',
                'LBR': 'LEMBAR',
                'PC': 'POTONG',
                'YARDS': 'YARD',
                'MTR²': 'METER PERSEGI',
                'ROLL': 'ROLL',
                'DRUM': 'DRUM',
                'LJR': 'LONJOR',
                'MTR': 'METER',
                'UNIT': 'UNIT'
            };

            if (!satuan) return '-';

            return satuanMap[satuan.trim()] ?? satuan;
        };

            // TABLE
            res.data.forEach(item => {
                let qty = item.Qty ?? 0;
                let formattedQty = Number(qty).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });

                let satJual = formatSatuan(item.SatJual);

                rows += `
                    <tr>
                        <td>${item.NamaType ?? '-'}</td>
                        <td>${formattedQty}</td>
                        <td>${satJual ?? '-'}</td>
                    </tr>
                `;
            });

            let tgl = '-';
            if (first.TglKirim) {
                let date = new Date(first.TglKirim);

                let day = String(date.getDate()).padStart(2, '0');
                let month = String(date.getMonth() + 1).padStart(2, '0');
                let year = date.getFullYear();

                tgl = `${day}-${month}-${year}`;
            }

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
