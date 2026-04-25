@extends('layouts.app')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-11 RDZMobilePaddingLR0">
                <div class="card">
                    @if (session('ForgetPassword'))
                        <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            Swal.fire({
                                title: 'Ganti Password',
                                html: `
                                    <input type="password" id="password" class="swal2-input" placeholder="Password baru">

                                    <div style="text-align:left; margin:5px 20px;">
                                        <input type="checkbox" id="showPassword" style="margin-right:5px;">
                                        <label for="showPassword" style="font-size:14px;">Tampilkan Password</label>
                                    </div>

                                    <small style="color:#666">
                                        Minimal 8 karakter, huruf besar, huruf kecil, dan karakter spesial
                                    </small>
                                `,
                                confirmButtonText: 'Simpan',
                                allowOutsideClick: false,
                                allowEscapeKey: false,

                                 didOpen: () => {
                                    let passwordInput = document.getElementById('password');
                                    let checkbox = document.getElementById('showPassword');

                                    checkbox.addEventListener('change', function () {
                                        passwordInput.type = this.checked ? 'text' : 'password';
                                    });
                                },

                                preConfirm: () => {
                                    let password = document.getElementById('password').value;

                                    if (!password) {
                                        Swal.showValidationMessage('Password wajib diisi');
                                        return false;
                                    }

                                    // Validasi regex
                                    let regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[^A-Za-z0-9]).{8,}$/;

                                    if (!regex.test(password)) {
                                        Swal.showValidationMessage(
                                            'Password harus minimal 8 karakter, mengandung huruf besar, huruf kecil, dan karakter spesial'
                                        );
                                        return false;
                                    }

                                    return { password: password };
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch("{{ route('force.reset.password') }}", {
                                        method: "POST",
                                        headers: {
                                            "Content-Type": "application/json",
                                            "X-CSRF-TOKEN": "{{ csrf_token() }}"
                                        },
                                        body: JSON.stringify({
                                            password: result.value.password
                                        })
                                    })
                                    .then(res => res.json())
                                    .then(data => {
                                        if (data.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Berhasil',
                                                text: 'Password berhasil diubah'
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire('Error', 'Gagal mengubah password', 'error');
                                        }
                                    });
                                }
                            });
                        });
                        </script>
                        @endif
                    <div class="card-header">Product Receipt</div>
                    <div class="card-body">
                        <div style="overflow: auto;">
                            {{-- <h1>silahkan scan qr barcode dari kami</h1> --}}
                            <a href="{{ route('SuratJalan.index') }}" class="btn btn-primary">
                                Surat Jalan Belum Verifikasi
                            </a>
                            <a href="{{ route('DokumenSJ.index') }}" class="btn btn-success ms-2">
                                Surat Jalan Sudah Verifikasi
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
