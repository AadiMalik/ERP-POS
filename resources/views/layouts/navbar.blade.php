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
        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item d-flex align-items-center">
                <i class="fa fa-search fs-4 lh-0"></i>
                <input
                    type="text"
                    class="form-control border-0 shadow-none"
                    placeholder="Search..."
                    aria-label="Search..." />
            </div>
        </div>
        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-auto">
            <!-- Place this tag where you want the button to render. -->
            <li class="nav-item lh-1 me-3">
                <a
                    class="github-button"
                    href="https://github.com/themeselection/sneat-html-admin-template-free"
                    data-icon="octicon-star"
                    data-size="large"
                    data-show-count="true"
                    aria-label="Star themeselection/sneat-html-admin-template-free on GitHub">Star</a>
            </li>

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
                        <a class="dropdown-item" href="#">
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