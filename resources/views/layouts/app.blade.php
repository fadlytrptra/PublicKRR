<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"> <!-- CSRF Token -->

    <link rel="icon" href="{{ asset('/images/krr.png') }}" type="image/gif" sizes="17x15">
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
</head>

<body>
    <div>
        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>

</html>
