<!DOCTYPE html>
<html>

<head>
    <link rel="icon" href="{{ asset('/images/krr.png') }}" type="image/gif" sizes="17x15">
    <title>Register</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f5f5f5;
            margin: 0;
        }

        .container {
            width: 30%;
            margin: 15px auto;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        input,
        textarea {
            width: 90%;
            padding: 8px;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #38c172;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #2f9e5f;
        }

        .btn-back {
            display: block;
            text-align: center;
            margin: 10px 0 10px 0;
            padding: 10px;
            background: #4d5358;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.2s;
            font-size: 17px;
            width: 30%;
            place-self: center;
        }

        .btn-back:hover {
            background: #5a6268;
        }

        .register {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.2s;
            font-size: 17px;
            width: 30%;
            place-self: center;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
        }

        .toggle-password {
            position: relative;
            vertical-align: text-top;
            right: 7%;
            cursor: pointer;
            color: #555;
        }

        .toggle-password:hover {
            color: #000;
        }

        .header-logo {
            width: 30%;
            margin: 5px auto 5px auto;
            padding-right: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            background-color: #d4d3d3 padding: 12px 15px 15px 10px;
            font-weight: bold;
            font-size: 20px;
        }

        .logo-krr {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .nohp-wrapper {
            display: flex;
            width: calc(90% + 16px);
            max-width: 100%;
            box-sizing: border-box;
            margin-top: 5px;
            margin-bottom: 15px;
        }

        .nohp-wrapper .kode-negara {
            width: 150px;
            min-width: 150px;
            height: 31px;
            padding: 4px 8px;
            margin: 0;
            box-sizing: border-box;
            border: 1px solid #ced4da;
            border-radius: 4px 0 0 4px;
            background-color: white;
        }

        .nohp-wrapper #nohp {
            flex: 1;
            width: auto;
            min-width: 0;
            height: 31px;
            padding: 6px 8px;
            margin: 0;
            box-sizing: border-box;
            border: 1px solid #ced4da;
            border-left: none;
            border-radius: 0 4px 4px 0;
        }
    </style>
</head>

<body>
    <div class="header-logo">
        <img src="{{ asset('images/KRR.png') }}" alt="KRR Logo" class="logo-krr">
        <span>Kerta Rajasa Raya</span>
    </div>

    <div class="container">
        {{-- <div class="header-logo">
            <img src="{{ asset('images/KRR.png') }}" alt="KRR Logo" class="logo-krr">
            <span>Kerta Rajasa Raya</span>
        </div> --}}

        <h2>Register</h2>

        @if ($errors->has('error'))
            <div class="error">
                {{ $errors->first('error') }}
            </div>
        @endif

        <form method="POST" action="/register" enctype="multipart/form-data">
            @csrf

            @php
                $data = session('showOtp') ? session('register_data') : null;
            @endphp
            <label for="Email">Email</label>
            <div>
                <input type="email" id="Email" name="Email" value="{{ old('Email', $data['Email'] ?? '') }}">
                @error('Email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label for="NamaUser">Nama User</label>
            <div>
                <input type="text" id="NamaUser" name="NamaUser"
                    value="{{ old('NamaUser', $data['NamaUser'] ?? '') }}">
                @error('NamaUser')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label for="NamaPerusahaan">Nama Perusahaan</label>
            <div>
                <input type="text" id="NamaPerusahaan" name="NamaPerusahaan"
                    value="{{ old('NamaPerusahaan', $data['NamaPerusahaan'] ?? '') }}">
                @error('NamaPerusahaan')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label for="AlamatPerusahaan">Alamat Perusahaan (Sesuai NPWP)</label>
            <div>
                <textarea id="AlamatPerusahaan" name="AlamatPerusahaan">{{ old('AlamatPerusahaan', $data['AlamatPerusahaan'] ?? '') }}</textarea>
                @error('AlamatPerusahaan')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <label for="NoHP">No HP</label>

            <div class="nohp-wrapper">
                <select name="kodeNegara" id="kodeNegara" class="kode-negara">
                    <option value="62" selected>Indonesia (62)</option>
                    <option value="60">Malaysia (60)</option>
                    <option value="65">Singapore (65)</option>
                    <option value="66">Thailand (66)</option>
                    <option value="84">Vietnam (84)</option>
                    <option value="63">Philippines (63)</option>
                    <option value="673">Brunei (673)</option>
                    <option value="1">United States (1)</option>
                    <option value="44">United Kingdom (44)</option>
                    <option value="81">Japan (81)</option>
                    <option value="82">South Korea (82)</option>
                    <option value="86">China (86)</option>
                    <option value="91">India (91)</option>
                    <option value="61">Australia (61)</option>
                </select>

                <input
                    type="text"
                    name="NoHP"
                    id="nohp"
                    inputmode="numeric"
                    maxlength="14"
                    placeholder="81234567890"
                    required
                >
            </div>

            @error('NoHP')
                <div class="error">{{ $message }}</div>
            @enderror

            <label for="NPWP">NPWP Perusahaan</label>
            <div>
                <input type="text" name="NPWP" id="npwp" inputmode="numeric" maxlength="16" required
                    value="{{ old('NPWP', $data['NPWP'] ?? '') }}">
                @error('NPWP')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <div class="password-wrapper">
                <label>Password</label>

                <div>
                    <input type="password" name="Password" id="password"
                        value="{{ old('Password', $data['raw_password'] ?? '') }}">

                    <span class="toggle-password" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                            viewBox="0 0 24 24">
                            <path d="M12 9a3 3 0 1 0 0 6 3 3 0 1 0 0-6"></path>
                            <path
                                d="M12 19c7.63 0 9.93-6.62 9.95-6.68.07-.21.07-.43 0-.63-.02-.07-2.32-6.68-9.95-6.68s-9.93 6.61-9.95 6.67c-.07.21-.07.43 0 .63.02.07 2.32 6.68 9.95 6.68Zm0-12c5.35 0 7.42 3.85 7.93 5-.5 1.16-2.58 5-7.93 5s-7.42-3.84-7.93-5c.5-1.16 2.58-5 7.93-5">
                            </path>
                        </svg>
                    </span>
                </div>

                @error('Password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            {{-- Pilih Metode untuk otp --}}
            <label>Metode Verifikasi OTP: </label>
            <div style="margin-bottom: 15px;">
                <div style="display:flex; gap:20px; margin-top:8px;">
                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="otp_method" value="email"
                            {{ old('otp_method', 'email') == 'email' ? 'checked' : '' }}>
                        Email
                    </label>

                    <label style="display:flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="otp_method" value="sms"
                            {{ old('otp_method') == 'sms' ? 'checked' : '' }}>
                        SMS
                    </label>
                </div>
                @error('otp_method')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            @error('otp_method')
                <div class="error">{{ $message }}</div>
            @enderror

            {{-- BUTTON REGISTER --}}
            <button type="submit" class="btn register">
                Register
            </button>

            <button class="btn-back" id="button_backToLogin">
                Kembali ke Login
            </button>

        </form>
    </div>

    {{-- OTP FORM TERPISAH --}}
    @if (session('showOtp'))
        <div class="container">
            <hr>

            <h3>Verifikasi OTP</h3>

            @if (session('success'))
                <div style="color: green;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ url('/verify-otp') }}">

                @csrf

                <input type="hidden" name="email" value="{{ session('email') }}">

                <label>
                    Masukkan OTP
                </label>

                <input type="text" name="otp" maxlength="6" placeholder="6 digit OTP">

                @error('otp')
                    <div class="error">
                        {{ $message }}
                    </div>
                @enderror

                <button type="submit">
                    Verifikasi
                </button>
            </form>
        </div>
    @endif


    <script>
        let inputFile = document.getElementById('TTCustomer');
        let previewContainer = document.getElementById('preview-container');
        let previewImage = document.getElementById('preview-image');
        let button_backToLogin = document.getElementById('button_backToLogin');

        if (inputFile) {
            inputFile.addEventListener('change', function() {
                let file = this.files[0];

                if (file) {
                    let reader = new FileReader();

                    reader.onload = function(e) {
                        previewImage.src = e.target.result;
                        previewContainer.style.display = 'block';
                    }

                    reader.readAsDataURL(file);
                } else {
                    previewContainer.style.display = 'none';
                }
            });
        }

        function togglePassword() {
            let input = document.getElementById("password");

            if (input.type === "password") {
                input.type = "text";
            } else {
                input.type = "password";
            }
        }

        let npwpInput = document.getElementById('npwp');
        if (npwpInput) {
            npwpInput.addEventListener('input', function(e) {
                let value = e.target.value;

                // hanya angka
                value = value.replace(/[^0-9]/g, '');

                // max 16 digit
                value = value.substring(0, 16);

                e.target.value = value;
            });
        }

        // ========================================
        // NO HP
        // ========================================
        let nohpInput = document.getElementById('nohp');

        if (nohpInput) {
            nohpInput.addEventListener('input', function (e) {
                let value = e.target.value;
                value = value.replace(/[^0-9]/g, '');
                value = value.replace(/^0+/, '');
                value = value.substring(0, 14);
                e.target.value = value;
            });
        }


        // ========================================
        // SET KODE NEGARA & NOMOR SAAT FORM LOAD
        // ========================================
        document.addEventListener('DOMContentLoaded', function () {
            const kodeNegara = document.getElementById('kodeNegara');
            const nohp = document.getElementById('nohp');

            if (!kodeNegara || !nohp) {
                return;
            }

            let nomor = nohp.value.replace(/[^0-9]/g, '');

            if (nomor === '') {
                return;
            }

            let kodeTerpilih = '';
            Array.from(kodeNegara.options).forEach(function (option) {
                const kode = option.value;

                if (nomor.startsWith(kode) && kode.length > kodeTerpilih.length) {
                    kodeTerpilih = kode;
                }
            });

            if (kodeTerpilih !== '') {
                kodeNegara.value = kodeTerpilih;
                // Tampilkan hanya nomor setelah kode negara
                nohp.value = nomor.substring(kodeTerpilih.length);
            }
        });

        let registerForm = document.querySelector('form[action="/register"]');

        if (registerForm) {
            registerForm.addEventListener(
                'submit',
                function (e) {
                    let npwp = document.getElementById('npwp') ?.value || '';
                    let nohp = document.getElementById('nohp') ?.value || '';
                    let kodeNegara = document.getElementById('kodeNegara') ?.value || '';

                    if (npwp.length !== 16) {
                        alert('NPWP harus 16 digit');
                        e.preventDefault();
                        return;
                    }

                    if (!kodeNegara) {
                        alert('Silakan pilih kode negara');
                        e.preventDefault();
                        return;
                    }

                    if (nohp.length < 10) {
                        alert('No HP tidak valid');
                        e.preventDefault();
                        return;
                    }
                }
            );
        }

        button_backToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            window.location.href = '/';
        });
    </script>
</body>

</html>
