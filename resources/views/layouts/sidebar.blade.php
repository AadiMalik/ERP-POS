<!-- Menu -->

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        @include('partials.brand-logo', ['variant' => 'sidebar'])

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="fa fa-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        @php
            $subscriptionLocked = false;
            $subscriptionPendingPaymentCount = 0;
            if (auth()->check() && getRoleName() == \App\Enums\RoleNames::SUPERADMIN) {
                try {
                    $subscriptionPendingPaymentCount = app(\App\Services\Concrete\Admin\InvoiceService::class)->pendingPaymentCount();
                } catch (\Throwable $e) {
                    $subscriptionPendingPaymentCount = 0;
                }
            } elseif (auth()->check() && getRoleName() != \App\Enums\RoleNames::SUPERADMIN && auth()->user()->business) {
                try {
                    $subscriptionLocked = app(\App\Services\Concrete\Admin\SubscriptionService::class)
                        ->isAccessRestricted(auth()->user()->business);
                } catch (\Throwable $e) {
                    $subscriptionLocked = false;
                }
            }
        @endphp

        @if ($subscriptionLocked)
            <li class="menu-header small text-uppercase">
                <span class="menu-header-text">Subscription</span>
            </li>
            @canAccess('my-subscription.manage')
                <li class="menu-item">
                    <a href="{{ route('my-subscription.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons fa fa-credit-card"></i>
                        <div data-i18n="My Subscription">{{ __('sidebar.my_subscription') }}</div>
                    </a>
                </li>
            @endcanAccess
            <li class="menu-item">
                <a href="{{ route('profile.edit') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-user"></i>
                    <div data-i18n="Profile">{{ __('sidebar.profile') }}</div>
                </a>
            </li>
        @else
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Main</span>
        </li>
        <!-- Dashboard -->
        <li class="menu-item">
            <a href="{{ url('/home') }}" class="menu-link">
                <i class="menu-icon tf-icons fa fa-home"></i>
                <div data-i18n="Analytics">{{ __('sidebar.dashboard') }}</div>
            </a>
        </li>
        <!-- Advanced Analytics & BI -->
        @if (businessModuleEnabled('analytics'))
        @canAccess('analytics.view')
            <li class="menu-item">
                <a href="{{ route('analytics.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-chart-pie"></i>
                    <div data-i18n="Advanced Analytics">{{ __('sidebar.analytics') }}</div>
                </a>
            </li>
        @endcanAccess
        @endif
        <!-- Self Service (Employee) -->
        @canAccessAny(['ess.dashboard.view', 'ess.attendance.manage', 'ess.leave.view', 'ess.payslip.view', 'ess.profile.view', 'ess.advance.apply', 'ess.resignation.apply'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-id-badge"></i>
                    <div data-i18n="Self Service">{{ __('sidebar.self_service') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('ess.dashboard.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.dashboard') }}" class="menu-link">
                                <div data-i18n="My Dashboard">{{ __('sidebar.my_dashboard') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.attendance.manage')
                        <li class="menu-item">
                            <a href="{{ route('ess.attendance.index') }}" class="menu-link">
                                <div data-i18n="My Attendance">{{ __('sidebar.my_attendance') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.leave.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.leave.index') }}" class="menu-link">
                                <div data-i18n="My Leave">{{ __('sidebar.my_leave') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.payslip.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.payslip.index') }}" class="menu-link">
                                <div data-i18n="My Salary Slips">{{ __('sidebar.my_salary_slips') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.advance.apply')
                        <li class="menu-item">
                            <a href="{{ route('ess.advance.index') }}" class="menu-link">
                                <div data-i18n="My Advances">{{ __('sidebar.my_advances') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.resignation.apply')
                        <li class="menu-item">
                            <a href="{{ route('ess.exit.index') }}" class="menu-link">
                                <div data-i18n="My Resignation">{{ __('sidebar.my_resignation') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.profile.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.profile.index') }}" class="menu-link">
                                <div data-i18n="My Profile">{{ __('sidebar.my_profile') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Business</span>
        </li>
        <!-- Business -->
        @canAccessAny(['package.view', 'business.view', 'branch.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-store"></i>
                    <div data-i18n="Business Manage.">{{ __('sidebar.business_manage') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('package.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/packages') }}" class="menu-link">
                                <div data-i18n="Package">{{ __('sidebar.packages') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('business.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/business') }}" class="menu-link">
                                <div data-i18n="Business">{{ __('sidebar.business') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('branch.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/branch') }}" class="menu-link">
                                <div data-i18n="Branch">{{ __('sidebar.branch') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny
        @if (getRoleName() == \App\Enums\RoleNames::SUPERADMIN)
            <!-- Subscriptions & Billing -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-credit-card"></i>
                    <div data-i18n="Subscriptions">{{ __('sidebar.subscriptions_billing') }}</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('subscriptions.dashboard') }}" class="menu-link">
                            <div data-i18n="Subscriptions">{{ __('sidebar.dashboard') }}</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('subscription-invoices.index') }}" class="menu-link">
                            <div data-i18n="Invoices">Invoices
                                @if (($subscriptionPendingPaymentCount ?? 0) > 0)
                                    <span class="badge badge-danger bg-danger rounded-pill ms-1" id="subscriptionInvoicesPendingBadge">{{ $subscriptionPendingPaymentCount }}</span>
                                @endif
                            </div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('subscription-settings.edit') }}" class="menu-link">
                            <div data-i18n="Subscription Settings">{{ __('sidebar.settings') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Backup & Restore -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-database"></i>
                    <div data-i18n="Backup & Restore">{{ __('sidebar.backup_restore') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('backups.index') }}" class="menu-link">
                            <div data-i18n="Backups">{{ __('sidebar.dashboard') }}</div>
                        </a>
                    </li>
                    <li class="menu-item">
                        <a href="{{ route('backup-settings.edit') }}" class="menu-link">
                            <div data-i18n="Backup Settings">{{ __('sidebar.settings') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Dukanaz Intro CMS -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-globe"></i>
                    <div data-i18n="Intro CMS">{{ __('sidebar.intro_cms') }}</div>
                </a>
                <ul class="menu-sub">
                    <li class="menu-item"><a href="{{ route('intro.modules.index') }}" class="menu-link"><div>Modules</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.blogs.index') }}" class="menu-link"><div>Blog</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.blog-categories.index') }}" class="menu-link"><div>Blog Categories</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.blog-tags.index') }}" class="menu-link"><div>Blog Tags</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.blog-comments.index') }}" class="menu-link"><div>Blog Comments</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.testimonials.index') }}" class="menu-link"><div>Testimonials</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.contact-inquiries.index') }}" class="menu-link"><div>Contact Inquiries</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.website-settings.index') }}" class="menu-link"><div>Website Settings</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.navigation.index') }}" class="menu-link"><div>Navigation</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.media.index') }}" class="menu-link"><div>Media</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.homepage-sections.index') }}" class="menu-link"><div>Homepage Sections</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.pages.index') }}" class="menu-link"><div>Pages / SEO</div></a></li>
                    <li class="menu-item"><a href="{{ route('intro.business-registrations.index') }}" class="menu-link"><div>Business Registrations</div></a></li>
                </ul>
            </li>
        @else
            <!-- My Subscription -->
            @canAccess('my-subscription.manage')
                <li class="menu-item">
                    <a href="{{ route('my-subscription.index') }}" class="menu-link">
                        <i class="menu-icon tf-icons fa fa-credit-card"></i>
                        <div data-i18n="My Subscription">{{ __('sidebar.my_subscription') }}</div>
                    </a>
                </li>
            @endcanAccess
        @endif
        <!-- Users -->
        @canAccessAny(['permission.view', 'role.view', 'user.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-users"></i>
                    <div data-i18n="Users Management">{{ __('sidebar.users_management') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('permission.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/permissions') }}" class="menu-link">
                                <div data-i18n="Permissions">{{ __('sidebar.permissions') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('role.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/roles') }}" class="menu-link">
                                <div data-i18n="Roles">{{ __('sidebar.roles') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('user.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/users') }}" class="menu-link">
                                <div data-i18n="Admin Users">{{ __('sidebar.admin_users') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny

        <!-- Inventory -->
        @if (businessModuleEnabled('inventory'))
        @canAccessAny(['unit.view', 'warehouse.view', 'brand.view', 'category.view', 'sub-category.view', 'product.view',
            'unit-conversion.view', 'batch.view', 'serial-number.view', 'stock.view', 'stock-transaction.view', 'opening-stock.view',
            'stock-taking.view', 'loss-reason.view', 'waste-damage-expiry.view', 'transfer-note.view', 'recipe.view', 'manufacturing-plan.view', 'production.view',
            'reports.stock-ledger.view', 'reports.stock-summary.view',
            'reports.stock-valuation.view', 'reports.stock-aging.view', 'reports.stock-transfer-report.view',
            'reports.stock-reconciliation.view', 'reports.batch-expiry.view', 'reports.stock-loss.view',
            'reports.waste-damage-expiry.view',
            'reports.serial-number-register.view', 'reports.serial-number-available.view', 'reports.serial-number-sold.view',
            'reports.serial-number-movement.view', 'reports.serial-number-customer.view',
            'reports.material-consumption-report.view', 'reports.manufacturing-plan-report.view',
            'reports.production-report.view', 'reports.recipe-bom-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-box"></i>
                    <div data-i18n="Inventory">{{ __('sidebar.inventory') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('unit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/unit') }}" class="menu-link">
                                <div data-i18n="Unit">{{ __('sidebar.units') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('warehouse.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/warehouse') }}" class="menu-link">
                                <div data-i18n="Warehouse">{{ __('sidebar.warehouse') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('brand.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/brands') }}" class="menu-link">
                                <div data-i18n="Brands">{{ __('sidebar.brands') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/category') }}" class="menu-link">
                                <div data-i18n="Categories">{{ __('sidebar.categories') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('sub-category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/sub-category') }}" class="menu-link">
                                <div data-i18n="Sub Categories">{{ __('sidebar.sub_categories') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('product.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product') }}" class="menu-link">
                                <div data-i18n="Products">{{ __('sidebar.products') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('unit-conversion.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-unit-conversion') }}" class="menu-link">
                                <div data-i18n="Unit Conversion">{{ __('sidebar.unit_conversion') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('batch.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-batch') }}" class="menu-link">
                                <div data-i18n="Batches">{{ __('sidebar.batches') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('serial-number.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/serial-number') }}" class="menu-link">
                                <div data-i18n="Serial Numbers">{{ __('sidebar.serial_numbers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-stock') }}" class="menu-link">
                                <div data-i18n="Stock">{{ __('sidebar.stock') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock-transaction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-stock-transaction') }}" class="menu-link">
                                <div data-i18n="Transactions">{{ __('sidebar.transactions') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('opening-stock.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/opening-stock') }}" class="menu-link">
                                <div data-i18n="Opening Stock">{{ __('sidebar.opening_stock') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock-taking.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/stock-taking') }}" class="menu-link">
                                <div data-i18n="Stock Taking">{{ __('sidebar.stock_taking') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('waste-damage-expiry.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/waste-damage-expiry') }}" class="menu-link">
                                <div data-i18n="Waste / Damage / Expiry">{{ __('sidebar.waste_damage_expiry') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('loss-reason.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/loss-reason') }}" class="menu-link">
                                <div data-i18n="Loss Reasons">{{ __('sidebar.loss_reasons') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('transfer-note.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/transfer-note') }}" class="menu-link">
                                <div data-i18n="Transfer Note">{{ __('sidebar.transfer_note') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @if (businessModuleEnabled('manufacturing'))
                    @canAccessAny(['recipe.view', 'manufacturing-plan.view', 'production.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Manufacturing">{{ __('sidebar.manufacturing') }}</div>
                            </a>

                            <ul class="menu-sub">
                                @canAccess('recipe.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/recipe') }}" class="menu-link">
                                            <div data-i18n="Recipes / BOM">{{ __('sidebar.recipes_bom') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('manufacturing-plan.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/manufacturing-plan') }}" class="menu-link">
                                            <div data-i18n="Manufacturing Plans">{{ __('sidebar.manufacturing_plans') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('production.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/production') }}" class="menu-link">
                                            <div data-i18n="Productions">{{ __('sidebar.productions') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                    @endif
                    @canAccessAny(['reports.stock-ledger.view', 'reports.stock-summary.view', 'reports.stock-valuation.view',
                        'reports.stock-aging.view', 'reports.stock-transfer-report.view', 'reports.stock-reconciliation.view',
                        'reports.batch-expiry.view', 'reports.stock-loss.view', 'reports.waste-damage-expiry.view', 'reports.material-consumption-report.view',
                        'reports.serial-number-register.view', 'reports.serial-number-available.view', 'reports.serial-number-sold.view',
                        'reports.serial-number-movement.view', 'reports.serial-number-customer.view',
                        'reports.manufacturing-plan-report.view', 'reports.production-report.view', 'reports.recipe-bom-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Inventory Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                {{-- Stock Reports --}}
                                @canAccessAny(['reports.stock-ledger.view', 'reports.stock-summary.view', 'reports.stock-valuation.view',
                                    'reports.stock-aging.view', 'reports.stock-transfer-report.view', 'reports.stock-reconciliation.view',
                                    'reports.batch-expiry.view', 'reports.stock-loss.view', 'reports.waste-damage-expiry.view'])
                                    <li class="menu-item">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <div data-i18n="Stock Reports">{{ __('sidebar.stock_reports') }}</div>
                                        </a>
                                        <ul class="menu-sub">
                                            @canAccess('reports.stock-summary.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-summary') }}" class="menu-link"><div>Stock Summary</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-ledger.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-ledger') }}" class="menu-link"><div>Stock Ledger</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-valuation.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-valuation') }}" class="menu-link"><div>Stock Valuation</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-aging.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-aging') }}" class="menu-link"><div>Stock Aging</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-transfer-report.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-transfer-report') }}" class="menu-link"><div>Stock Transfer</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-reconciliation.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-reconciliation') }}" class="menu-link"><div>Reconciliation</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.stock-loss.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/stock-loss') }}" class="menu-link"><div>Stock Loss</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.batch-expiry.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/batch-expiry') }}" class="menu-link"><div>Batch &amp; Expiry</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.waste-damage-expiry.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/waste-damage-expiry') }}" class="menu-link"><div>Waste / Damage / Expiry</div></a></li>
                                            @endcanAccess
                                        </ul>
                                    </li>
                                @endcanAccessAny

                                {{-- Serial Number Reports --}}
                                @canAccessAny(['reports.serial-number-register.view', 'reports.serial-number-available.view',
                                    'reports.serial-number-sold.view', 'reports.serial-number-movement.view', 'reports.serial-number-customer.view'])
                                    <li class="menu-item">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <div data-i18n="Serial Number Reports">{{ __('sidebar.serial_number_reports') }}</div>
                                        </a>
                                        <ul class="menu-sub">
                                            @canAccess('reports.serial-number-register.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/serial-number-register') }}" class="menu-link"><div>Serial Number Register</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.serial-number-available.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/serial-number-available') }}" class="menu-link"><div>Available Serial Numbers</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.serial-number-sold.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/serial-number-sold') }}" class="menu-link"><div>Sold Serial Numbers</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.serial-number-movement.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/serial-number-movement') }}" class="menu-link"><div>Movement History</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.serial-number-customer.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/serial-number-customer') }}" class="menu-link"><div>Customer-wise Serial Numbers</div></a></li>
                                            @endcanAccess
                                        </ul>
                                    </li>
                                @endcanAccessAny

                                {{-- Consumption Reports (manufacturing-gated screens, nested under Inventory) --}}
                                @if (businessModuleEnabled('manufacturing'))
                                @canAccess('reports.material-consumption-report.view')
                                    <li class="menu-item">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <div data-i18n="Consumption Reports">{{ __('sidebar.consumption_reports') }}</div>
                                        </a>
                                        <ul class="menu-sub">
                                            <li class="menu-item"><a href="{{ url('/admin/reports/material-consumption') }}" class="menu-link"><div>Material Consumption</div></a></li>
                                            <li class="menu-item"><a href="{{ url('/admin/reports/material-consumption?report_mode=variance') }}" class="menu-link"><div>Consumption Variance</div></a></li>
                                        </ul>
                                    </li>
                                @endcanAccess
                                @endif

                                {{-- Manufacturing Reports --}}
                                @if (businessModuleEnabled('manufacturing'))
                                @canAccessAny(['reports.manufacturing-plan-report.view', 'reports.production-report.view'])
                                    <li class="menu-item">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <div data-i18n="Manufacturing Reports">{{ __('sidebar.manufacturing_reports') }}</div>
                                        </a>
                                        <ul class="menu-sub">
                                            @canAccess('reports.manufacturing-plan-report.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/manufacturing-plan') }}" class="menu-link"><div>Manufacturing Plan</div></a></li>
                                            @endcanAccess
                                            @canAccess('reports.production-report.view')
                                                <li class="menu-item"><a href="{{ url('/admin/reports/production') }}" class="menu-link"><div>Production Report</div></a></li>
                                            @endcanAccess
                                        </ul>
                                    </li>
                                @endcanAccessAny
                                @endif

                                {{-- Recipe/BOM Reports --}}
                                @if (businessModuleEnabled('manufacturing'))
                                @canAccess('reports.recipe-bom-report.view')
                                    <li class="menu-item">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <div data-i18n="Recipe BOM Reports">{{ __('sidebar.recipe_bom_reports') }}</div>
                                        </a>
                                        <ul class="menu-sub">
                                            <li class="menu-item"><a href="{{ url('/admin/reports/recipe-bom-report') }}" class="menu-link"><div>Recipe / BOM</div></a></li>
                                            <li class="menu-item"><a href="{{ url('/admin/reports/recipe-bom-report?report_mode=material_requirement') }}" class="menu-link"><div>Material Requirement</div></a></li>
                                        </ul>
                                    </li>
                                @endcanAccess
                                @endif
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny
        @endif

        @if (businessModuleEnabled('accounting'))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Accounting</span>
        </li>
        {{-- Accounting --}}
        @canAccessAny(['account-type.view', 'account-sub-type.view', 'journal.view', 'account.view', 'journal-entry.view',
            'bank-reconciliation.view',
            'recurring-transaction.view', 'fixed-asset.view', 'fixed-asset-category.view', 'fixed-asset-depreciation.view',
            'fiscal-year.view', 'accounting-period.view', 'period-closing-rule.manage', 'budget.view',
            'reports.accounts-payable.view', 'reports.general-ledger.view', 'reports.trial-balance.view',
            'reports.journal-register.view', 'reports.account-ledger.view', 'reports.account-balance.view',
            'reports.day-book.view', 'reports.profit-loss.view', 'reports.balance-sheet.view', 'reports.cash-flow.view',
            'reports.cash-bank-ledger.view', 'reports.income-report.view', 'reports.sales-report.view', 'reports.voucher-usage.view', 'reports.expense-report.view',
            'reports.tax-report.view', 'reports.equity-report.view', 'reports.budget-vs-actual.view',
            'reports.fixed-asset-register.view', 'reports.depreciation-report.view', 'reports.asset-valuation-report.view', 'reports.asset-disposal-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-box"></i>
                    <div data-i18n="Accounting">{{ __('sidebar.accounting') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('account-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account-type') }}" class="menu-link">
                                <div data-i18n="Account Types">{{ __('sidebar.account_types') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('account-sub-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account-sub-type') }}" class="menu-link">
                                <div data-i18n="Account Sub Types">{{ __('sidebar.account_sub_types') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('journal.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/journal') }}" class="menu-link">
                                <div data-i18n="Journals">{{ __('sidebar.journals') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('account.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account') }}" class="menu-link">
                                <div data-i18n="Accounts">{{ __('sidebar.accounts') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('journal-entry.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/journal-entry') }}" class="menu-link">
                                <div data-i18n="Journal Entries">{{ __('sidebar.journal_entries') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('bank-reconciliation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/bank-reconciliation') }}" class="menu-link">
                                <div data-i18n="Bank Reconciliation">{{ __('sidebar.bank_reconciliation') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('recurring-transaction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/recurring-transaction') }}" class="menu-link">
                                <div data-i18n="Recurring Transactions">{{ __('sidebar.recurring_transactions') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset-category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset-category') }}" class="menu-link">
                                <div data-i18n="Fixed Asset Categories">{{ __('sidebar.fixed_asset_categories') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset') }}" class="menu-link">
                                <div data-i18n="Fixed Assets">{{ __('sidebar.fixed_assets') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset-depreciation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset-depreciation') }}" class="menu-link">
                                <div data-i18n="Depreciation">{{ __('sidebar.depreciation') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    {{-- Advanced Accounting Mode - hidden by default, see
                         CommonFunctions::businessAccountingAdvancedModeEnabled() --}}
                    @if (businessAccountingAdvancedModeEnabled())
                        @canAccess('fiscal-year.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/fiscal-year') }}" class="menu-link">
                                    <div data-i18n="Fiscal Years">{{ __('sidebar.fiscal_years') }}</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('accounting-period.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/accounting-period') }}" class="menu-link">
                                    <div data-i18n="Accounting Periods">{{ __('sidebar.accounting_periods') }}</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('period-closing-rule.manage')
                            <li class="menu-item">
                                <a href="{{ url('/admin/period-closing-rule') }}" class="menu-link">
                                    <div data-i18n="Closing Rules">{{ __('sidebar.closing_rules') }}</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('budget.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/budget') }}" class="menu-link">
                                    <div data-i18n="Budgets">{{ __('sidebar.budgets') }}</div>
                                </a>
                            </li>
                        @endcanAccess
                    @endif
                    @canAccessAny(['reports.accounts-payable.view', 'reports.general-ledger.view', 'reports.trial-balance.view',
                        'reports.journal-register.view', 'reports.account-ledger.view', 'reports.account-balance.view',
                        'reports.day-book.view', 'reports.profit-loss.view', 'reports.balance-sheet.view', 'reports.cash-flow.view',
                        'reports.cash-bank-ledger.view', 'reports.income-report.view', 'reports.sales-report.view', 'reports.voucher-usage.view', 'reports.expense-report.view',
                        'reports.tax-report.view', 'reports.equity-report.view', 'reports.budget-vs-actual.view',
                        'reports.fixed-asset-register.view', 'reports.depreciation-report.view', 'reports.asset-valuation-report.view', 'reports.asset-disposal-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Accounting Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.accounts-payable.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/accounts-payable') }}" class="menu-link">
                                            <div data-i18n="Accounts Payable">{{ __('sidebar.accounts_payable') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.general-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/general-ledger') }}" class="menu-link">
                                            <div data-i18n="General Ledger">{{ __('sidebar.general_ledger') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.trial-balance.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/trial-balance') }}" class="menu-link">
                                            <div data-i18n="Trial Balance">{{ __('sidebar.trial_balance') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.journal-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/journal-register') }}" class="menu-link">
                                            <div data-i18n="Journal Register">{{ __('sidebar.journal_register') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.account-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/account-ledger') }}" class="menu-link">
                                            <div data-i18n="Account Ledger">{{ __('sidebar.account_ledger') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.account-balance.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/account-balance') }}" class="menu-link">
                                            <div data-i18n="Account Balance">{{ __('sidebar.account_balance') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.day-book.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/day-book') }}" class="menu-link">
                                            <div data-i18n="Day Book">{{ __('sidebar.day_book') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.profit-loss.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/profit-loss') }}" class="menu-link">
                                            <div data-i18n="Profit & Loss">{{ __('sidebar.profit_loss') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.balance-sheet.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/balance-sheet') }}" class="menu-link">
                                            <div data-i18n="Balance Sheet">{{ __('sidebar.balance_sheet') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cash-flow.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cash-flow') }}" class="menu-link">
                                            <div data-i18n="Cash Flow Statement">{{ __('sidebar.cash_flow_statement') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.fixed-asset-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/fixed-asset-register') }}" class="menu-link">
                                            <div data-i18n="Fixed Asset Register">{{ __('sidebar.fixed_asset_register') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.depreciation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/depreciation-report') }}" class="menu-link">
                                            <div data-i18n="Depreciation Report">{{ __('sidebar.depreciation_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-valuation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-valuation-report') }}" class="menu-link">
                                            <div data-i18n="Asset Valuation Report">{{ __('sidebar.asset_valuation_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-disposal-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-disposal-report') }}" class="menu-link">
                                            <div data-i18n="Asset Disposal Report">{{ __('sidebar.asset_disposal_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cash-bank-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cash-bank-ledger') }}" class="menu-link">
                                            <div data-i18n="Cash & Bank Ledger">{{ __('sidebar.cash_bank_ledger') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.income-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/income-report') }}" class="menu-link">
                                            <div data-i18n="Income Report">{{ __('sidebar.income_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.sales-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/sales-report') }}" class="menu-link">
                                            <div data-i18n="Sales Report">{{ __('sidebar.sales_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.voucher-usage.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/voucher-usage-report') }}" class="menu-link">
                                            <div data-i18n="Voucher Usage Report">{{ __('sidebar.voucher_usage_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.expense-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/expense-report') }}" class="menu-link">
                                            <div data-i18n="Expense Report">{{ __('sidebar.expense_report_by_account') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.tax-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/tax-report') }}" class="menu-link">
                                            <div data-i18n="Tax Reports">{{ __('sidebar.tax_reports') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.equity-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/equity-report') }}" class="menu-link">
                                            <div data-i18n="Equity Report">{{ __('sidebar.equity_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                {{-- Visible in both Simple and Advanced Accounting Mode -
                                     read-only, speaks "budget/actual/variance" not debit/credit. --}}
                                @canAccess('reports.budget-vs-actual.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/budget-vs-actual') }}" class="menu-link">
                                            <div data-i18n="Budget vs Actual">{{ __('sidebar.budget_vs_actual') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny
        @endif

        {{-- Expense --}}
        @if (businessModuleEnabled('accounting'))
        @canAccessAny(['expense-category.manage', 'expense.view', 'admin-expense.manage', 'reports.expense-detail-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-receipt"></i>
                    <div data-i18n="Expense">{{ __('sidebar.expense') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('expense-category.manage')
                        <li class="menu-item">
                            <a href="{{ url('/admin/expense-category') }}" class="menu-link">
                                <div data-i18n="Expense Category">{{ __('sidebar.expense_category') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('expense.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/expense') }}" class="menu-link">
                                <div data-i18n="Expense Detail (POS)">{{ __('sidebar.expense_detail_pos') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('admin-expense.manage')
                        <li class="menu-item">
                            <a href="{{ url('/admin/admin-expense') }}" class="menu-link">
                                <div data-i18n="Admin Expenses">{{ __('sidebar.admin_expenses') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('reports.expense-detail-report.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/reports/expense-detail-report') }}" class="menu-link">
                                <div data-i18n="Expense Reports">{{ __('sidebar.expense_reports') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny
        @endif

        @if (businessModuleEnabled('hrm') || businessModuleEnabled('payroll'))
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">HRM</span>
        </li>
        {{-- HR & Payroll --}}
        @canAccessAny(['department.view', 'designation.view', 'shift.view', 'employee.view', 'attendance.view',
            'leave-type.view', 'leave-request.view', 'salary-component.view', 'salary-structure.view',
            'payroll.view', 'employee-advance.view', 'employee-deduction.view', 'employee-ledger.view',
            'employee-exit.view', 'asset.view', 'asset-allocation.view',
            'reports.employee-master-report.view', 'reports.employee-directory-report.view',
            'reports.employee-joining-report.view', 'reports.employee-exit-report.view',
            'reports.department-wise-employee-report.view', 'reports.designation-wise-employee-report.view',
            'reports.branch-wise-employee-report.view', 'reports.employee-status-report.view',
            'reports.attendance-summary-report.view', 'reports.daily-attendance-report.view',
            'reports.monthly-attendance-report.view', 'reports.attendance-register.view',
            'reports.late-attendance-report.view', 'reports.early-checkout-report.view',
            'reports.absent-employees-report.view', 'reports.missing-checkin-checkout-report.view',
            'reports.overtime-report.view', 'reports.shift-wise-attendance-report.view',
            'reports.shift-assignment-report.view', 'reports.leave-summary-report.view',
            'reports.employee-leave-history-report.view', 'reports.leave-type-wise-report.view',
            'reports.department-wise-leave-report.view', 'reports.pending-leave-approval-report.view',
            'reports.leave-approval-status-report.view', 'reports.leave-balance-report.view',
            'reports.salary-structure-report.view', 'reports.salary-component-report.view',
            'reports.deduction-report.view', 'reports.employee-advance-report.view',
            'reports.advance-recovery-report.view', 'reports.employee-ledger-report.view',
            'reports.payroll-summary-report.view', 'reports.employee-wise-payroll-report.view',
            'reports.department-wise-payroll-report.view', 'reports.branch-wise-payroll-report.view',
            'reports.monthly-payroll-register.view', 'reports.payroll-cost-report.view',
            'reports.pending-payroll-report.view', 'reports.salary-slip-report.view',
            'reports.payroll-disbursement-report.view', 'reports.resignation-report.view',
            'reports.termination-report.view', 'reports.employee-clearance-report.view',
            'reports.asset-allocation-report.view', 'reports.employee-asset-return-report.view',
            'reports.employee-document-report.view', 'reports.employee-lifecycle-report.view',
            'reports.attendance-payroll-comparison-report.view', 'reports.leave-payroll-impact-report.view',
            'reports.employee-cost-report.view', 'reports.department-payroll-cost-report.view',
            'reports.branch-payroll-cost-report.view', 'reports.hr-dashboard-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-users-cog"></i>
                    <div data-i18n="HR & Payroll">{{ __('sidebar.hr_payroll') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('reports.hr-dashboard-report.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/reports/hr-dashboard-report') }}" class="menu-link">
                                <div data-i18n="HR Dashboard">{{ __('sidebar.hr_dashboard') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee') }}" class="menu-link">
                                <div data-i18n="Employees">{{ __('sidebar.employees') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('department.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/department') }}" class="menu-link">
                                <div data-i18n="Departments">{{ __('sidebar.departments') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('designation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/designation') }}" class="menu-link">
                                <div data-i18n="Designations">{{ __('sidebar.designations') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('shift.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/shift') }}" class="menu-link">
                                <div data-i18n="Shifts">{{ __('sidebar.shifts') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('attendance.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/attendance') }}" class="menu-link">
                                <div data-i18n="Attendance">{{ __('sidebar.attendance') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('leave-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/leave-type') }}" class="menu-link">
                                <div data-i18n="Leave Types">{{ __('sidebar.leave_types') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('leave-request.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/leave-request') }}" class="menu-link">
                                <div data-i18n="Leave Requests">{{ __('sidebar.leave_requests') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('salary-component.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/salary-component') }}" class="menu-link">
                                <div data-i18n="Salary Components">{{ __('sidebar.salary_components') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('salary-structure.view')
                        <li class="menu-item">
                            <a href="{{ route('salary-structure.index') }}" class="menu-link">
                                <div data-i18n="Salary Structures">{{ __('sidebar.salary_structures') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payroll.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payroll') }}" class="menu-link">
                                <div data-i18n="Payroll">{{ __('sidebar.payroll') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-advance.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-advance') }}" class="menu-link">
                                <div data-i18n="Employee Advances">{{ __('sidebar.employee_advances') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-deduction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-deduction') }}" class="menu-link">
                                <div data-i18n="Employee Deductions">{{ __('sidebar.employee_deductions') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-ledger.view')
                        <li class="menu-item">
                            <a href="{{ route('employee-ledger.index') }}" class="menu-link">
                                <div data-i18n="Employee Ledger">{{ __('sidebar.employee_ledger') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-exit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-exit') }}" class="menu-link">
                                <div data-i18n="Resignation / Termination">{{ __('sidebar.resignation_termination') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('asset.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/asset') }}" class="menu-link">
                                <div data-i18n="Assets">{{ __('sidebar.assets') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('asset-allocation.view')
                        <li class="menu-item">
                            <a href="{{ route('asset-allocation.index') }}" class="menu-link">
                                <div data-i18n="Asset Allocation">{{ __('sidebar.asset_allocation') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.employee-master-report.view', 'reports.employee-directory-report.view',
                        'reports.employee-joining-report.view', 'reports.employee-exit-report.view',
                        'reports.department-wise-employee-report.view', 'reports.designation-wise-employee-report.view',
                        'reports.branch-wise-employee-report.view', 'reports.employee-status-report.view',
                        'reports.attendance-summary-report.view', 'reports.daily-attendance-report.view',
                        'reports.monthly-attendance-report.view', 'reports.attendance-register.view',
                        'reports.late-attendance-report.view', 'reports.early-checkout-report.view',
                        'reports.absent-employees-report.view', 'reports.missing-checkin-checkout-report.view',
                        'reports.overtime-report.view', 'reports.shift-wise-attendance-report.view',
                        'reports.shift-assignment-report.view', 'reports.leave-summary-report.view',
                        'reports.employee-leave-history-report.view', 'reports.leave-type-wise-report.view',
                        'reports.department-wise-leave-report.view', 'reports.pending-leave-approval-report.view',
                        'reports.leave-approval-status-report.view', 'reports.leave-balance-report.view',
                        'reports.salary-structure-report.view', 'reports.salary-component-report.view',
                        'reports.deduction-report.view', 'reports.employee-advance-report.view',
                        'reports.advance-recovery-report.view', 'reports.employee-ledger-report.view',
                        'reports.payroll-summary-report.view', 'reports.employee-wise-payroll-report.view',
                        'reports.department-wise-payroll-report.view', 'reports.branch-wise-payroll-report.view',
                        'reports.monthly-payroll-register.view', 'reports.payroll-cost-report.view',
                        'reports.pending-payroll-report.view', 'reports.salary-slip-report.view',
                        'reports.payroll-disbursement-report.view', 'reports.resignation-report.view',
                        'reports.termination-report.view', 'reports.employee-clearance-report.view',
                        'reports.asset-allocation-report.view', 'reports.employee-asset-return-report.view',
                        'reports.employee-document-report.view', 'reports.employee-lifecycle-report.view',
                        'reports.attendance-payroll-comparison-report.view', 'reports.leave-payroll-impact-report.view',
                        'reports.employee-cost-report.view', 'reports.department-payroll-cost-report.view',
                        'reports.branch-payroll-cost-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="HR Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.employee-master-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-master-report') }}" class="menu-link">
                                            <div data-i18n="Employee Master Report">{{ __('sidebar.employee_master_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-directory-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-directory-report') }}" class="menu-link">
                                            <div data-i18n="Employee Directory Report">{{ __('sidebar.employee_directory_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-joining-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-joining-report') }}" class="menu-link">
                                            <div data-i18n="Employee Joining Report">{{ __('sidebar.employee_joining_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-exit-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-exit-report') }}" class="menu-link">
                                            <div data-i18n="Employee Exit Report">{{ __('sidebar.employee_exit_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Employee Report">{{ __('sidebar.department_wise_employee_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.designation-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/designation-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Designation-wise Employee Report">{{ __('sidebar.designation_wise_employee_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Branch-wise Employee Report">{{ __('sidebar.branch_wise_employee_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-status-report') }}" class="menu-link">
                                            <div data-i18n="Employee Status Report">{{ __('sidebar.employee_status_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-summary-report') }}" class="menu-link">
                                            <div data-i18n="Attendance Summary Report">{{ __('sidebar.attendance_summary_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.daily-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/daily-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Daily Attendance Report">{{ __('sidebar.daily_attendance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.monthly-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/monthly-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Monthly Attendance Report">{{ __('sidebar.monthly_attendance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-register') }}" class="menu-link">
                                            <div data-i18n="Attendance Register">{{ __('sidebar.attendance_register') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.late-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/late-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Late Attendance Report">{{ __('sidebar.late_attendance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.early-checkout-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/early-checkout-report') }}" class="menu-link">
                                            <div data-i18n="Early Checkout Report">{{ __('sidebar.early_checkout_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.absent-employees-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/absent-employees-report') }}" class="menu-link">
                                            <div data-i18n="Absent Employees Report">{{ __('sidebar.absent_employees_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.missing-checkin-checkout-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/missing-checkin-checkout-report') }}" class="menu-link">
                                            <div data-i18n="Missing Check-In/Check-Out Report">{{ __('sidebar.missing_check_in_check_out_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.overtime-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/overtime-report') }}" class="menu-link">
                                            <div data-i18n="Overtime Report">{{ __('sidebar.overtime_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.shift-wise-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/shift-wise-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Shift-wise Attendance Report">{{ __('sidebar.shift_wise_attendance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.shift-assignment-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/shift-assignment-report') }}" class="menu-link">
                                            <div data-i18n="Shift Assignment Report">{{ __('sidebar.shift_assignment_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-summary-report') }}" class="menu-link">
                                            <div data-i18n="Leave Summary Report">{{ __('sidebar.leave_summary_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-leave-history-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-leave-history-report') }}" class="menu-link">
                                            <div data-i18n="Employee Leave History Report">{{ __('sidebar.employee_leave_history_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-type-wise-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-type-wise-report') }}" class="menu-link">
                                            <div data-i18n="Leave Type-wise Report">{{ __('sidebar.leave_type_wise_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-leave-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-leave-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Leave Report">{{ __('sidebar.department_wise_leave_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.pending-leave-approval-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/pending-leave-approval-report') }}" class="menu-link">
                                            <div data-i18n="Pending Leave Approval Report">{{ __('sidebar.pending_leave_approval_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-approval-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-approval-status-report') }}" class="menu-link">
                                            <div data-i18n="Leave Approval Status Report">{{ __('sidebar.leave_approval_status_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-balance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-balance-report') }}" class="menu-link">
                                            <div data-i18n="Leave Balance Report">{{ __('sidebar.leave_balance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-structure-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-structure-report') }}" class="menu-link">
                                            <div data-i18n="Salary Structure Report">{{ __('sidebar.salary_structure_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-component-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-component-report') }}" class="menu-link">
                                            <div data-i18n="Salary Component Report">{{ __('sidebar.salary_component_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.deduction-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/deduction-report') }}" class="menu-link">
                                            <div data-i18n="Deduction Report">{{ __('sidebar.deduction_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-advance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-advance-report') }}" class="menu-link">
                                            <div data-i18n="Employee Advance Report">{{ __('sidebar.employee_advance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.advance-recovery-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/advance-recovery-report') }}" class="menu-link">
                                            <div data-i18n="Advance Recovery Report">{{ __('sidebar.advance_recovery_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-ledger-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-ledger-report') }}" class="menu-link">
                                            <div data-i18n="Employee Ledger Report">{{ __('sidebar.employee_ledger_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-summary-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Summary Report">{{ __('sidebar.payroll_summary_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Employee-wise Payroll Report">{{ __('sidebar.employee_wise_payroll_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Payroll Report">{{ __('sidebar.department_wise_payroll_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Branch-wise Payroll Report">{{ __('sidebar.branch_wise_payroll_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.monthly-payroll-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/monthly-payroll-register') }}" class="menu-link">
                                            <div data-i18n="Monthly Payroll Register">{{ __('sidebar.monthly_payroll_register') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Cost Report">{{ __('sidebar.payroll_cost_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.pending-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/pending-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Pending Payroll Report">{{ __('sidebar.pending_payroll_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-slip-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-slip-report') }}" class="menu-link">
                                            <div data-i18n="Salary Slip Report">{{ __('sidebar.salary_slip_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-disbursement-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-disbursement-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Payment/Disbursement Report">{{ __('sidebar.payroll_payment_disbursement_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.resignation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/resignation-report') }}" class="menu-link">
                                            <div data-i18n="Resignation Report">{{ __('sidebar.resignation_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.termination-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/termination-report') }}" class="menu-link">
                                            <div data-i18n="Termination Report">{{ __('sidebar.termination_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-clearance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-clearance-report') }}" class="menu-link">
                                            <div data-i18n="Employee Clearance Report">{{ __('sidebar.employee_clearance_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-allocation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-allocation-report') }}" class="menu-link">
                                            <div data-i18n="Asset Allocation Report">{{ __('sidebar.asset_allocation_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-asset-return-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-asset-return-report') }}" class="menu-link">
                                            <div data-i18n="Employee Asset Return Report">{{ __('sidebar.employee_asset_return_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-document-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-document-report') }}" class="menu-link">
                                            <div data-i18n="Employee Document Report">{{ __('sidebar.employee_document_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-lifecycle-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-lifecycle-report') }}" class="menu-link">
                                            <div data-i18n="Employee Lifecycle Report">{{ __('sidebar.employee_lifecycle_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-payroll-comparison-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-payroll-comparison-report') }}" class="menu-link">
                                            <div data-i18n="Attendance & Payroll Comparison Report">{{ __('sidebar.attendance_payroll_comparison_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-payroll-impact-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-payroll-impact-report') }}" class="menu-link">
                                            <div data-i18n="Leave & Payroll Impact Report">{{ __('sidebar.leave_payroll_impact_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-cost-report') }}" class="menu-link">
                                            <div data-i18n="Employee Cost Report">{{ __('sidebar.employee_cost_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Department Payroll Cost Report">{{ __('sidebar.department_payroll_cost_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Branch Payroll Cost Report">{{ __('sidebar.branch_payroll_cost_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny
        @endif

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">System</span>
        </li>
        {{-- Documentation --}}
        @canAccess('documentation.view')
            <li class="menu-item">
                <a href="{{ route('documentation.index') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-book"></i>
                    <div data-i18n="Documentation">{{ __('sidebar.documentation') }}</div>
                </a>
            </li>
        @endcanAccess

        {{-- Notifications --}}
        @canAccess('notification.view')
            <li class="menu-item">
                <a href="{{ url('/admin/notifications') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-bell"></i>
                    <div data-i18n="Notifications">{{ __('sidebar.notifications') }}</div>
                </a>
            </li>
        @endcanAccess

        {{-- Push Notifications (FCM) --}}
        @canAccessAny(['notification-template.view', 'broadcast-notification.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-mobile-alt"></i>
                    <div data-i18n="Push Notifications">{{ __('sidebar.push_notifications') }}</div>
                </a>
                <ul class="menu-sub">
                    @canAccess('notification-template.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/notification-template') }}" class="menu-link">
                                <div data-i18n="Notification Templates">{{ __('sidebar.notification_templates') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('broadcast-notification.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/broadcast-notification') }}" class="menu-link">
                                <div data-i18n="Broadcast Notifications">{{ __('sidebar.broadcast_notifications') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny

        {{-- Audit & Security --}}
        @canAccessAny(['activity-log.view', 'login-history.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-shield-alt"></i>
                    <div data-i18n="Audit & Security">{{ __('sidebar.audit_security') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('activity-log.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/activity-log') }}" class="menu-link">
                                <div data-i18n="Activity Log">{{ __('sidebar.activity_log') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('login-history.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/login-history') }}" class="menu-link">
                                <div data-i18n="Login History">{{ __('sidebar.login_history') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny

        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Procurement & Sales</span>
        </li>
        {{-- Procurement --}}
        @if (businessModuleEnabled('inventory'))
        @canAccessAny(['supplier.view', 'purchase-request.view', 'purchase-request-quotation.view', 'purchase.view',
            'good-receipt-note.view', 'purchase-return.view', 'supplier-payment.view',
            'reports.supplier-ledger.view', 'reports.supplier-aging.view', 'reports.supplier-payment-history.view',
            'reports.purchase-return-summary.view', 'reports.purchase-return-detail.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-shopping-cart"></i>
                    <div data-i18n="Procurement">{{ __('sidebar.procurement') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('supplier.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/supplier') }}" class="menu-link">
                                <div data-i18n="Suppliers">{{ __('sidebar.suppliers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-request.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-request') }}" class="menu-link">
                                <div data-i18n="Purchase Requests">{{ __('sidebar.purchase_requests') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-request-quotation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-request-quotation') }}" class="menu-link">
                                <div data-i18n="Purchase Request Quotations">{{ __('sidebar.quotations') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase') }}" class="menu-link">
                                <div data-i18n="Purchases">{{ __('sidebar.purchases') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('good-receipt-note.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/good-receipt-note') }}" class="menu-link">
                                <div data-i18n="Goods Receipt Notes">{{ __('sidebar.goods_receipt_notes') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-return') }}" class="menu-link">
                                <div data-i18n="Purchase Returns">{{ __('sidebar.purchase_returns') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('supplier-payment.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/supplier-payment') }}" class="menu-link">
                                <div data-i18n="Supplier Payments">{{ __('sidebar.supplier_payments') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.supplier-ledger.view', 'reports.supplier-aging.view',
                        'reports.supplier-payment-history.view', 'reports.purchase-return-summary.view',
                        'reports.purchase-return-detail.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Purchase Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.supplier-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-ledger') }}" class="menu-link">
                                            <div data-i18n="Supplier Ledger">{{ __('sidebar.supplier_ledger') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.supplier-aging.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-aging') }}" class="menu-link">
                                            <div data-i18n="Supplier Aging">{{ __('sidebar.supplier_aging') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.supplier-payment-history.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-payment-history') }}" class="menu-link">
                                            <div data-i18n="Supplier Payment History">{{ __('sidebar.supplier_payment_history') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.purchase-return-summary.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/purchase-return-summary') }}" class="menu-link">
                                            <div data-i18n="Purchase Return Summary">{{ __('sidebar.purchase_return_summary') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.purchase-return-detail.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/purchase-return-detail') }}" class="menu-link">
                                            <div data-i18n="Purchase Return Detail">{{ __('sidebar.purchase_return_detail') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny

                </ul>
            </li>
        @endcanAccessAny
        @endif

        {{-- Service Management (non-stock purchase/sale: gas cylinders, rentals,
             installation/delivery charges, etc) --}}
        @if (businessModuleEnabled('service-management'))
        @canAccessAny(['service-purchase.view', 'service-purchase-return.view', 'service-sale.view', 'service-sale-return.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-concierge-bell"></i>
                    <div data-i18n="Service Management">{{ __('sidebar.service_management') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('service-purchase.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-purchase') }}" class="menu-link">
                                <div data-i18n="Service Purchases">{{ __('sidebar.service_purchases') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-purchase-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-purchase-return') }}" class="menu-link">
                                <div data-i18n="Service Purchase Returns">{{ __('sidebar.service_purchase_returns') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-sale.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-sale') }}" class="menu-link">
                                <div data-i18n="Service Sales">{{ __('sidebar.service_sales') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-sale-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-sale-return') }}" class="menu-link">
                                <div data-i18n="Service Sale Returns">{{ __('sidebar.service_sale_returns') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.service-sale-report.view', 'reports.service-purchase-report.view',
                        'reports.service-transaction-summary.view', 'reports.service-payment-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Service Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.service-sale-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-sale-report') }}" class="menu-link">
                                            <div data-i18n="Sale Service Report">{{ __('sidebar.sale_service_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-purchase-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-purchase-report') }}" class="menu-link">
                                            <div data-i18n="Purchase Service Report">{{ __('sidebar.purchase_service_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-transaction-summary.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-transaction-summary') }}" class="menu-link">
                                            <div data-i18n="Service Transaction Summary">{{ __('sidebar.service_transaction_summary') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-payment-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-payment-report') }}" class="menu-link">
                                            <div data-i18n="Service Payment Report">{{ __('sidebar.service_payment_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny
        @endif

        {{-- Customers (business-scoped Customer CRUD + Customer Payments + Customer Reports) --}}
        @canAccessAny(['customer.view', 'customer-payment.view', 'reports.customer-ledger.view', 'reports.customer-aging.view', 'reports.customer-payment-history.view', 'reports.customer-loyalty-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-users"></i>
                    <div data-i18n="Customers">{{ __('sidebar.customers') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('customer.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/customer') }}" class="menu-link">
                                <div data-i18n="Customers">{{ __('sidebar.customers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('customer-payment.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/customer-payment') }}" class="menu-link">
                                <div data-i18n="Customer Payments">{{ __('sidebar.customer_payments') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.customer-ledger.view', 'reports.customer-aging.view', 'reports.customer-payment-history.view', 'reports.customer-loyalty-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.customer-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-ledger') }}" class="menu-link">
                                            <div data-i18n="Customer Ledger">{{ __('sidebar.customer_ledger') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-aging.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-aging') }}" class="menu-link">
                                            <div data-i18n="Customer Aging">{{ __('sidebar.customer_aging') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-payment-history.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-payment-history') }}" class="menu-link">
                                            <div data-i18n="Customer Payment History">{{ __('sidebar.customer_payment_history') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-loyalty-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-loyalty-report') }}" class="menu-link">
                                            <div data-i18n="Customer Loyalty History">{{ __('sidebar.customer_loyalty_history') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny

        {{-- Orders (centralized - shared by POS, Website, Mobile App, API) --}}
        @canAccessAny(['pos.access', 'order-return.view', 'order-type.view', 'order-source.view', 'payment-method.view', 'payment-gateway.view', 'payment-transaction.view', 'discount.view', 'voucher.view',
            'reports.order-detail.view', 'reports.product-sales.view', 'reports.variation-sales.view', 'reports.customer-sales.view',
            'reports.branch-sales.view', 'reports.order-source-sales.view', 'reports.payment-method-sales.view',
            'reports.order-status-report.view', 'reports.cancelled-orders.view', 'reports.due-credit-sales.view',
            'reports.discount-report.view', 'reports.loyalty-report.view', 'reports.order-tax-report.view', 'reports.top-selling.view', 'reports.offline-orders-report.view',
            'reports.order-correction-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-receipt"></i>
                    <div data-i18n="Orders">{{ __('sidebar.orders') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('pos.access')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order') }}" class="menu-link">
                                <div data-i18n="Orders">{{ __('sidebar.orders') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-return') }}" class="menu-link">
                                <div data-i18n="Order Returns">{{ __('sidebar.order_returns') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-type') }}" class="menu-link">
                                <div data-i18n="Order Types">{{ __('sidebar.order_types') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-source.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-source') }}" class="menu-link">
                                <div data-i18n="Order Sources">{{ __('sidebar.order_sources') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payment-method.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payment-method') }}" class="menu-link">
                                <div data-i18n="Payment Methods">{{ __('sidebar.payment_methods') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payment-gateway.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payment-gateway') }}" class="menu-link">
                                <div data-i18n="Payment Gateways">{{ __('sidebar.payment_gateways') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payment-transaction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payment-transaction') }}" class="menu-link">
                                <div data-i18n="Payment Gateway Transactions">{{ __('sidebar.payment_gateway_transactions') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('discount.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/discount') }}" class="menu-link">
                                <div data-i18n="Discounts">{{ __('sidebar.discounts') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('voucher.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/voucher') }}" class="menu-link">
                                <div data-i18n="Vouchers">{{ __('sidebar.vouchers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.order-detail.view', 'reports.product-sales.view', 'reports.variation-sales.view',
                        'reports.customer-sales.view', 'reports.branch-sales.view', 'reports.order-source-sales.view',
                        'reports.payment-method-sales.view', 'reports.order-status-report.view', 'reports.cancelled-orders.view',
                        'reports.due-credit-sales.view', 'reports.discount-report.view', 'reports.loyalty-report.view', 'reports.order-tax-report.view',
                        'reports.top-selling.view', 'reports.offline-orders-report.view', 'reports.order-correction-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Order Reports">{{ __('sidebar.reports') }}</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.order-detail.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-detail') }}" class="menu-link">
                                            <div data-i18n="Order Detail Report">{{ __('sidebar.order_detail_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.product-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/product-sales') }}" class="menu-link">
                                            <div data-i18n="Product Sales">{{ __('sidebar.product_wise_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.variation-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/variation-sales') }}" class="menu-link">
                                            <div data-i18n="Variation Sales">{{ __('sidebar.variation_wise_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-sales') }}" class="menu-link">
                                            <div data-i18n="Customer Sales">{{ __('sidebar.customer_wise_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-sales') }}" class="menu-link">
                                            <div data-i18n="Branch Sales">{{ __('sidebar.branch_wise_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-source-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-source-sales') }}" class="menu-link">
                                            <div data-i18n="Order Source Sales">{{ __('sidebar.order_source_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payment-method-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payment-method-sales') }}" class="menu-link">
                                            <div data-i18n="Payment Method Sales">{{ __('sidebar.payment_method_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-status-report') }}" class="menu-link">
                                            <div data-i18n="Order Status Report">{{ __('sidebar.order_status_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cancelled-orders.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cancelled-orders') }}" class="menu-link">
                                            <div data-i18n="Cancelled Orders">{{ __('sidebar.cancelled_orders') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.due-credit-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/due-credit-sales') }}" class="menu-link">
                                            <div data-i18n="Due Credit Sales">{{ __('sidebar.due_credit_sales') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.discount-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/discount-report') }}" class="menu-link">
                                            <div data-i18n="Discount Report">{{ __('sidebar.discount_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.loyalty-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/loyalty-report') }}" class="menu-link">
                                            <div data-i18n="Loyalty Report">{{ __('sidebar.loyalty_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-tax-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-tax-report') }}" class="menu-link">
                                            <div data-i18n="Order Tax Report">{{ __('sidebar.order_tax_report') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.top-selling.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/top-selling') }}" class="menu-link">
                                            <div data-i18n="Top Selling">{{ __('sidebar.top_selling') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.offline-orders-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/offline-orders-report') }}" class="menu-link">
                                            <div data-i18n="Offline Orders Report">{{ __('sidebar.offline_orders') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-correction-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-correction-report') }}" class="menu-link">
                                            <div data-i18n="Order Correction Report">{{ __('sidebar.order_corrections') }}</div>
                                        </a>
                                    </li>
                                @endcanAccess
                            </ul>
                        </li>
                    @endcanAccessAny
                </ul>
            </li>
        @endcanAccessAny

        {{-- Website CMS --}}
        @canAccessAny(['website-section.view', 'website-page.view', 'website-faq.view', 'social-media.view', 'website-hero-stat.view', 'website-benefit.view', 'website-testimonial.view', 'contact-message.view', 'product-review.view', 'newsletter-subscriber.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-globe"></i>
                    <div data-i18n="Website CMS">{{ __('sidebar.website_cms') }}</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('website-section.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-section') }}" class="menu-link">
                                <div data-i18n="Homepage Sections">{{ __('sidebar.homepage_sections') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-page.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-page') }}" class="menu-link">
                                <div data-i18n="Pages & Policies">{{ __('sidebar.pages_policies') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-faq.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-faq') }}" class="menu-link">
                                <div data-i18n="FAQ / Help Center">{{ __('sidebar.faq_help_center') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('social-media.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/social-media') }}" class="menu-link">
                                <div data-i18n="Social Media">{{ __('sidebar.social_media') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-hero-stat.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-hero-stat') }}" class="menu-link">
                                <div data-i18n="Hero Stats">{{ __('sidebar.hero_stats') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-benefit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-benefit') }}" class="menu-link">
                                <div data-i18n="Content Cards">{{ __('sidebar.content_cards') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-testimonial.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-testimonial') }}" class="menu-link">
                                <div data-i18n="Testimonials">{{ __('sidebar.testimonials') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('contact-message.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/contact-message') }}" class="menu-link">
                                <div data-i18n="Contact Messages">{{ __('sidebar.contact_messages') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('product-review.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-review') }}" class="menu-link">
                                <div data-i18n="Reviews">{{ __('sidebar.reviews') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('newsletter-subscriber.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/newsletter-subscriber') }}" class="menu-link">
                                <div data-i18n="Newsletter Subscribers">{{ __('sidebar.newsletter_subscribers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny

        {{-- POS (operational interface only) --}}
        @canAccess('pos.access')
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-cash-register"></i>
                    <div data-i18n="POS">{{ __('sidebar.pos') }}</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ url('/admin/pos-screen') }}" class="menu-link">
                            <div data-i18n="POS Screen">{{ __('sidebar.pos_screen') }}</div>
                        </a>
                    </li>
                    @canAccess('pos-register.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/pos-register') }}" class="menu-link">
                                <div data-i18n="Registers">{{ __('sidebar.registers') }}</div>
                            </a>
                        </li>
                    @endcanAccess
                    <li class="menu-item">
                        <a href="{{ url('/admin/pos-register-session') }}" class="menu-link">
                            <div data-i18n="Register Sessions">{{ __('sidebar.register_sessions') }}</div>
                        </a>
                    </li>
                </ul>
            </li>
        @endcanAccess
    <br>
    <br>
    <br>
    <br>
        @endif
    </ul>
</aside>
<!-- / Menu -->
