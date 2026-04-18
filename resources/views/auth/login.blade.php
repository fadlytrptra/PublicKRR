<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <!-- MATERIAL ICON -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">

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

                <div style="text-align:right; margin-top:10px;">
                    <a href="#"
                    style="font-size:14px; color:#dc3545; text-decoration:none;"
                    onclick="openModal()">
                        Lupa Password?
                    </a>
                </div>
            </form>

        </div>
    </div>
</div>

<div id="modalForgot" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">
    <div style="background:white; width:400px; margin:100px auto; padding:20px; border-radius:8px;">
        <h3 class="modal-title">Lupa Password</h3>

        @error('forgot_error')
            <div style="color:red">{{ $message }}</div>
        @enderror

        <form method="POST" action="/forgot-password">
            @csrf
            <input type="text" name="login" placeholder="Email / Username" class="input-modal">
            <button type="submit" class="btn btn-login">Reset Password</button>
        </form>

        @if(session('generated_password'))
            <div style="background:#e6ffed; padding:10px; border-radius:5px; margin-bottom:10px; color:#155724;">
                <strong>Password Baru:</strong><br>
                <span style="font-size:16px; font-weight:bold;">
                    {{ session('generated_password') }}
                </span>
            </div>
        @endif

        <button type="button" class="btn-close" onclick="closeModal()">Close</button>
    </div>
</div>

@if($errors->has('forgot_error'))
<script>
    window.onload = function() {
        openModal();
    }
</script>
@endif

@if(session('generated_password'))
<script>
    window.onload = function() {
        openModal();
    }
</script>
@endif

</body>
</html>

<script>
    function openModal() {
        document.getElementById('modalForgot').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('modalForgot').style.display = 'none';
    }

    // klik area gelap (overlay)
    window.onclick = function(event) {
        const modal = document.getElementById('modalForgot');
        if (event.target === modal) {
            closeModal();
        }
    }

    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    });
</script>

