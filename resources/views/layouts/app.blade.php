<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- <meta name="csrf-token" content="{{ csrf_token() }}"> <!-- CSRF Token --> --}}

    <link rel="icon" href="{{ asset('/images/krr.png') }}" type="image/gif" sizes="17x15">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <title style="font-size: 20px">{{ config('app.name', 'Laravel') }}</title>
    <!-- Scripts -->
    <!-- JQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <!-- Datatables -->
    <script src="https://cdn.datatables.net/2.3.7/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.3.7/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdn.datatables.net/2.3.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!--Sweet Alert-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="{{ asset('js/RDZ.js')}}"></script>
</head>

<body>
    @if(!request()->is('/'))
    <nav class="navbar navbar-light bg-white shadow sticky-top px-4 py-3">
        <div>
            <a href="{{ url('/home') }}" class="text-decoration-none text-dark fw-bold">
                Home
            </a>
        </div>

        <div class="ms-auto d-flex align-items-center gap-2">
            @if(session('user'))
                <a href="#"
                data-bs-toggle="modal"
                data-bs-target="#profileModal"
                class="d-flex align-items-center gap-1 text-decoration-none text-dark">

                    <!-- ICON -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3M4 22h16c.55 0 1-.45 1-1v-1c0-3.86-3.14-7-7-7h-4c-3.86 0-7 3.14-7 7v1c0 .55.45 1 1 1m6-7h4c2.76 0 5 2.24 5 5H5c0-2.76 2.24-5 5-5"></path>
                    </svg>

                    <!-- NAMA -->
                    <span>{{ session('user')->NamaUser }}</span>
                </a>

                |

                <form action="{{ url('/logout') }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit" class="no-border nav_font">
                        Logout
                    </button>
                </form>
            @endif
        </div>
    </nav>
    @endif

    <div class="w-100">
        @if(session('success'))
            <div class="alert alert-success rounded-0 text-start">
                {{ session('success') }}
            </div>
        @endif
    </div>


    <main class="py-4">
        @yield('content')
    </main>

    @if($errors->any())
    <script>
        var modal = new bootstrap.Modal(document.getElementById('profileModal'));
        modal.show();
    </script>
    @endif
</body>

<style>
    .no-border {
        border: none;
        background: none;
        padding: 0;
        margin: 0;
        color: #000308;
        font-weight: bold;
        cursor: pointer;
    }

    .nav_font {
        font-size: 19px;
    }
</style>

</html>

@include('profile_modal')
