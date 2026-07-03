<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Product Receipt</title>

    <link rel="icon" type="image/png" href="{{ asset('images/KRR.png') }}">

    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/2.3.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="{{ asset('css/suratJalanPesanan.css') }}" rel="stylesheet">
</head>

<body>

    <div class="container-fluid mt-3">
        <div class="row justify-content-center">
            <div class="col-11">
                <div class="card">

                    <div class="card-header fw-bold">Konfirmasi Product Receipt</div>

                    <div class="card-body">

                        <!-- HEADER + OTP -->
                        <div class="mb-3 d-flex justify-content-between align-items-start">

                            <!-- LEFT -->
                            <div>
                                <div style="font-size:18px">
                                    <strong>Nomor PO</strong> : <span id="noPo">-</span>
                                </div>
                                <div style="font-size:18px">
                                    <strong>Tanggal Kirim</strong> : <span id="tglKirim">-</span>
                                </div>
                                <div style="font-size:18px">
                                    <strong>Note</strong> : <span id="noteUntukCustomer">-</span>
                                </div>
                            </div>

                            <!-- RIGHT -->
                            <div style="min-width:250px">

                                @if (!isset($otp) || !$otp)
                                    <button id="btnOpenOtp" class="btn btn-primary w-100 mb-2">
                                        Konfirmasi Product Receipt
                                    </button>

                                    <div id="otpSection" style="display:none;">

                                        <select id="contactType" class="form-select mb-2">
                                            <option value="">-- Pilih Metode OTP --</option>
                                            <option value="email">Email</option>
                                            <option value="sms">SMS</option>
                                        </select>

                                        <select id="contactSelect" class="form-select mb-2">
                                            <option value="">
                                                -- Pilih Kontak --
                                            </option>
                                        </select>

                                        <button id="btnSendOtp" class="btn btn-warning w-100 mb-2">
                                            Send OTP
                                        </button>

                                        <input type="text" id="otpInput" class="form-control mb-2"
                                            placeholder="Masukkan OTP">

                                        <button id="btnVerifyOtp" class="btn btn-success w-100">
                                            Verify OTP
                                        </button>

                                    </div>
                                @endif

                                <!-- APPROVAL INFO -->
                                <div id="approvalInfo" class="mt-2"
                                    style="{{ isset($otp) && $otp ? '' : 'display:none;' }}">

                                    <div id="rowStatus" style="display:none;">
                                        <strong id="labelStatus">Status:</strong>
                                        <span id="statusApproval"></span>
                                    </div>

                                    <div>
                                        <strong id="labelApprovedBy">Approved By:</strong>
                                        <span id="approvedEmail"></span>
                                    </div>

                                    <div>
                                        <strong id="labelApprovedAt">Approved At:</strong>
                                        <span id="approvedAt"></span>
                                    </div>

                                    <button id="btnResendEmail" class="btn w-100 mt-2 fw-bold">
                                        Resend Email
                                    </button>
                                </div>

                            </div>
                        </div>

                        <!-- TABLE -->
                        <div class="table-responsive">
                            <table id="tableSuratJalan" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nama Barang</th>
                                        <th>Quantity</th>
                                        <th>Satuan</th>
                                        <th>Nama Customer</th>
                                        <th>Surat Pesanan</th>
                                        <th>Nama Ekspedisi</th>
                                        <th>Nomor Polisi</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PASCA -->
    <div class="modal fade" id="modalKonfirmasi" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Konfirmasi Pasca Kirim</h5>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal"></button> --}}
                </div>

                <div class="modal-body">
                    <div id="stepKonfirmasi">
                        <p><strong>Yakin untuk konfirmasi pasca kirim?</strong></p>

                        <div class="d-flex gap-2">
                            <button id="btnYa" class="btn btn-success w-50">Ya</button>
                            <button id="btnTidak" class="btn btn-danger w-50">Tidak</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Inject data --}}
    <script>
        window.appData = {
            idPengiriman: "{{ $id_pengiriman }}",
            noPo: "{{ $no_po }}"
        };

        window.otpData = @json($otp ?? null);
    </script>

    <script>
        const channel = new BroadcastChannel("session_channel");
        const TAB_ID = Date.now() + "_" + Math.random();

        const LEADER_KEY = "heartbeat_leader";
        const LEADER_TS_KEY = "heartbeat_leader_ts";
        const SESSION_EXPIRED_KEY = "session_expired";

        const HEARTBEAT_INTERVAL = 1810000; // 30 minutes + 10 sec buffer
        const LEADER_TIMEOUT = 1850000; // slightly > heartbeat

        // Untuk testing, gunakan interval yang lebih pendek
        // const HEARTBEAT_INTERVAL = 61000; // 1 min + 1 sec buffer
        // const LEADER_TIMEOUT = 70000; // slightly > heartbeat

        let isLeader = false;
        let lastCheck = 0;
        const MIN_INTERVAL = 60 * 1000; // 10 minute

        // 🔁 Try to become leader
        function tryBecomeLeader() {
            const now = Date.now();
            const leader = localStorage.getItem(LEADER_KEY);
            const leaderTs = parseInt(localStorage.getItem(LEADER_TS_KEY), 10);
            // No leader OR leader is dead
            if (!leader || !leaderTs || now - leaderTs > LEADER_TIMEOUT) {
                localStorage.setItem(LEADER_KEY, TAB_ID);
                localStorage.setItem(LEADER_TS_KEY, now);
                isLeader = true;
            } else if (leader === TAB_ID) {
                isLeader = true;
            } else {
                isLeader = false;
            }
        }

        channel.onmessage = (event) => {
            if (event.data === "session_expired") {
                window.location.href = "/sessionexpired";
            }
        };

        function triggerSessionExpired() {
            channel.postMessage("session_expired");
            window.location.href = "/sessionexpired";
        }

        $(document).ajaxError(function(event, xhr) {
            if (xhr.status === 419) {
                triggerSessionExpired();
            }
        });

        setInterval(() => {
            tryBecomeLeader();

            if (!isLeader) return;

            // Update leader timestamp (I'm alive)
            localStorage.setItem(LEADER_TS_KEY, Date.now());

            fetch("/heartbeat", {
                    method: "GET",
                    headers: {
                        "X-Requested-With": "XMLHttpRequest",
                    },
                })
                .then((res) => {
                    console.log({
                        status: res.status,
                        redirected: res.redirected,
                        url: res.url,
                    });
                    if (res.status === 401 || res.status === 419 || res.redirected) {
                        triggerSessionExpired();
                    }
                })
                .catch(() => {
                    triggerSessionExpired();
                });
        }, HEARTBEAT_INTERVAL);

        window.addEventListener("beforeunload", () => {
            const leader = localStorage.getItem(LEADER_KEY);
            if (leader === TAB_ID) {
                localStorage.removeItem(LEADER_KEY);
                localStorage.removeItem(LEADER_TS_KEY);
            }
        });

        document.addEventListener("visibilitychange", () => {
            if (document.visibilityState === "visible") {
                const now = Date.now();

                if (now - lastCheck < MIN_INTERVAL) {
                    return; // skip, too soon
                }

                lastCheck = now;
                if (!isLeader) {
                    tryBecomeLeader();
                }

                if (isLeader) {
                    fetch("/heartbeat", {
                            method: "GET",
                            headers: {
                                "X-Requested-With": "XMLHttpRequest",
                            },
                        })
                        .then((res) => {
                            if (
                                res.status === 401 ||
                                res.status === 419 ||
                                res.redirected
                            ) {
                                triggerSessionExpired();
                            }
                        })
                        .catch(() => {
                            triggerSessionExpired();
                        });
                }
            }
        });
    </script>

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables -->
    <script src="https://cdn.datatables.net/2.3.7/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.7/js/dataTables.bootstrap5.min.js"></script>

    <!-- Custom JS -->
    <script src="{{ asset('js/pascaKirim.js') }}"></script>

</body>

</html>
