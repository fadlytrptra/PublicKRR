<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Surat Jalan ACC')</title>
    <link rel="icon" href="{{ asset('images/KRR.png') }}" type="image/png">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" rel="stylesheet">
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body class="bg-light">

    <!-- NAVBAR -->
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

                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                        fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5m0-8c1.65 0 3 1.35 3 3s-1.35 3-3 3-3-1.35-3-3 1.35-3 3-3M4 22h16c.55 0 1-.45 1-1v-1c0-3.86-3.14-7-7-7h-4c-3.86 0-7 3.14-7 7v1c0 .55.45 1 1 1m6-7h4c2.76 0 5 2.24 5 5H5c0-2.76 2.24-5 5-5"></path>
                    </svg>

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

    <!-- CONTENT -->
    <div class="container py-4">
        @yield('content')
    </div>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
    </script>

    @stack('scripts')

</body>
</html>

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

