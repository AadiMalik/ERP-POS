<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Print')</title>
    <link rel="stylesheet" href="{{ asset('public/assets/css/print.css') }}">
    @yield('css')
</head>

<body>
    <div class="print-toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
    </div>

    <div class="print-page @yield('page_class')">
        @yield('content')
    </div>
</body>

</html>
