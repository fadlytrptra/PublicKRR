jQuery(function ($) {

//#region Variables
    let idPengiriman = window.appData?.idPengiriman;
    let noPo = window.appData?.noPo;
    let selectedFiles = [];
    let selectedFilesACC = [];
    let activeUploadMode = null;
    let stream = null;
    let fileFoto =document.getElementById('fileFoto');
    let cameraInput =document.getElementById('cameraInput');
    let btnCameraFoto =document.getElementById('btnCameraFoto');
    let cameraModal =document.getElementById('cameraModal');
    let cameraVideo =document.getElementById('cameraVideo');
    let cameraCanvas =document.getElementById('cameraCanvas');
    let btn_clearPhotos = document.getElementById("btn_clearPhotos");

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
                d.idPengiriman = idPengiriman,
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
            {
                data: 'QtyJual',
                render: function (data, type, row) {
                    if (data == null) return '-';

                    return parseFloat(data).toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            },
            {
                data: 'SatJual',
                render: function (data, type, row) {
                    if (!data) return '-';

                    const formatSatuan = {
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

                    return formatSatuan[data.trim()] ?? data;
                }
            },
            { data: 'NamaCust' },
            { data: 'SuratPesanan' },
            { data: 'NamaExpeditor' },
            { data: 'TrukNopol' }
        ]
    });

    // =============================
    // INIT STATE (dari backend)
    // =============================
    let otpData = window.otpData || null;

    if (otpData) {

        $('#approvalInfo').show();

        let approvedBy = otpData.Email ?? otpData.Phone ?? '-';
        let approvedAt = otpData.ApprovedAt;
        let createdAt = otpData.CreatedAt;

        if (approvedAt) {
            // Approve (ACC)
            $('#rowStatus').hide();

            $('#labelApprovedBy').text('Approved By:');
            $('#labelApprovedAt').text('Approved At:');

            $('#approvedEmail').text(approvedBy);
            $('#approvedAt').text(formatDateTime(approvedAt));

            $('#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt')
                .removeClass('text-danger')
                .addClass('text-success');

            $('#btnResendEmail')
                .prop('disabled', false)
                .removeClass('btn-secondary')
                .addClass('btn-primary')
                .text('Resend Email');

        } else {
            // PASCA KIRIM
            $('#rowStatus').show();
            $('#statusApproval').text('Requested');

            $('#labelApprovedBy').text('No HP:');
            $('#labelApprovedAt').text('Tanggal:');

            $('#approvedEmail').text(approvedBy);
            $('#approvedAt').text(formatDateTime(createdAt));

            $('#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt')
                .removeClass('text-success')
                .addClass('text-danger');

            $('#btnResendEmail')
                .prop('disabled', true)
                .removeClass('btn-primary')
                .addClass('btn-secondary')
                .text('Pasca Kirim');
        }
    }

    if (window.wajibUploadFoto) {
        $('#stepKonfirmasi').hide();
        $('#stepQty').hide();
        $('#stepFoto').show();

        const modal = new bootstrap.Modal(
            document.getElementById('modalKonfirmasi'),
            {
                backdrop: 'static',
                keyboard: false
            }
        );

        modal.show();

        $('.btn-close').hide();
    }

//#endregion

//#region function

    function formatDate(dateString) {
        if (!dateString) return '-';

        let date = new Date(dateString);
        return date.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'long',
            year: 'numeric'
        });
    }

    function formatDateTime(dateInput) {
        if (!dateInput) return '-';

        let date;

        // kalau string dari DB
        if (typeof dateInput === 'string') {
            let clean = dateInput.split('.')[0];
            date = new Date(clean.replace(' ', 'T'));
        }
        // kalau object Date
        else {
            date = dateInput;
        }

        if (isNaN(date)) return '-';

        let day = String(date.getDate()).padStart(2, '0');
        let month = String(date.getMonth() + 1).padStart(2, '0');
        let year = date.getFullYear();

        let hours = String(date.getHours()).padStart(2, '0');
        let minutes = String(date.getMinutes()).padStart(2, '0');
        let seconds = String(date.getSeconds()).padStart(2, '0');

        return `${day}/${month}/${year}, ${hours}:${minutes}:${seconds}`;
    }

    function uploadFoto(files)
    {
        let formData = new FormData();

        formData.append(
            'id_surat_jalan',
            window.idSuratJalan
        );

        for(let file of files){
            formData.append(
                'pictures[]',
                file
            );
        }

        $.ajax({
            url: '/SuratJalan/upload-foto',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function(res){
                alert(res.message);

            },

            error: function(xhr){
                alert(
                    xhr.responseJSON?.message ??
                    'Upload foto gagal'
                );
            }
        });
    }

    function renderPreview() {
        $('#fotoPreview').empty();

        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();

            reader.onload = function (e) {
                $('#fotoPreview').append(`
                    <div class="preview-item">
                        <img src="${e.target.result}" class="preview-image"
                            data-src="${e.target.result}">
                        <button
                            type="button"
                            class="delete-btn"
                            data-index="${index}">
                            ×
                        </button>
                    </div>
                `);
            };

            reader.readAsDataURL(file);
        });

        $('#jumlahFotoDipilih').text(
            selectedFiles.length + ' foto dipilih'
        );
    }

    function renderPreviewACC() {
        $('#fotoPreviewACC').empty();

        selectedFilesACC.forEach((file, index) => {

            const reader = new FileReader();

            reader.onload = function (e) {

                $('#fotoPreviewACC').append(`
                    <div class="preview-item">
                        <img
                            src="${e.target.result}"
                            class="preview-image"
                            data-src="${e.target.result}"
                        >

                        <button
                            type="button"
                            class="delete-btn-acc"
                            data-index="${index}">
                            ×
                        </button>
                    </div>
                `);
            };

            reader.readAsDataURL(file);
        });

        $('#jumlahFotoDipilihACC').text(
            selectedFilesACC.length + ' foto dipilih'
        );
    }

//#endregion

//#region Event Listener

        // =============================
        // LIMIT INPUT QTY
        // =============================
        $('#qtyInput').on('input', function () {
            let rowData = table.row(0).data();
            if (!rowData) return;

            let qtyJual = parseFloat(rowData.QtyJual);
            if (isNaN(qtyJual)) return;

            let maxQty = qtyJual * 2;
            let maxDigits = Math.floor(maxQty).toString().length;

            let value = $(this).val();

            // ambil angka saja
            value = value.replace(/\D/g, '');

            // batas digit
            if (value.length > maxDigits) {
                value = value.substring(0, maxDigits);
            }

            // convert ke number
            let numericValue = parseInt(value || 0);

            // maksimum 2x qty jual
            if (numericValue > maxQty) {
                numericValue = maxQty;
            }

            // format ribuan
            let formattedValue = numericValue.toLocaleString('en-US');

            $(this).val(numericValue === 0 ? '' : formattedValue);
        });

    // =============================
    // OTP FLOW
    // =============================

    // STEP 1: Load email
    let contacts = [];
    $('#btnOpenOtp').click(function () {

        $('#btnOpenOtp')
            .prop('disabled', true)
            .text('Loading...');

        $.get('/SuratJalan/get-contacts/' + idPengiriman)
            .done(function (res) {
                contacts = res;

                $('#contactType').val('');
                $('#contactSelect').html(
                    '<option value="">-- Pilih Kontak --</option>'
                );

                $('#otpSection').slideDown();
            })

            .fail(function () {
                alert('Gagal mengambil kontak');
            })

            .always(function () {
                $('#btnOpenOtp')
                    .prop('disabled', false)
                    .text('Konfirmasi Product Receipt');
            });
    });

    $('#contactType').change(function () {
        let type = $(this).val();
        let html =
            '<option value="">-- Pilih Kontak --</option>';

        contacts.forEach(item => {
            if (type === 'email' && item.Email) {
                html += `
                    <option
                        value="${item.Email}"
                        data-userid="${item.IdUser}"
                        data-nama="${item.NamaUser}">
                        ${item.NamaUser} - ${item.Email}
                    </option>
                `;
            }

            if (type === 'sms' && item.Phone) {
                html += `
                    <option
                        value="${item.Phone}"
                        data-userid="${item.IdUser}"
                        data-nama="${item.NamaUser}">
                        ${item.NamaUser} - ${item.Phone}
                    </option>
                `;
            }
        });

        $('#contactSelect').html(html);
    });

    // STEP 2: Kirim OTP
    $('#btnSendOtp').click(function () {
        let type = $('#contactType').val();
        let value = $('#contactSelect').val();
        let selected = $('#contactSelect option:selected');

        if (!type) {
            alert('Pilih metode OTP');
            return;
        }

        if (!value) {
            alert('Pilih kontak terlebih dahulu');
            return;
        }

        $('#btnSendOtp')
            .prop('disabled', true)
            .text('Mengirim...');

        let payload = {
            id_pengiriman: idPengiriman,
            otp_method: type,
            id_user: selected.data('userid'),
        };

        if (type === 'email') {
            payload.email = value;
        } else {
            payload.phone = value;
        }

        $.post('/SuratJalan/send-otp', payload)

            .done(function () {
                alert('OTP berhasil dikirim');
            })

            .fail(function (xhr) {
                alert(
                    xhr.responseJSON?.error ??
                    xhr.responseJSON?.message ??
                    'Gagal kirim OTP'
                );
            })

            .always(function () {
                $('#btnSendOtp')
                    .prop('disabled', false)
                    .text('Send OTP');
            });
    });

    // STEP 3: Verify OTP
    $('#btnVerifyOtp').click(function () {
        let type = $('#contactType').val();
        let value = $('#contactSelect').val();
        let otp = $('#otpInput').val();
        let selected = $('#contactSelect option:selected');

        if (!value) {
            alert('Pilih kontak terlebih dahulu');
            return;
        }

        if (!otp) {
            alert('Masukkan OTP');
            return;
        }

        let payload = {
            id_pengiriman: idPengiriman,
            otp: otp,
            id_user: selected.data('userid'),
        };

        if (type === 'email') {
            payload.email = value;
        } else {
            payload.phone = value;
        }

        let $btn = $('#btnVerifyOtp');

        $btn
            .prop('disabled', true)
            .html('Verifying...');

        $.post('/SuratJalan/verify-otp', payload)

            .done(function (res) {

                window.idSuratJalan =
                    res.id_surat_jalan;

                window.otpId =
                    res.otp_id;

                $('#otpInput').val('');
                $('#otpSection').hide();

                $('#stepKonfirmasi').show();
                $('#stepQty').hide();
                $('#stepFoto').hide();
                $('#qtyInput').val('');

                $('#modalKonfirmasi').modal('show');
            })

            .fail(function (xhr) {
                alert(
                    xhr.responseJSON?.error ??
                    'OTP tidak valid'
                );
            })

            .always(function () {
                $btn
                    .prop('disabled', false)
                    .html('Verify OTP');
            });
    });


    // PILIH YA
    $('#btnYa').click(function () {
        $('#btnYa').prop('disabled', true).text('Processing...');

        $.post('/SuratJalan/confirm-approval', {
            id_surat_jalan: window.idSuratJalan,
            otp_id: window.otpId,
            is_sesuai: 1,
            email: $('#emailSelect').val()
        })

        .done(function () {
            alert('Approved & Email berhasil dikirim.');

            $('#modalKonfirmasi').modal('hide');

            let modalACC = new bootstrap.Modal(document.getElementById('modalACC'));
            modalACC.show();

            $('#btnOpenOtp').hide();
            $('#otpSection').hide();

            $('#approvalInfo').show();

            $('#rowStatus').hide();

            $('#labelApprovedBy').text('Approved By:');
            $('#labelApprovedAt').text('Approved At:');

            let contactValue = $('#contactSelect').val();

            $('#approvedEmail').text(contactValue);
            $('#approvedAt').text(formatDateTime(new Date()));

            $('#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt')
                .removeClass('text-danger')
                .addClass('text-success');

            $('#btnResendEmail')
                .prop('disabled', false)
                .removeClass('btn-secondary')
                .addClass('btn-primary')
                .text('Resend Email');
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
        let qty = parseFloat($('#qtyInput').val().replace(/,/g, ''));

        if (!qty || qty < 0) {
            alert('Jumlah Quantity tidak boleh minus');
            return;
        }

        let rowData = table.row(0).data();

        if (!rowData) {
            alert('Data tabel belum siap');
            return;
        }

        let qtyJual = parseFloat(rowData.QtyJual);
        let maxQty = qtyJual * 2;

        // batas digit berdasarkan maxQty
        let maxDigits = Math.floor(maxQty).toString().length;

        let inputValue = $('#qtyInput').val().trim();
        let numericOnly = inputValue.replace('.', '').replace(',', '');

        if (numericOnly.length > maxDigits) {
            alert(`Jumlah digit maksimal ${maxDigits} digit`);
            return;
        }

        // VALIDASI MAX 2x
        if (qty > maxQty) {
            alert(`Quantity barang sesuai tidak boleh melebihi 2x Quantity Jual (${maxQty})`);
            return;
        }

        let isSesuai = (qty === qtyJual) ? 1 : 0;

        $('#btnSubmitQty').prop('disabled', true).text('Processing...');

       $.post('/SuratJalan/confirm-approval', {
            id_surat_jalan: window.idSuratJalan,
            otp_id: window.otpId,
            is_sesuai: isSesuai,
            qty_temp: qty,
            email: $('#emailSelect').val()
        })
        .done(function () {

            $('#stepKonfirmasi').hide();
            $('#stepQty').hide();
            $('#stepFoto').show();

            $('#btnOpenOtp').hide();
            $('#otpSection').hide();
            $('#approvalInfo').show();

            let selectedText = $('#contactSelect option:selected').text();
            $('#approvedEmail').text(selectedText);

            $('#approvedAt').text(formatDateTime(new Date()));

            if (isSesuai === 1) {
                // APPROVE
                alert('Approved & Email berhasil dikirim');

                $('#rowStatus').hide();

                $('#labelApprovedBy').text('Approved By:');
                $('#labelApprovedAt').text('Approved At:');

                $('#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt')
                    .removeClass('text-danger')
                    .addClass('text-success');

                $('#btnResendEmail')
                    .prop('disabled', false)
                    .removeClass('btn-secondary')
                    .addClass('btn-primary')
                    .text('Resend Email');

            } else {
                // PASCA KIRIM
                alert('Data disimpan sebagai PASCA KIRIM');

                $('#rowStatus').show();
                $('#statusApproval').text('Requested');

                $('#labelApprovedBy').text('No HP:');
                $('#labelApprovedAt').text('Tanggal:');

                $('#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt')
                    .removeClass('text-success')
                    .addClass('text-danger');

                $('#btnResendEmail')
                    .prop('disabled', true)
                    .removeClass('btn-primary')
                    .addClass('btn-secondary')
                    .text('Pasca Kirim');
            }

        })
        .fail(function (xhr) {
            alert(xhr.responseJSON?.error ?? 'Terjadi kesalahan');
            $('#btnSubmitQty').prop('disabled', false).text('Submit');
        });
    });

    $('#btnResendEmail').click(function () {
        let idPengiriman = window.appData?.idPengiriman;

        if (!idPengiriman) {
            alert('ID Pengiriman tidak ditemukan');
            return;
        }

        $('#btnResendEmail').prop('disabled', true).text('Sending...');

        $.post('/SuratJalan/resend-email', {
            id_pengiriman: idPengiriman
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

    $('#fileFoto').on('change', function () {
        const files = Array.from(this.files);

        files.forEach(file => {

            let currentTotalSize = selectedFiles.reduce(
                (sum, file) => sum + file.size,
                    0
                );

            if (currentTotalSize + file.size > 50 * 1024 * 1024) {
                alert(
                    'Total ukuran seluruh foto maksimal 50 MB'
                );
                return;
            }

            selectedFiles.push(file);
        });

        renderPreview();
        $(this).val('');

        $('#jumlahFotoDipilih').text(
            selectedFiles.length + ' foto dipilih'
        );

        $(this).val('');
    });


    //upload foto pasca
    $('#btnUploadFoto').click(function () {
        if (selectedFiles.length === 0) {
            alert('Pilih foto terlebih dahulu');
            return;
        }

        let formData = new FormData();
        formData.append(
            'id_surat_jalan',
            window.idSuratJalan
        );

        formData.append('mode', 'PASCA');

        selectedFiles.forEach(file => {
            formData.append(
                'pictures[]',
                file
            );
        });

        let $btn = $(this);

        $btn
            .prop('disabled', true)
            .text('Uploading...');

        $.ajax({
            url: '/SuratJalan/upload-foto',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function (res) {
                alert(res.message);
                $('#modalKonfirmasi').modal('hide');
                location.reload();
            },

            error: function (xhr) {
                console.log('Status:', xhr.status);
                console.log('Response:', xhr.responseText);
                console.log('JSON:', xhr.responseJSON);

                if (xhr.status === 422) {

                    if (xhr.responseJSON?.message) {
                        alert(xhr.responseJSON.message);
                        return;
                    }

                    if (xhr.responseJSON?.errors) {

                        let message = Object.values(
                            xhr.responseJSON.errors
                        )
                        .flat()
                        .join('\n');

                        alert(message);
                        return;
                    }
                }

                if (xhr.status === 413) {
                    alert('Ukuran file melebihi batas server');
                    return;
                }

                if (xhr.responseText) {
                    alert(
                        'Error ' +
                        xhr.status +
                        '\n\n' +
                        xhr.responseText.substring(0, 500)
                    );
                    return;
                }

                alert('Upload gagal');
            },

            complete: function () {
                $btn
                    .prop('disabled', false)
                    .text('Upload Foto');
            }
        });
    });

    $('#btnCameraFoto').click(async function () {
        activeUploadMode = 'PASCA';
        const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);

        if (isMobile) {
            $('#cameraInput').click();
            return;
        }

        try {
            stream =
                await navigator.mediaDevices
                    .getUserMedia({
                        video: true
                    });
            cameraVideo.srcObject = stream;
            cameraModal.style.display = 'flex';

        }
        catch (err) {
            alert('Tidak dapat mengakses kamera');
        }
    });

    $('#btnCameraFotoACC').click(async function () {
        activeUploadMode = 'ACC';

        const isMobile = /Android|iPhone|iPad/i.test(navigator.userAgent);

        if (isMobile) {
            $('#cameraInputACC').click();
            return;
        }

        try {

            stream =
                await navigator.mediaDevices
                    .getUserMedia({
                        video: true
                    });

            cameraVideo.srcObject = stream;
            cameraModal.style.display = 'flex';

        }
        catch (err) {

            alert(
                'Tidak dapat mengakses kamera'
            );
        }
    });

    $('#cameraInput').on('change', function () {
        let files = Array.from(this.files);

        if (activeUploadMode === 'ACC') {
            let file = files[0];
            if (!file) {
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert(
                    'Ukuran foto maksimal 5 MB'
                );
                return;
            }

            selectedFilesACC = [file];
            renderPreviewACC();
            $(this).val('');
            return;
        }

        // PASCA
        for (let file of files) {
            let currentTotalSize =
                selectedFiles.reduce(
                    (sum, f) => sum + f.size,
                    0
                );

            if (currentTotalSize + file.size > 50 * 1024 * 1024) {
                alert(
                    'Total ukuran seluruh foto maksimal 50 MB'
                );
                return;
            }
            selectedFiles.push(file);
        }

        renderPreview();

        $(this).val('');
    });

    $('#btnTakePhoto').click(function () {
        let ctx = cameraCanvas.getContext('2d');

        cameraCanvas.width = cameraVideo.videoWidth;
        cameraCanvas.height = cameraVideo.videoHeight;

        ctx.drawImage(
            cameraVideo,
            0,
            0
        );

        cameraCanvas.toBlob(
            function (blob) {
                const file =
                    new File(
                        [blob],
                        `camera_${Date.now()}.jpg`,
                        {
                            type: 'image/jpeg'
                        }
                    );

                const currentTotalSize =
                    selectedFiles.reduce(
                        (sum, f) => sum + f.size,
                        0
                    );

                if (
                    currentTotalSize + file.size >
                    50 * 1024 * 1024
                ) {

                    alert(
                        'Total ukuran seluruh foto maksimal 50 MB'
                    );

                    if (stream) {
                        stream.getTracks().forEach(
                            track => track.stop()
                        );
                    }

                    cameraModal.style.display = 'none';

                    return;
                }

                if (activeUploadMode === 'ACC') {
                    if (file.size > 5 * 1024 * 1024) {
                        alert(
                            'Ukuran foto maksimal 5 MB'
                        );
                        return;
                    }

                    selectedFilesACC = [file];
                    renderPreviewACC();

                } else {
                    let currentTotalSize =
                        selectedFiles.reduce(
                            (sum, f) => sum + f.size,
                            0
                        );

                    if (currentTotalSize + file.size > 50 * 1024 * 1024) {
                        alert(
                            'Total ukuran seluruh foto maksimal 50 MB'
                        );
                        return;
                    }
                    selectedFiles.push(file);
                    renderPreview();
                }
            },
            'image/jpeg',0.9
        );

        if (stream) {
            stream.getTracks().forEach(
                track => track.stop()
            );
        }

        cameraModal.style.display = 'none';
    });

    $('#btnCloseCamera').click(function () {
        if (stream) {
            stream.getTracks().forEach(
                track => track.stop()
            );
        }

        cameraModal.style.display = 'none';
    });

    $(document).on('click', '.delete-btn', function () {
        let index = $(this).data('index');
        selectedFiles.splice(index, 1);
        renderPreview();
    });

    $(document).on('click', '.delete-btn-acc', function () {
        selectedFilesACC = [];
        renderPreviewACC();
        }
    );

    $(document).on('click', '.preview-image', function () {
        $('#previewModalImage').attr(
            'src',
            $(this).data('src')
        );

        $('#imagePreviewModal').modal('show');
    });


    $('#fileFotoACC').on('change', function () {
        let file = this.files[0];

        if (!file) {
            return;
        }

        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran foto maksimal 5 MB');
            $(this).val('');
            return;
        }

        selectedFilesACC = [file];

        renderPreviewACC();

        $(this).val('');
    });

    $('#btnUploadFotoACC').click(function () {
        if (selectedFilesACC.length === 0) {
            $('#modalACC').modal('hide');
            location.reload();
            return;
        }

        let formData = new FormData();

        formData.append(
            'id_surat_jalan',
            window.idSuratJalan
        );

        formData.append(
        'mode',
        'ACC'
    );

        formData.append(
            'pictures[]',
            selectedFilesACC[0]
        );

        let $btn = $(this);

        $btn
            .prop('disabled', true)
            .text('Uploading...');

        $.ajax({
            url: '/SuratJalan/upload-foto',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            success: function (res) {

                alert(res.message);

                $('#modalACC').modal('hide');

                location.reload();
            },

            error: function (xhr) {

                alert(
                    xhr.responseJSON?.message ??
                    'Upload gagal'
                );
            },

            complete: function () {

                $btn
                    .prop('disabled', false)
                    .text('Upload Foto');
            }
        });
    });

    btn_clearPhotos.addEventListener("click", function () {
        selectedFiles = [];
        renderPreview();
    });

    //#endregion


});
