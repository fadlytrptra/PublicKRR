jQuery(function ($) {
    //#region Variables
    let idPengiriman = window.appData?.idPengiriman;
    let noPo = window.appData?.noPo;
    let selectedFiles = [];
    let selectedFilesACC = [];
    let activeUploadMode = null;
    let stream = null;
    let fileFoto = document.getElementById("fileFoto");
    let cameraInput = document.getElementById("cameraInput");
    let btnCameraFoto = document.getElementById("btnCameraFoto");
    let cameraModal = document.getElementById("cameraModal");
    let cameraVideo = document.getElementById("cameraVideo");
    let cameraCanvas = document.getElementById("cameraCanvas");
    let btn_clearPhotos = document.getElementById("btn_clearPhotos");
    let btn_clearPhotos_acc = document.getElementById("btn_clearPhotos_acc");

    if (!idPengiriman) {
        console.error("ID Pengiriman tidak ditemukan");
        return;
    }

    $.ajaxSetup({
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
    });

    // =============================
    // DATATABLE
    // =============================
    let table = $("#tableSuratJalan").DataTable({
        processing: true,
        searching: false,
        lengthChange: false,
        ajax: {
            url: "/PascaKirim-data",
            data: function (d) {
                ((d.idPengiriman = idPengiriman), (d.no_po = noPo)); // hanya untuk ambil data tabel
            },
            dataSrc: function (json) {
                $("#noPo").text(json.header?.No_PO ?? "-");
                $("#tglKirim").text(formatDate(json.header?.TglKirim));
                $("#noteUntukCustomer").text(
                    json.header?.NotePascaKeCustomer ?? "-",
                );
                return json.data;
            },
        },
        columns: [
            { data: "NamaType" },
            {
                data: "QtyTempVerifikasi",
                render: function (data, type, row) {
                    console.log(data);

                    if (data == null) return "-";

                    return parseFloat(data).toLocaleString("en-US", {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    });
                },
            },
            {
                data: "SatJual",
                render: function (data, type, row) {
                    if (!data) return "-";

                    const formatSatuan = {
                        TABUNG: "TABUNG",
                        SET: "SET",
                        KGM: "KILOGRAM",
                        RP: "RP",
                        BALL: "BALL",
                        LBR: "LEMBAR",
                        PC: "POTONG",
                        YARDS: "YARD",
                        "MTR²": "METER PERSEGI",
                        ROLL: "ROLL",
                        DRUM: "DRUM",
                        LJR: "LONJOR",
                        MTR: "METER",
                        UNIT: "UNIT",
                    };

                    return formatSatuan[data.trim()] ?? data;
                },
            },
            { data: "NamaCust" },
            { data: "SuratPesanan" },
            { data: "NamaExpeditor" },
            { data: "TrukNopol" },
        ],
    });

    // =============================
    // INIT STATE (dari backend)
    // =============================
    let otpData = window.otpData || null;

    if (otpData) {
        $("#approvalInfo").show();

        let approvedBy = otpData.Email ?? otpData.Phone ?? "-";
        let accCustomer = Number(otpData.ACCCustomer);
        let createdAt = otpData.CreatedAt;
        let approvedAt = otpData.ApprovedAt;

        if (accCustomer === 1) {
            // Approve (ACC)
            $("#rowStatus").hide();

            $("#labelApprovedBy").text("Approved By:");
            $("#labelApprovedAt").text("Approved At:");

            $("#approvedEmail").text(approvedBy);
            $("#approvedAt").text(formatDateTime());

            $(
                "#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt",
            )
                .removeClass("text-danger")
                .addClass("text-success");

            $("#btnResendEmail")
                .prop("disabled", false)
                .removeClass("btn-secondary")
                .addClass("btn-primary")
                .text("Resend Email");
        } else {
            // PASCA KIRIM
            $("#rowStatus").show();
            $("#statusApproval").text("Requested");

            $("#labelApprovedBy").text("Kontak:");
            $("#labelApprovedAt").text("Tanggal:");

            $("#approvedEmail").text(approvedBy);
            $("#approvedAt").text(formatDateTime(approvedAt));

            $(
                "#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt",
            )
                .removeClass("text-success")
                .addClass("text-danger");

            $("#btnResendEmail")
                .prop("disabled", true)
                .removeClass("btn-primary")
                .addClass("btn-secondary")
                .text("Pasca Kirim");
        }
    }

    //#endregion

    //#region function

    function formatDate(dateString) {
        if (!dateString) return "-";

        let date = new Date(dateString);
        return date.toLocaleDateString("id-ID", {
            day: "2-digit",
            month: "long",
            year: "numeric",
        });
    }

    function formatDateTime(dateInput) {
        if (!dateInput) return "-";

        let date;

        // kalau string dari DB
        if (typeof dateInput === "string") {
            let clean = dateInput.split(".")[0];
            date = new Date(clean.replace(" ", "T"));
        }
        // kalau object Date
        else {
            date = dateInput;
        }

        if (isNaN(date)) return "-";

        let day = String(date.getDate()).padStart(2, "0");
        let month = String(date.getMonth() + 1).padStart(2, "0");
        let year = date.getFullYear();

        let hours = String(date.getHours()).padStart(2, "0");
        let minutes = String(date.getMinutes()).padStart(2, "0");
        let seconds = String(date.getSeconds()).padStart(2, "0");

        return `${day}/${month}/${year}, ${hours}:${minutes}:${seconds}`;
    }

    //#endregion

    //#region Event Listener

    // =============================
    // OTP FLOW
    // =============================

    // STEP 1: Load email
    let contacts = [];
    $("#btnOpenOtp").click(function () {
        $("#btnOpenOtp").prop("disabled", true).text("Loading...");

        $.get("/SuratJalan/get-contacts/" + idPengiriman)
            .done(function (res) {
                contacts = res;

                $("#contactType").val("");
                $("#contactSelect").html(
                    '<option value="">-- Pilih Kontak --</option>',
                );

                $("#otpSection").slideDown();
            })

            .fail(function () {
                alert("Gagal mengambil kontak");
            })

            .always(function () {
                $("#btnOpenOtp")
                    .prop("disabled", false)
                    .text("Konfirmasi Product Receipt");
            });
    });

    $("#contactType").change(function () {
        let type = $(this).val();
        let html = '<option value="">-- Pilih Kontak --</option>';

        contacts.forEach((item) => {
            if (type === "email" && item.Email) {
                html += `
                    <option
                        value="${item.Email}"
                        data-userid="${item.IdUser}"
                        data-nama="${item.NamaUser}">
                        ${item.NamaUser} - ${item.Email}
                    </option>
                `;
            }

            if (type === "sms" && item.Phone) {
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

        $("#contactSelect").html(html);
    });

    // STEP 2: Kirim OTP
    $("#btnSendOtp").click(function () {
        let type = $("#contactType").val();
        let value = $("#contactSelect").val();
        let selected = $("#contactSelect option:selected");

        if (!type) {
            alert("Pilih metode OTP");
            return;
        }

        if (!value) {
            alert("Pilih kontak terlebih dahulu");
            return;
        }

        $("#btnSendOtp").prop("disabled", true).text("Mengirim...");

        let payload = {
            id_pengiriman: idPengiriman,
            otp_method: type,
            id_user: selected.data("userid"),
        };

        if (type === "email") {
            payload.email = value;
        } else {
            payload.phone = value;
        }

        $.post("/PascaKirim/send-otp", payload)

            .done(function () {
                alert("OTP berhasil dikirim");
            })

            .fail(function (xhr) {
                alert(
                    xhr.responseJSON?.error ??
                        xhr.responseJSON?.message ??
                        "Gagal kirim OTP",
                );
            })

            .always(function () {
                $("#btnSendOtp").prop("disabled", false).text("Send OTP");
            });
    });

    // STEP 3: Verify OTP
    $("#btnVerifyOtp").click(function () {
        let type = $("#contactType").val();
        let value = $("#contactSelect").val();
        let otp = $("#otpInput").val();
        let selected = $("#contactSelect option:selected");

        if (!value) {
            alert("Pilih kontak terlebih dahulu");
            return;
        }

        if (!otp) {
            alert("Masukkan OTP");
            return;
        }

        let payload = {
            id_pengiriman: idPengiriman,
            otp: otp,
            id_user: selected.data("userid"),
        };

        if (type === "email") {
            payload.email = value;
        } else {
            payload.phone = value;
        }

        let $btn = $("#btnVerifyOtp");

        $btn.prop("disabled", true).html("Verifying...");

        console.log(type, value, otp, selected);

        $.post("/PascaKirim/verify-otp", payload)

            .done(function (res) {
                window.idSuratJalan = res.id_surat_jalan;

                window.otpId = res.otp_id;

                $("#otpInput").val("");
                $("#otpSection").hide();
                $("#modalKonfirmasi").modal("show");
            })

            .fail(function (xhr) {
                alert(xhr.responseJSON?.error ?? "OTP tidak valid");
            })

            .always(function () {
                $btn.prop("disabled", false).html("Verify OTP");
            });
    });

    // PILIH YA
    $("#btnYa").click(function () {
        $("#btnYa").prop("disabled", true).text("Processing...");

        $.post("/PascaKirim/confirm-approval", {
            id_surat_jalan: window.idSuratJalan,
            otp_id: window.otpId,
            is_sesuai: 1,
            email: $("#contactSelect").val(),
        })

            .done(function () {
                alert("Approved & Email berhasil dikirim.");

                $("#modalKonfirmasi").modal("hide");

                $("#btnOpenOtp").hide();
                $("#otpSection").hide();

                $("#approvalInfo").show();

                $("#rowStatus").hide();

                $("#labelApprovedBy").text("Approved By:");
                $("#labelApprovedAt").text("Approved At:");

                let contactValue = $("#contactSelect").val();

                $("#approvedEmail").text(contactValue);
                $("#approvedAt").text(formatDateTime(new Date()));

                $(
                    "#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt",
                )
                    .removeClass("text-danger")
                    .addClass("text-success");

                $("#btnResendEmail")
                    .prop("disabled", false)
                    .removeClass("btn-secondary")
                    .addClass("btn-primary")
                    .text("Resend Email");
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.error ?? "Terjadi kesalahan");
                $("#btnYa").prop("disabled", false).text("Ya");
            });
    });

    // PILIH TIDAK
    $("#btnTidak").click(function () {
        $("#modalKonfirmasi").modal("hide");
        $.post("/PascaKirim/confirm-approval", {
            id_surat_jalan: window.idSuratJalan,
            otp_id: window.otpId,
            is_sesuai: 0,
            email: $("#contactSelect").val(),
        })

            .done(function () {
                alert("Data disimpan sebagai PASCA KIRIM");
                $("#btnOpenOtp").hide();
                $("#otpSection").hide();
                $("#approvalInfo").show();
                $("#stepKonfirmasi").hide();

                let contactValue = $("#contactSelect").val();
                $("#rowStatus").show();
                $("#statusApproval").text("Requested");
                $("#labelApprovedBy").text("Kontak:");
                $("#labelApprovedAt").text("Tanggal:");
                $("#approvedEmail").text(contactValue);
                $("#approvedAt").text(formatDateTime(new Date()));

                $(
                    "#labelStatus, #statusApproval, #labelApprovedBy, #labelApprovedAt, #approvedEmail, #approvedAt",
                )
                    .removeClass("text-success")
                    .addClass("text-danger");

                $("#btnResendEmail")
                    .prop("disabled", true)
                    .removeClass("btn-primary")
                    .addClass("btn-secondary")
                    .text("Pasca Kirim");
            })
            .fail(function (xhr) {
                alert(xhr.responseJSON?.error ?? "Terjadi kesalahan");
                $("#btnYa").prop("disabled", false).text("Ya");
            });
    });

    $("#btnResendEmail").click(function () {
        let idPengiriman = window.appData?.idPengiriman;

        if (!idPengiriman) {
            alert("ID Pengiriman tidak ditemukan");
            return;
        }

        $("#btnResendEmail").prop("disabled", true).text("Sending...");

        $.post("/PascaKirim/resend-email", {
            id_pengiriman: idPengiriman,
        })

            .done(function (res) {
                alert(res.message || "Email berhasil dikirim");
            })

            .fail(function (xhr) {
                alert(xhr.responseJSON?.message ?? "Gagal kirim email");
            })
            .always(function () {
                $("#btnResendEmail")
                    .prop("disabled", false)
                    .text("Resend Email");
            });
    });
    //#endregion
});
