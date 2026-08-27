<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    @include('partials.favicon')
    <title>{{ config('app.name', 'Dukanaz') }} — @yield('title', 'Login')</title>

    <link rel="stylesheet" href="{{ asset('public/assets/vendor/fonts/boxicons.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/vendor/css/core.css') }}" class="template-customizer-core-css" />
    <link rel="stylesheet" href="{{ asset('public/assets/vendor/css/theme-default.css') }}" class="template-customizer-theme-css" />
    <link rel="stylesheet" href="{{ asset('public/assets/css/demo.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
    <link rel="stylesheet" href="{{ asset('public/assets/vendor/css/pages/page-auth.css') }}" />
    <style>
        .dukanaz-brand-logo--login {
            overflow: visible;
        }
        .dukanaz-brand-img--login {
            width: 72px;
            height: 72px;
            max-height: 72px;
            max-width: 72px;
            border-radius: 16px;
            object-fit: cover;
        }
        .dukanaz-wordmark {
            font-weight: 700;
            color: #0B1B32;
        }
        .dukanaz-accent {
            color: #2DD4BF;
        }
        .js-password-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
        }
    </style>
    <script src="{{ asset('public/assets/vendor/js/helpers.js') }}"></script>
</head>

<body>
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center mb-4">
                            @include('partials.brand-logo', ['variant' => 'login'])
                        </div>
                        @yield('content')
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('public/assets/vendor/libs/jquery/jquery.js') }}"></script>
    <script src="{{ asset('public/assets/vendor/libs/popper/popper.js') }}"></script>
    <script src="{{ asset('public/assets/vendor/js/bootstrap.js') }}"></script>
    <script src="{{ asset('public/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
    <script src="{{ asset('public/assets/vendor/js/menu.js') }}"></script>
    <script src="{{ asset('public/assets/js/main.js') }}"></script>
    <script src="{{ asset('public/assets/js/password-toggle.js') }}"></script>
</body>

</html>
