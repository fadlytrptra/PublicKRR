<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <!-- MATERIAL ICON -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <style>
        body {
            font-family: Arial;
            background-color: #f1f3f5;
        }

        .container {
            width: 600px;
            margin: 80px auto;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 15px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            background: #f7f7f7;
        }

        .card-body {
            padding: 20px;
        }

        .input-group {
            display: flex;
            margin-bottom: 15px;
        }

        .input-group .icon {
            background: #e9ecef;
            padding: 10px;
            display: flex;
            align-items: center;
            border: 1px solid #ccc;
            border-right: none;
        }

        .input-group input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ccc;
            outline: none;
        }

        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
        }

        .btn-login {
            background: #3b82c4;
            font-size: 16px;
        }

        .btn-login:hover {
            background: #2f6fa3;
        }

        .btn-register {
            background: #343a40;
            font-size: 16px;
        }

        .btn-register:hover {
            background: #23272b;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">
        <div class="card-header">Login</div>

        <div class="card-body">

            {{-- SUCCESS --}}
            @if(session('success'))
                <div class="success">{{ session('success') }}</div>
            @endif

            {{-- ERROR --}}
            @error('error')
                <div style="color: red; background: #ffe5e5; padding:10px; border-radius:5px; margin-bottom:10px;">
                    {{ $message }}
                </div>
            @enderror


            <form method="POST" action="/login">
                @csrf

                {{-- LOGIN (EMAIL / USERNAME) --}}
                <div class="input-group">
                    <div class="icon">
                        <span class="material-icons">person</span>
                    </div>
                    <input type="text" name="login" placeholder="Email / Username"
                        value="{{ old('login') }}">
                </div>

                {{-- PASSWORD --}}
                <div class="input-group">
                    <div class="icon">
                        <span class="material-icons">lock</span>
                    </div>
                    <input type="password" name="Password" placeholder="Password">
                </div>

                <button type="submit" class="btn btn-login">Login</button>

                <button type="button" class="btn btn-register" onclick="window.location='/register'">
                    Register
                </button>

            </form>

        </div>
    </div>
</div>

</body>
</html>
