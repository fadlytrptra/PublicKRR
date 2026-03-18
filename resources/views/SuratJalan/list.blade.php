@extends('layouts.app')

@section('content')

<div class="container">
    <div class="card">
        <div class="card-header">List Surat Jalan</div>

        <div class="card-body">
            <table id="tableList" class="table table-striped">
                <thead>
                    <tr>
                        <th>No PO</th>
                        <th>Nama Customer</th>
                        <th>Nama Type</th>
                        <th>Action</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<script>
$('#tableList').DataTable({
    ajax: '/SuratJalan/list-data',
    columns: [
        { data: 'No_PO' },
        { data: 'NamaCust' },
        { data: 'NamaType' },
        {
            data: 'IDPengiriman',
            render: function (data, type, row) {
                return `<a href="/SuratJalan/${data}" class="btn btn-primary btn-sm">View</a>`;
            }
        }
    ]
});
</script>

@endsection
