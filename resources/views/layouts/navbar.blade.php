<!-- Navbar -->

<nav
    class="layout-navbar container-xxl navbar navbar-expand-xl navbar-detached align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)">
            <i class="fa fa-menu bx-sm"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center" id="navbar-collapse">
        <!-- Global Search -->
        <div class="navbar-nav align-items-center global-search-wrapper" id="globalSearchWrapper">
            <div class="nav-item d-flex align-items-center w-100">
                <i class="fa fa-search fs-4 lh-0"></i>
                <input
                    type="text"
                    id="globalSearchInput"
                    class="form-control border-0 shadow-none"
                    placeholder="Search suppliers, products, orders, purchases..."
                    aria-label="Global search"
                    autocomplete="off"
                    data-search-url="{{ route('search.global') }}" />
            </div>
            <div id="globalSearchDropdown" class="global-search-dropdown d-none"></div>
        </div>
        <!-- /Global Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            @canAccess('pos.access')
                <li class="nav-item me-3 d-none d-md-block">
                    <a href="{{ url('admin/pos-screen') }}" class="btn btn-primary btn-sm">
                        <i class="fa fa-cash-register me-1"></i> POS
                    </a>
                </li>
            @endcanAccess
            <!-- Notifications -->
            @can('notification.view')
                <li class="nav-item navbar-dropdown dropdown-notifications dropdown me-3">
                    <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                        <i class="fa fa-bell fs-4"></i>
                        <span class="badge bg-danger rounded-pill d-none" id="notificationBadge"
                            style="position:relative; top:-10px; left:-6px; font-size:.65rem;">0</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 380px; max-height: 420px; overflow-y: auto;">
                        <li>
                            <div class="dropdown-header d-flex align-items-center justify-content-between py-2 px-3">
                                <h6 class="mb-0">Notifications</h6>
                                <a href="javascript:void(0);" class="text-body" id="markAllReadDropdownBtn" title="Mark all as read">
                                    <i class="fa fa-check-double"></i>
                                </a>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li id="notificationListContainer">
                            <div class="text-center text-muted py-4">No notifications</div>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                        <li>
                            <a href="{{ url('admin/notifications') }}" class="dropdown-item d-flex justify-content-center p-2 text-primary">
                                View all notifications
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan
            <!--/ Notifications -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{asset('public/assets/img/avatars/1.png')}}" alt class="w-px-40 h-auto rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">
                                    <div class="avatar avatar-online">
                                        <img src="{{asset('public/assets/img/avatars/1.png')}}" alt class="w-px-40 h-auto rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <span class="fw-semibold d-block">{{auth()->user()->name??'Admin'}}</span>
                                    <small class="text-muted">{{getRoleName()}}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ url('admin/profile') }}">
                            <i class="fa fa-user me-2"></i>
                            <span class="align-middle">My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ url('admin/setting') }}">
                            <i class="fa fa-cog me-2"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#">
                            <span class="d-flex align-items-center align-middle">
                                <i class="flex-shrink-0 fa fa-credit-card me-2"></i>
                                <span class="flex-grow-1 align-middle">Billing</span>
                                <span class="flex-shrink-0 badge badge-center rounded-pill bg-danger w-px-20 h-px-20">4</span>
                            </span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider"></div>
                    </li>
                    <li>
                        <a href="{{ route('logout') }}" class="dropdown-item"
                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                            <i class="fa fa-power-off me-2"></i> {{ __('Logout') }}
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

<!-- / Navbar -->

    <style>
        .navbar-nav-right {
            flex-wrap: nowrap;
        }
        .global-search-wrapper {
            position: relative;
            flex: 1 1 auto;
            max-width: 480px;
            min-width: 160px;
        }
        .global-search-dropdown {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            width: 380px;
            max-width: 90vw;
            max-height: 460px;
            overflow-y: auto;
            background: var(--bs-body-bg, #fff);
            border: 1px solid var(--bs-border-color, rgba(0, 0, 0, .1));
            border-radius: .5rem;
            box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .15);
            z-index: 1090;
            padding: .5rem 0;
        }
        .global-search-dropdown .search-group-label {
            padding: .4rem 1rem .2rem;
            font-size: .7rem;
            font-weight: 600;
            letter-spacing: .04em;
            text-transform: uppercase;
            color: var(--bs-secondary-color, #8592a3);
        }
        .global-search-dropdown .search-result-item {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .5rem 1rem;
            color: inherit;
            text-decoration: none;
        }
        .global-search-dropdown .search-result-item:hover,
        .global-search-dropdown .search-result-item.active {
            background: var(--bs-tertiary-bg, rgba(0, 0, 0, .04));
        }
        .global-search-dropdown .search-result-item i {
            width: 18px;
            text-align: center;
            color: var(--bs-secondary-color, #8592a3);
            flex-shrink: 0;
        }
        .global-search-dropdown .search-result-text {
            flex-grow: 1;
            min-width: 0;
        }
        .global-search-dropdown .search-result-title {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .global-search-dropdown .search-result-subtitle {
            font-size: .78rem;
            color: var(--bs-secondary-color, #8592a3);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .global-search-dropdown .search-more-note {
            padding: .25rem 1rem .5rem;
            font-size: .75rem;
            color: var(--bs-secondary-color, #8592a3);
        }
        .global-search-dropdown .search-state-message {
            padding: 1.5rem 1rem;
            text-align: center;
            color: var(--bs-secondary-color, #8592a3);
        }
    </style>

@can('notification.view')
    @push('js')
        <script>
            (function() {
                let soundEnabled = {{ session('notification_setting.sound_enabled', true) ? 'true' : 'false' }};
                let lastUnreadCount = null;
                const unreadCountUrl = "{{ route('notifications.unread-count') }}";
                const latestUrl = "{{ route('notifications.latest') }}";
                const markReadUrlBase = "{{ url('admin/notifications') }}";
                const markAllReadUrl = "{{ route('notifications.mark-all-read') }}";

                function playNotificationBeep() {
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

                function renderNotificationDropdown(items) {
                    const $container = $('#notificationListContainer');
                    if (!items || items.length === 0) {
                        $container.html('<div class="text-center text-muted py-4">No notifications</div>');
                        return;
                    }
                    let html = '';
                    items.forEach(function(item) {
                        const unreadClass = item.is_read ? '' : 'bg-label-primary';
                        html += '<a href="javascript:void(0);" class="dropdown-item d-flex flex-column py-2 px-3 border-bottom notification-item ' + unreadClass + '" data-id="' + item.id + '" data-url="' + (item.url || '') + '">' +
                            '<span class="fw-semibold small">' + item.title + '</span>' +
                            '<span class="small text-muted text-truncate d-block" style="max-width:340px;">' + item.message + '</span>' +
                            '<span class="small text-muted">' + item.date + '</span>' +
                            '</a>';
                    });
                    $container.html(html);

                    $('.notification-item').off('click').on('click', function() {
                        const id = $(this).data('id');
                        const url = $(this).data('url');
                        $.post(markReadUrlBase + '/' + id + '/read', {
                            _token: "{{ csrf_token() }}"
                        }).always(function() {
                            if (url) {
                                window.location.href = url;
                            } else {
                                refreshNotificationBell();
                            }
                        });
                    });
                }

                window.refreshNotificationBell = function() {
                    $.getJSON(unreadCountUrl, function(res) {
                        const count = res.count || 0;
                        const $badge = $('#notificationBadge');

                        if (count > 0) {
                            $badge.text(count > 99 ? '99+' : count).removeClass('d-none');
                        } else {
                            $badge.addClass('d-none');
                        }

                        if (lastUnreadCount !== null && count > lastUnreadCount) {
                            playNotificationBeep();
                        }
                        lastUnreadCount = count;
                    });

                    $.getJSON(latestUrl, function(res) {
                        renderNotificationDropdown(res.data || []);
                    });
                };

                $(function() {
                    refreshNotificationBell();
                    setInterval(refreshNotificationBell, 30000);

                    $('#markAllReadDropdownBtn').on('click', function(e) {
                        e.preventDefault();
                        $.post(markAllReadUrl, {
                            _token: "{{ csrf_token() }}"
                        }).always(function() {
                            refreshNotificationBell();
                        });
                    });
                });
            })();
        </script>
    @endpush
@endcan