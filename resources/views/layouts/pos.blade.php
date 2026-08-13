<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - POS</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('public/assets/img/favicon/favicon.ico') }}" />
    @include('layouts/css')
    @yield('css')
    <style>
        /* POS is a dedicated, full-screen interface - no admin sidebar/navbar/footer chrome. */
        body { background: #f5f5f9; }
        .pos-content-wrapper { min-height: calc(100vh - 56px); }
    </style>
</head>

<body>
    <!-- ======== Preloader =========== -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>

    @include('layouts/pos-header')

    <div class="pos-content-wrapper">
        @yield('content')
    </div>

    @include('layouts/pos-footer')

    @include('layouts/js')
    @yield('js')
</body>

</html>
