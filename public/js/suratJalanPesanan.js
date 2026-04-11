jQuery(function ($) {

//#region Variables
    let idPengiriman = window.appData?.idPengiriman;
    let noPo = window.appData?.noPo;

    if (!idPengiriman) {
        console.error("ID Pengiriman tidak ditemukan");
        return;
    }

    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // =============================
    // DATATABLE
    // =============================
    let table = $('#tableSuratJalan').DataTable({
        processing: true,
        searching: false,
        lengthChange: false,
        ajax: {
            url: "/SuratJalan-data",
            data: function (d) {
                d.no_po = noPo; // hanya untuk ambil data tabel
            },
            dataSrc: function (json) {
                $('#noPo').text(json.header?.No_PO ?? '-');
                $('#tglKirim').text(formatDate(json.header?.TglKirim));
                return json.data;
            }
        },
        columns: [
            { data: 'NamaType' },
            { data: 'QtyJual' },
            { data: 'SatJual' },
            { data: 'NamaCust' },
            { data: 'SuratPesanan' },
            { data: 'NamaExpeditor' },
            { data: 'TrukNopol' }
        ]
    });

//#endregion

//#region function

    function formatDate(dateString) {
        if (!dateString) return '-';

        let date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        });
    }

    function formatDateTime(dateString) {
        if (!dateString) return '-';

        let date = new Date(dateString);
        return date.toLocaleString('id-ID', {
            timeZone: 'Asia/Jakarta'
        });
    }

//#endregion

//#region Event Listener

    // =============================
    // OTP FLOW
    // =============================

    // STEP 1: Load email
    $('#btnOpenOtp').click(function () {
        $('#btnOpenOtp').prop('disabled', true).text('Loading...');

        $.get('/SuratJalan/get-emails/' + idPengiriman)
            .done(function (res) {
                let html = '<option value="">-- pilih email --</option>';

                res.forEach(e => {
                    html += `<option value="${e.Email}">${e.Email}</option>`;
                });

                $('#emailSelect').html(html);
                $('#otpSection').slideDown();

            })
            .fail(function () {
                alert('Gagal mengambil email');
            })
            .always(function () {
                $('#btnOpenOtp').prop('disabled', false).text('Send OTP');
            });

    });

    // STEP 2: Kirim OTP
    $('#btnSendOtp').click(function () {
        let email = $('#emailSelect').val();

        if (!email) {
            alert('Pilih email terlebih dahulu');
            return;
        }

        $('#btnSendOtp').prop('disabled', true).text('Mengirim...');

        $.post('/SuratJalan/send-otp', {
            id_pengiriman: idPengiriman,
            email: email
        })
        .done(function () {
            alert('OTP dikirim ke ' + email);
        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.error ?? 'Gagal kirim OTP');
        })
        .always(function () {
            $('#btnSendOtp').prop('disabled', false).text('Kirim OTP');
        });

    });

    // STEP 3: Verify OTP
    $('#btnVerifyOtp').click(function () {
        let email = $('#emailSelect').val();
        let otp = $('#otpInput').val();

        if (!email) {
            alert('Pilih email terlebih dahulu');
            return;
        }

        if (!otp) {
            alert('Masukkan OTP');
            return;
        }

        $('#btnVerifyOtp')
            .prop('disabled', true)
            .html('Verifying...');

        $.post('/SuratJalan/verify-otp', {
            id_pengiriman: idPengiriman,
            email: email,
            otp: otp
        })
        .done(function (res) {

            // simpan id surat jalan dari backend
            window.idSuratJalan = res.id_surat_jalan;

            // tampilkan section konfirmasi
            $('#otpSection').hide();

            // reset modal state
            $('#stepKonfirmasi').show();
            $('#stepQty').hide();
            $('#qtyInput').val('');

            // tampilkan modal
            $('#modalKonfirmasi').modal('show');

        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.error ?? 'OTP tidak valid');

            $('#btnVerifyOtp')
                .prop('disabled', false)
                .html('Verify OTP');
        });
    });


    // PILIH YA
    $('#btnYa').click(function () {
        $('#btnYa').prop('disabled', true).text('Processing...');

        $.post('/SuratJalan/confirm-approval', {
            id_surat_jalan: window.idSuratJalan,
            is_sesuai: 1,
            email: $('#emailSelect').val()
        })
        .done(function () {
            alert('Approved & Email berhasil dikirim');

            $('#modalKonfirmasi').modal('hide');

            $('#btnOpenOtp').hide();
            $('#otpSection').hide();

            $('#approvalInfo').show();

            $('#approvedEmail').text($('#emailSelect').val());
            $('#approvedAt').text(formatDateTime(new Date()));
        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.error ?? 'Terjadi kesalahan');
            $('#btnYa').prop('disabled', false).text('Ya');
        });
    });

    // PILIH TIDAK
    $('#btnTidak').click(function () {
        console.log('Klik Tidak');

        $('#stepKonfirmasi').hide();
        $('#stepQty').fadeIn();
    });

    // SUBMIT QTY
    $('#btnSubmitQty').click(function () {

    let qty = parseFloat($('#qtyInput').val());

    if (!qty || qty <= 0) {
        alert('Qty harus diisi dengan benar');
        return;
    }

    // VALIDASI QTY INPUT VS QTY JUAL
    let rowData = table.row(0).data();

    if (!rowData) {
        alert('Data tabel belum siap');
        return;
    }

    let qtyJual = parseFloat(rowData.QtyJual);

    if (qty > qtyJual) {
        alert(`Qty tidak boleh melebihi Qty Jual (${qtyJual})`);
        return;
    }

    $('#btnSubmitQty').prop('disabled', true).text('Processing...');
        $.post('/SuratJalan/confirm-approval', {
            id_surat_jalan: window.idSuratJalan,
            is_sesuai: 0,
            qty_temp: qty,
            email: $('#emailSelect').val()
        })
        .done(function () {
            alert('Approved tanpa email');

            $('#modalKonfirmasi').modal('hide');

            $('#btnOpenOtp').hide();
            $('#otpSection').hide();

            $('#approvalInfo').show();

            $('#approvedEmail').text($('#emailSelect').val());
            $('#approvedAt').text(formatDateTime(new Date()));
        })
        .fail(function (xhr) {
            console.log(xhr.responseJSON);
            alert(xhr.responseJSON?.error ?? 'Terjadi kesalahan');
            $('#btnSubmitQty').prop('disabled', false).text('Submit');
        });
    });

    $('#btnResendEmail').click(function () {

        let email = $('#approvedEmail').text().trim();
        let idPengiriman = window.appData?.idPengiriman;

        if (!email) {
            alert('Email approval tidak ditemukan');
            return;
        }

        $('#btnResendEmail').prop('disabled', true).text('Sending...');

        $.post('/SuratJalan/resend-email', {
            id_pengiriman: idPengiriman,
            email: email
        })
        .done(function (res) {
            alert(res.message || 'Email berhasil dikirim');
        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.message ?? 'Gagal kirim email');
        })
        .always(function () {
            $('#btnResendEmail').prop('disabled', false).text('Resend Email');
        });

    });
//#endregion


});
