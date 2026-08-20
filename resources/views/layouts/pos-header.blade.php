@php
    $is_fixed_context = $is_fixed_context ?? false;
    $show_pos_actions = $show_pos_actions ?? false;
    $business = $business ?? null;
    $branch_name = $branch_name ?? null;
    $warehouse_name = $warehouse_name ?? null;

    $business_logo_url = !empty($business->logo)
        ? asset('public/uploads/business/' . $business->logo)
        : asset('public/assets/img/no-image.png');
    $business_display_name = $business->name ?? config('app.name', 'POS');

    $user_name = auth()->user()->name ?? '';
    $user_initials = collect(explode(' ', trim($user_name)))
        ->filter()
        ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
        ->take(2)
        ->implode('');
    $user_initials = $user_initials !== '' ? $user_initials : 'U';
@endphp
<!-- POS Header - dedicated to the POS screen, intentionally not the admin navbar/sidebar -->
<nav class="navbar navbar-expand pos-navbar px-3" id="pos-navbar">
    <a href="{{ route('pos-screen') }}" class="navbar-brand d-flex align-items-center mb-0 pos-brand">
        <img src="{{ $business_logo_url }}" alt="{{ $business_display_name }}" class="pos-brand-logo" style="max-height: 50px;">
        <span class="pos-brand-text">
            <span class="pos-brand-name" title="{{ $business_display_name }}">{{ $business_display_name }}</span>
            <span class="pos-brand-welcome">Welcome, {{ $user_name }}</span>
        </span>
    </a>

    @if (!$show_pos_actions && ($branch_name || $warehouse_name))
        <div class="pos-context-cluster">
            @if ($branch_name)
                <span class="pos-context-chip" title="Selected Branch">
                    <i class="fa fa-code-branch"></i>
                    <span class="pos-context-label d-none d-md-inline">Branch:</span>
                    {{ $branch_name }}
                </span>
            @endif
            @if ($warehouse_name)
                <span class="pos-context-chip" title="Selected Warehouse">
                    <i class="fa fa-warehouse"></i>
                    <span class="pos-context-label d-none d-md-inline">Warehouse:</span>
                    {{ $warehouse_name }}
                </span>
            @endif
        </div>
    @endif

    <div class="d-flex align-items-center ms-auto gap-2 flex-wrap pos-header-actions">
        @if ($show_pos_actions)
            <div id="registerBadge" class="pos-register-badge d-none"></div>

            <button type="button" class="btn btn-icon btn-sm btn-outline-success d-none" id="cashInBtn" title="Cash In">
                <i class="fa fa-plus"></i>
            </button>
            <button type="button" class="btn btn-icon btn-sm btn-outline-warning d-none" id="cashOutBtn" title="Cash Out">
                <i class="fa fa-minus"></i>
            </button>
            <button type="button" class="btn btn-icon btn-sm btn-outline-danger d-none" id="addExpenseBtn" title="Add Expense">
                <i class="fa fa-receipt"></i>
            </button>

            <a href="{{ route('order.history') }}" target="_blank" class="btn btn-sm pos-header-btn" title="History">
                <i class="fa fa-clock-rotate-left"></i> <span class="pos-header-btn-label">History</span>
            </a>
            <button type="button" class="btn btn-sm pos-header-btn" id="posReportsBtn" title="Reports">
                <i class="fa fa-chart-bar"></i> <span class="pos-header-btn-label">Reports</span>
            </button>
            <button type="button" class="btn btn-sm pos-header-btn d-none" id="closeRegisterBtn" title="Close Register">
                <i class="fa fa-lock"></i> <span class="pos-header-btn-label">Close Register</span>
            </button>
            <button type="button" class="btn btn-sm pos-header-btn position-relative" id="heldOrdersBtn" title="Hold Orders">
                <i class="fa fa-pause"></i> <span class="pos-header-btn-label">Hold Orders</span>
                <span class="badge rounded-pill bg-danger pos-held-count" id="heldOrdersCount">0</span>
            </button>

            <a href="{{ url('admin/setting') }}" target="_blank" class="btn btn-sm pos-header-btn" title="Settings">
                <i class="fa fa-gear"></i> <span class="pos-header-btn-label">Settings</span>
            </a>
        @endif

        @if (!$is_fixed_context)
            <a href="{{ route('home') }}" class="btn btn-sm pos-header-btn" title="Switch to Admin Panel">
                <i class="fa fa-arrow-left"></i> <span class="pos-header-btn-label">Switch to Admin Panel</span>
            </a>
        @endif

        <div class="nav-item navbar-dropdown dropdown-user dropdown">
            <a class="nav-link dropdown-toggle hide-arrow d-flex align-items-center pos-user-toggle" href="javascript:void(0);"
                data-bs-toggle="dropdown">
                <div class="avatar avatar-online pos-user-avatar">
                    <span class="avatar-initial rounded-circle">{{ $user_initials }}</span>
                </div>
                <span class="pos-user-info d-none d-md-flex">
                    <span class="pos-user-name">{{ $user_name }}</span>
                    <span class="pos-user-role">{{ getRoleName() }}</span>
                </span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text">
                        <span class="fw-semibold d-block">{{ $user_name }}</span>
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
