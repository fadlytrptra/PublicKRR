jQuery(function ($) {

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

        $('#btnVerifyOtp').prop('disabled', true).text('Verifying...');

        $.post('/SuratJalan/verify-otp', {
            id_pengiriman: idPengiriman,
            email: email,
            otp: otp
        })
        .done(function (res) {
            alert('Approved berhasil');

            // tampilkan info
            $('#approvalInfo').show();
            $('#approvedEmail').text(res.email ?? email);
            $('#approvedAt').text(formatDateTime(res.approved_at));

            // sembunyikan OTP section
            $('#otpSection').hide();

            // hilangkan tombol tanpa refresh
            $('#btnOpenOtp').hide();
            $('#btnSendOtp').hide();
            $('#btnVerifyOtp').hide();

            $('#emailSelect').prop('disabled', true);
            $('#otpInput').prop('disabled', true);

        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.error ?? 'OTP tidak valid');
        })
        .always(function () {
            $('#btnVerifyOtp').prop('disabled', false).text('Verify OTP');
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

    // =============================
    // HELPER
    // =============================

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

});
