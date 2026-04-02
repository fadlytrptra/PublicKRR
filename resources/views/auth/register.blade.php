<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <style>
        body {
            font-family: Arial;
            background-color: #f5f5f5;
        }
        .container {
            width: 400px;
            margin: 50px auto;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        input, textarea {
            width: 100%;
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
            margin-top: 10px;
            padding: 10px;
            background: #4d5358;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.2s;
        }

        .btn-back:hover {
            background: #5a6268;
        }
        .register {
            font-size: 17px;
        }

        .input-group {
            position: relative;
        }

        .input-group input {
            width: 100%;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #555;
        }

        .toggle-password:hover {
            color: #000;
        }

    </style>
</head>
<body>
    <div class="container">
        <h2>Register</h2>

        {{-- ERROR GLOBAL --}}
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

            <label>Email</label>
            <input type="email" name="Email"
                value="{{ old('Email', $data['Email'] ?? '') }}">
            @error('Email')
                <div class="error">{{ $message }}</div>
            @enderror

            <label>Nama User</label>
            <input type="text" name="NamaUser"
                value="{{ old('NamaUser', $data['NamaUser'] ?? '') }}">
            @error('NamaUser')
                <div class="error">{{ $message }}</div>
            @enderror

            <label>Nama Perusahaan</label>
            <input type="text" name="NamaPerusahaan"
                value="{{ old('NamaPerusahaan', $data['NamaPerusahaan'] ?? '') }}">
            @error('NamaPerusahaan')
                <div class="error">{{ $message }}</div>
            @enderror

            <label>Alamat Perusahaan</label>
            <textarea name="AlamatPerusahaan">{{ old('AlamatPerusahaan', $data['AlamatPerusahaan'] ?? '') }}</textarea>
            @error('AlamatPerusahaan')
                <div class="error">{{ $message }}</div>
            @enderror

            <label>No HP</label>
           <input type="text" name="NoHP"
                value="{{ old('NoHP', $data['NoHP'] ?? '') }}">
            @error('NoHP')
                <div class="error">{{ $message }}</div>
            @enderror

            {{-- <label>Tanda Tangan (TT Customer)</label>
            <input type="file" name="TTCustomer" id="TTCustomer" accept="image/*">

            @error('TTCustomer')
                <div class="error">{{ $message }}</div>
            @enderror

            <div id="preview-container" style="margin-top:10px; display:none;">
                <p>Preview Tanda Tangan:</p>
                <img id="preview-image" src="" width="150" style="border:1px solid #ccc; padding:5px;">
            </div> --}}

            <label>NPWP</label>
            <input type="text" name="NPWP"
                value="{{ old('NPWP', $data['NPWP'] ?? '') }}">
            @error('NPWP')
                <div class="error">{{ $message }}</div>
            @enderror

           <div class="password-wrapper">
                <label>Password</label>

                <div class="input-group">
                    <input type="password" name="Password" id="password">

                   <span class="toggle-password" onclick="togglePassword()">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                            fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 9a3 3 0 1 0 0 6 3 3 0 1 0 0-6"></path>
                            <path d="M12 19c7.63 0 9.93-6.62 9.95-6.68.07-.21.07-.43 0-.63-.02-.07-2.32-6.68-9.95-6.68s-9.93 6.61-9.95 6.67c-.07.21-.07.43 0 .63.02.07 2.32 6.68 9.95 6.68Zm0-12c5.35 0 7.42 3.85 7.93 5-.5 1.16-2.58 5-7.93 5s-7.42-3.84-7.93-5c.5-1.16 2.58-5 7.93-5"></path>
                        </svg>
                    </span>
                </div>

                @error('Password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="register">Register</button>
        </form>

        @if(session('showOtp'))
            <hr>

            <h3>Verifikasi OTP</h3>

            @if(session('success'))
                <div style="color: green;">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="/verify-otp">
                @csrf

                <input type="hidden" name="email" value="{{ session('email') }}">

                <label>Masukkan OTP</label>
                <input type="text" name="otp" placeholder="6 digit OTP">

                @error('otp')
                    <div class="error">{{ $message }}</div>
                @enderror

                <button type="submit">Verifikasi</button>
            </form>
        @endif

       <a href="/" class="btn-back">Kembali ke Login</a>
    </div>

<script>
    let inputFile = document.getElementById('TTCustomer');
    let previewContainer = document.getElementById('preview-container');
    let previewImage = document.getElementById('preview-image');

    inputFile.addEventListener('change', function () {
        let file = this.files[0];

        if (file) {
            let reader = new FileReader();

            reader.onload = function (e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
            }

            reader.readAsDataURL(file);
        } else {
            previewContainer.style.display = 'none';
        }
    });

    function togglePassword() {
        let input = document.getElementById("password");

        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }
</script>

</body>
</html>
