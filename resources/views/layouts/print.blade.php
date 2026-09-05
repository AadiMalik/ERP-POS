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
    {{-- ?auto=1: loaded inside a hidden iframe right after a POS sale
         (see silentPrintReceipt() in pos-screen.js), which itself calls
         window.print() once the iframe finishes loading - so the toolbar
         is pointless here and is skipped entirely. --}}
    @unless(request()->boolean('auto'))
    <div class="print-toolbar no-print">
        <button type="button" onclick="window.print()">{{ __('common.print') }}</button>
        <button type="button" onclick="window.close()">{{ __('common.close') }}</button>
    </div>
    @endunless

    <div class="print-page @yield('page_class')">
        @yield('content')
    </div>
</body>

</html>
