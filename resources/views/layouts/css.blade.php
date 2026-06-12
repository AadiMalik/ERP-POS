<!-- Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link
    href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap"
    rel="stylesheet" />

<!-- Icons. Uncomment required icon fonts -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />

<!-- Core CSS -->
<link rel="stylesheet" href="{{asset('public/assets/vendor/css/core.css')}}" class="template-customizer-core-css" />
<link rel="stylesheet" href="{{asset('public/assets/vendor/css/theme-default.css')}}" class="template-customizer-theme-css" />
<link rel="stylesheet" href="{{asset('public/assets/css/demo.css')}}" />

<!-- Vendors CSS -->
<link rel="stylesheet" href="{{asset('public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css')}}" />

<link rel="stylesheet" href="{{asset('public/assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="https://cdn.datatables.net/2.3.8/css/dataTables.dataTables.min.css" />
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Helpers -->
<script src="{{ asset('public/assets/vendor/js/helpers.js')}}"></script>
<script src="{{ asset('public/assets/js/config.js')}}"></script>
@yield('css')