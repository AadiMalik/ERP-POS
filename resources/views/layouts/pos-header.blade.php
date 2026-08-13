@php
    $is_fixed_context = $is_fixed_context ?? false;
    $show_pos_actions = $show_pos_actions ?? false;
@endphp
<!-- POS Header - dedicated to the POS screen, intentionally not the admin navbar/sidebar -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm px-3" id="pos-navbar">
    <a href="{{ route('pos-screen') }}" class="navbar-brand fw-bold d-flex align-items-center mb-0">
        <i class="fa fa-cash-register me-2"></i> POS
    </a>

    <div class="d-flex align-items-center ms-auto gap-2 flex-wrap">
        @if ($show_pos_actions)
            <button type="button" class="btn btn-sm btn-outline-primary" id="orderHistoryBtn">
                <i class="fa fa-receipt"></i> Order History
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" id="posReportsBtn">
                <i class="fa fa-chart-bar"></i> Reports
            </button>
            <span id="registerBadge" class="badge bg-label-secondary d-none"></span>
            <button type="button" class="btn btn-sm btn-outline-success d-none" id="cashInBtn">
                <i class="fa fa-plus"></i> Cash
            </button>
            <button type="button" class="btn btn-sm btn-outline-warning d-none" id="cashOutBtn">
                <i class="fa fa-minus"></i> Cash
            </button>
            <button type="button" class="btn btn-sm btn-outline-danger d-none" id="closeRegisterBtn">
                Close Register
            </button>
        @endif

        @if (!$is_fixed_context)
            <a href="{{ route('home') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Switch to Admin Panel
            </a>
        @endif

        <div class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center" href="javascript:void(0);"
                data-bs-toggle="dropdown">
                <div class="avatar avatar-online">
                    <img src="{{ asset('public/assets/img/avatars/1.png') }}" alt class="w-px-32 h-auto rounded-circle" />
                </div>
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
