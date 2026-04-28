<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    @include('layouts/css')
    @yield('css')

</head>

<body>
    <!-- ======== Preloader =========== -->
    <div id="preloader">
        <div class="spinner"></div>
    </div>
    @include('layouts/sidebar')
    <!-- ======== Preloader =========== -->
    <main class="main-wrapper">
        @include('layouts/navbar')
        @yield('content')

        @include('layouts/footer')
    </main>
    @include('layouts/js')
    @yield('js')
</body>

</html>
