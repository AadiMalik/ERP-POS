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

            @can('pos.access')
                <div class="nav-item navbar-dropdown dropdown-notifications dropdown">
                    <a class="btn btn-icon btn-sm pos-header-btn dropdown-toggle hide-arrow position-relative" href="javascript:void(0);" data-bs-toggle="dropdown" title="Online Orders">
                        <i class="fa fa-bell"></i>
                        <span class="badge bg-danger rounded-pill d-none" id="posNotificationBadge"
                            style="position:absolute; top:-2px; right:-2px; font-size:.6rem;">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 340px; max-height: 400px; overflow-y: auto;">
                        <li>
                            <div class="dropdown-header py-2 px-3"><h6 class="mb-0">Online Orders</h6></div>
                        </li>
                        <li><div class="dropdown-divider"></div></li>
                        <li id="posNotificationListContainer">
                            <div class="text-center text-muted py-4">No new orders</div>
                        </li>
                    </ul>
                </div>
            @endcan

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

@can('pos.access')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let soundEnabled = {{ session('notification_setting.sound_enabled', true) ? 'true' : 'false' }};
            let lastUnreadCount = null;
            const unreadCountUrl = "{{ route('pos-screen.notifications.unread-count') }}";
            const latestUrl = "{{ route('pos-screen.notifications.latest') }}";
            const markReadUrlBase = "{{ url('admin/notifications') }}";

            function playPosNotificationBeep() {
                if (!soundEnabled) return;
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = ctx.createOscillator();
                    const gain = ctx.createGain();
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(880, ctx.currentTime);
                    gain.gain.setValueAtTime(0.15, ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                    oscillator.connect(gain);
                    gain.connect(ctx.destination);
                    oscillator.start();
                    oscillator.stop(ctx.currentTime + 0.3);
                } catch (e) {}
            }

            function renderPosNotifications(items) {
                const $container = $('#posNotificationListContainer');
                if (!items || items.length === 0) {
                    $container.html('<div class="text-center text-muted py-4">No new orders</div>');
                    return;
                }
                let html = '';
                items.forEach(function(item) {
                    const unreadClass = item.is_read ? '' : 'bg-label-primary';
                    html += '<a href="javascript:void(0);" class="dropdown-item d-flex flex-column py-2 px-3 border-bottom pos-notification-item ' + unreadClass + '" data-id="' + item.id + '" data-url="' + (item.url || '') + '">' +
                        '<span class="fw-semibold small">' + item.title + '</span>' +
                        '<span class="small text-muted text-truncate d-block" style="max-width:300px;">' + item.message + '</span>' +
                        '<span class="small text-muted">' + item.date + '</span>' +
                        '</a>';
                });
                $container.html(html);

                $('.pos-notification-item').off('click').on('click', function() {
                    const id = $(this).data('id');
                    const url = $(this).data('url');
                    $.post(markReadUrlBase + '/' + id + '/read', {
                        _token: "{{ csrf_token() }}"
                    }).always(function() {
                        if (url) {
                            window.location.href = url;
                        } else {
                            refreshPosNotifications();
                        }
                    });
                });
            }

            function refreshPosNotifications() {
                $.getJSON(unreadCountUrl, function(res) {
                    const count = res.count || 0;
                    const $badge = $('#posNotificationBadge');

                    if (count > 0) {
                        $badge.text(count > 99 ? '99+' : count).removeClass('d-none');
                    } else {
                        $badge.addClass('d-none');
                    }

                    if (lastUnreadCount !== null && count > lastUnreadCount) {
                        playPosNotificationBeep();
                    }
                    lastUnreadCount = count;
                });

                $.getJSON(latestUrl, function(res) {
                    renderPosNotifications(res.data || []);
                });
            }

            refreshPosNotifications();
            setInterval(refreshPosNotifications, 30000);
        });
    </script>
@endcan
