<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - POS</title>
    @include('partials.favicon')
    @include('layouts/css')
    @yield('css')
    <style>
        /* POS is a dedicated, full-screen, non-scrolling interface - no admin
           sidebar/navbar/footer chrome. The header and footer keep their
           natural height; .pos-content-wrapper takes exactly what's left of
           the viewport, so the screen itself (not the page) owns all
           internal scrolling. */
        html, body { height: 100%; overflow: hidden; }
        body { background: #f5f5f9; display: flex; flex-direction: column; }
        .pos-content-wrapper { flex: 1 1 auto; min-height: 0; overflow: hidden; }
        .pos-footer { flex: 0 0 auto; }
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
