<!DOCTYPE html>
<html>

<head>
    <title>Kerta Rajasa Raya</title>
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

        .register {
            display: block;
            text-align: center;
            margin-top: 10px;
            padding: 10px;
            text-decoration: none;
            border-radius: 4px;
            transition: 0.2s;
            font-size: 16px;
            width: 25%;
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

        <h2>Reset Password</h2>

        @if ($errors->has('error'))
            <div class="error">
                {{ $errors->first('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('force.reset.password') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="email" id="email" value="{{ $email }}">
            <div class="password-wrapper">
                <label>Password</label>

                <div>
                    <input type="password" name="password" id="password"
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
            </div>

            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
            <button type="submit" class="btn register">Submit</button>
        </form>
    </div>
</body>
<script>
    function togglePassword() {
        let input = document.getElementById("password");

        if (input.type === "password") {
            input.type = "text";
        } else {
            input.type = "password";
        }
    }
</script>

</html>
