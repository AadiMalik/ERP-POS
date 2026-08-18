@php
    $is_fixed_context = $is_fixed_context ?? false;
    $show_pos_actions = $show_pos_actions ?? false;
@endphp
<!-- POS Header - dedicated to the POS screen, intentionally not the admin navbar/sidebar -->
<nav class="navbar navbar-expand pos-navbar px-3" id="pos-navbar">
    <a href="{{ route('pos-screen') }}" class="navbar-brand d-flex align-items-center mb-0 pos-brand">
        <span class="pos-brand-icon"><i class="fa fa-cash-register"></i></span>
        <span class="pos-brand-text">
            <span class="pos-brand-name">{{ config('app.name', 'POS') }}</span>
            <span class="pos-brand-welcome">Welcome, {{ auth()->user()->name ?? '' }}</span>
        </span>
    </a>

    <div class="d-flex align-items-center ms-auto gap-2 flex-wrap pos-header-actions">
        @if ($show_pos_actions)
            <div id="registerBadge" class="pos-register-badge d-none"></div>

            <button type="button" class="btn btn-icon btn-sm btn-outline-success d-none" id="cashInBtn" title="Cash In">
                <i class="fa fa-plus"></i>
            </button>
            <button type="button" class="btn btn-icon btn-sm btn-outline-warning d-none" id="cashOutBtn" title="Cash Out">
                <i class="fa fa-minus"></i>
            </button>

            <a href="{{ route('order.history') }}" target="_blank" class="btn btn-sm pos-header-btn">
                <i class="fa fa-clock-rotate-left"></i> History
            </a>
            <button type="button" class="btn btn-sm pos-header-btn" id="posReportsBtn">
                <i class="fa fa-chart-bar"></i> Reports
            </button>
            <button type="button" class="btn btn-sm pos-header-btn d-none" id="closeRegisterBtn">
                <i class="fa fa-lock"></i> Close Register
            </button>
            <button type="button" class="btn btn-sm pos-header-btn position-relative" id="heldOrdersBtn">
                <i class="fa fa-pause"></i> Hold Orders
                <span class="badge rounded-pill bg-danger pos-held-count" id="heldOrdersCount">0</span>
            </button>

            <a href="{{ url('admin/setting') }}" target="_blank" class="btn btn-sm pos-header-btn">
                <i class="fa fa-gear"></i> Settings
            </a>
        @endif

        @if (!$is_fixed_context)
            <a href="{{ route('home') }}" class="btn btn-sm pos-header-btn">
                <i class="fa fa-arrow-left"></i> Switch to Admin Panel
            </a>
        @endif

        <div class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center pos-user-toggle" href="javascript:void(0);"
                data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="{{ asset('public/assets/img/avatars/1.png') }}" alt class="w-px-32 h-auto rounded-circle" />
                </div>
                <span class="pos-user-info d-none d-md-flex">
                    <span class="pos-user-name">{{ auth()->user()->name ?? '' }}</span>
                    <span class="pos-user-role">{{ getRoleName() }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text">
                        <span class="fw-semibold d-block">{{ auth()->user()->name ?? '' }}</span>
                        <small class="text-muted">{{ getRoleName() }}</small>
                    </span>
                </li>
                <li>
                    <div class="dropdown-divider"></div>
                </li>
                <li>
                    <a href="{{ route('logout') }}" class="dropdown-item"
                        onclick="event.preventDefault(); document.getElementById('pos-logout-form').submit();">
                        <i class="fa fa-power-off me-2"></i> {{ __('Logout') }}
                    </a>
                    <form id="pos-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                        @csrf
                    </form>
                </li>
            </ul>
        </div>
    </div>
</nav>
