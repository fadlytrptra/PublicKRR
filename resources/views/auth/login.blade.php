@extends('layouts.app')

@section('content')

<link rel="stylesheet" href="{{ asset('css/login.css') }}">

<div class="container mt-5">
    <div class="card">
        <div class="card-header">Login</div>

        <div class="card-body">
            {{-- SUCCESS --}}
            @if(session('success'))
                <div class="success">
                    {{ session('success') }}
                </div>
            @endif

            {{-- ERROR --}}
            @error('error')
                <div style="color:red; background:#ffe5e5; padding:10px; border-radius:5px; margin-bottom:10px;">
                    {{ $message }}
                </div>
            @enderror

            <form method="POST" action="/login">
                @csrf

                {{-- LOGIN --}}
                <div class="input-group mb-3">
                    <div class="icon">
                        <span class="material-icons">person</span>
                    </div>

                    <input type="text"
                        name="login"
                        placeholder="Email"
                        value="{{ old('login') }}"
                        class="@error('login') input-error @enderror">
                </div>

                @error('login')
                    <div class="error">
                        {{ $message }}
                    </div>
                @enderror

                {{-- PASSWORD --}}
                <div class="input-group mb-3 password-wrapper">
                    <div class="icon">
                        <span class="material-icons">lock</span>
                    </div>

                    <input type="password"
                        id="password"
                        name="Password"
                        placeholder="Password"
                        class="@error('Password') input-error @enderror">

                    <div class="toggle-password" onclick="togglePassword()">
                        <span class="material-icons" id="eyeIcon">visibility</span>
                    </div>
                </div>

                @error('Password')
                    <div class="error">
                        {{ $message }}
                    </div>
                @enderror

                <button type="submit" class="btn btn-login">
                    Login
                </button>

                <button type="button"
                    class="btn btn-register"
                    onclick="window.location='/register'">
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

{{-- MODAL FORGOT PASSWORD --}}
<div id="modalForgot"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999;">

    <div style="background:white; width:400px; margin:100px auto; padding:20px; border-radius:8px;">

        <h3 class="modal-title">Lupa Password</h3>

        @error('forgot_error')
            <div style="color:red">
                {{ $message }}
            </div>
        @enderror

        <form method="POST" action="/forgot-password">
            @csrf

            <input type="text"
                name="login"
                placeholder="Email"
                class="input-modal">

            <button type="submit" class="btn btn-login">
                Reset Password
            </button>
        </form>

        @if(session('generated_password'))
            <div style="background:#e6ffed; padding:10px; border-radius:5px; margin-top:10px; color:#155724;">
                <strong>Password Baru:</strong><br>

                <span style="font-size:16px; font-weight:bold;">
                    {{ session('generated_password') }}
                </span>
            </div>
        @endif

        <button type="button"
            class="btn-close"
            onclick="closeModal()">

            Close
        </button>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modalForgot').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('modalForgot').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {

        @if($errors->has('forgot_error') || session('generated_password'))
            openModal();
        @endif

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('modalForgot');

            if (event.target === modal) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    });

    function togglePassword() {
        const password = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (password.type === 'password') {
            password.type = 'text';
            eyeIcon.textContent = 'visibility_off';
        } else {
            password.type = 'password';
            eyeIcon.textContent = 'visibility';
        }
    }
</script>

@endsection
