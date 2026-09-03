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
                        <div data-i18n="My Subscription">My Subscription</div>
                    </a>
                </li>
            @endcanAccess
            <li class="menu-item">
                <a href="{{ route('profile.edit') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-user"></i>
                    <div data-i18n="Profile">Profile</div>
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
                <div data-i18n="Analytics">Dashboard</div>
            </a>
        </li>
        <!-- Self Service (Employee) -->
        @canAccessAny(['ess.dashboard.view', 'ess.attendance.manage', 'ess.leave.view', 'ess.payslip.view', 'ess.profile.view', 'ess.advance.apply', 'ess.resignation.apply'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-id-badge"></i>
                    <div data-i18n="Self Service">Self Service</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('ess.dashboard.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.dashboard') }}" class="menu-link">
                                <div data-i18n="My Dashboard">My Dashboard</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.attendance.manage')
                        <li class="menu-item">
                            <a href="{{ route('ess.attendance.index') }}" class="menu-link">
                                <div data-i18n="My Attendance">My Attendance</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.leave.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.leave.index') }}" class="menu-link">
                                <div data-i18n="My Leave">My Leave</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.payslip.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.payslip.index') }}" class="menu-link">
                                <div data-i18n="My Salary Slips">My Salary Slips</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.advance.apply')
                        <li class="menu-item">
                            <a href="{{ route('ess.advance.index') }}" class="menu-link">
                                <div data-i18n="My Advances">My Advances</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.resignation.apply')
                        <li class="menu-item">
                            <a href="{{ route('ess.exit.index') }}" class="menu-link">
                                <div data-i18n="My Resignation">My Resignation</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('ess.profile.view')
                        <li class="menu-item">
                            <a href="{{ route('ess.profile.index') }}" class="menu-link">
                                <div data-i18n="My Profile">My Profile</div>
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
                    <div data-i18n="Business Manage.">Business Manage.</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('package.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/packages') }}" class="menu-link">
                                <div data-i18n="Package">Packages</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('business.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/business') }}" class="menu-link">
                                <div data-i18n="Business">Business</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('branch.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/branch') }}" class="menu-link">
                                <div data-i18n="Branch">Branch</div>
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
                    <div data-i18n="Subscriptions">Subscriptions &amp; Billing</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ route('subscriptions.dashboard') }}" class="menu-link">
                            <div data-i18n="Subscriptions">Dashboard</div>
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
                            <div data-i18n="Subscription Settings">Settings</div>
                        </a>
                    </li>
                </ul>
            </li>
            <!-- Dukanaz Intro CMS -->
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-globe"></i>
                    <div data-i18n="Intro CMS">Intro CMS</div>
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
                        <div data-i18n="My Subscription">My Subscription</div>
                    </a>
                </li>
            @endcanAccess
        @endif
        <!-- Users -->
        @canAccessAny(['permission.view', 'role.view', 'user.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-users"></i>
                    <div data-i18n="Users Management">Users Management</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('permission.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/permissions') }}" class="menu-link">
                                <div data-i18n="Permissions">Permissions</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('role.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/roles') }}" class="menu-link">
                                <div data-i18n="Roles">Roles</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('user.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/users') }}" class="menu-link">
                                <div data-i18n="Admin Users">Admin Users</div>
                            </a>
                        </li>
                    @endcanAccess
                </ul>
            </li>
        @endcanAccessAny

        <!-- Inventory -->
        @if (businessModuleEnabled('inventory'))
        @canAccessAny(['unit.view', 'warehouse.view', 'brand.view', 'category.view', 'sub-category.view', 'product.view',
            'unit-conversion.view', 'batch.view', 'stock.view', 'stock-transaction.view', 'opening-stock.view',
            'stock-taking.view', 'transfer-note.view', 'reports.stock-ledger.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-box"></i>
                    <div data-i18n="Inventory">Inventory</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('unit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/unit') }}" class="menu-link">
                                <div data-i18n="Unit">Units</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('warehouse.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/warehouse') }}" class="menu-link">
                                <div data-i18n="Warehouse">Warehouse</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('brand.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/brands') }}" class="menu-link">
                                <div data-i18n="Brands">Brands</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/category') }}" class="menu-link">
                                <div data-i18n="Categories">Categories</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('sub-category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/sub-category') }}" class="menu-link">
                                <div data-i18n="Sub Categories">Sub Categories</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('product.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product') }}" class="menu-link">
                                <div data-i18n="Products">Products</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('unit-conversion.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-unit-conversion') }}" class="menu-link">
                                <div data-i18n="Unit Conversion">Unit Conversion</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('batch.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-batch') }}" class="menu-link">
                                <div data-i18n="Batches">Batches</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-stock') }}" class="menu-link">
                                <div data-i18n="Stock">Stock</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock-transaction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-variation-stock-transaction') }}" class="menu-link">
                                <div data-i18n="Transactions">Transactions</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('opening-stock.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/opening-stock') }}" class="menu-link">
                                <div data-i18n="Opening Stock">Opening Stock</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('stock-taking.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/stock-taking') }}" class="menu-link">
                                <div data-i18n="Stock Taking">Stock Taking</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('transfer-note.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/transfer-note') }}" class="menu-link">
                                <div data-i18n="Transfer Note">Transfer Note</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('reports.stock-ledger.view')
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Stock Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                <li class="menu-item">
                                    <a href="{{ url('/admin/reports/stock-ledger') }}" class="menu-link">
                                        <div data-i18n="Stock Ledger">Stock Ledger &amp; Movement</div>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endcanAccess
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
                    <div data-i18n="Accounting">Accounting</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('account-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account-type') }}" class="menu-link">
                                <div data-i18n="Account Types">Account Types</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('account-sub-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account-sub-type') }}" class="menu-link">
                                <div data-i18n="Account Sub Types">Account Sub Types</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('journal.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/journal') }}" class="menu-link">
                                <div data-i18n="Journals">Journals</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('account.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/account') }}" class="menu-link">
                                <div data-i18n="Accounts">Accounts</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('journal-entry.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/journal-entry') }}" class="menu-link">
                                <div data-i18n="Journal Entries">Journal Entries</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('bank-reconciliation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/bank-reconciliation') }}" class="menu-link">
                                <div data-i18n="Bank Reconciliation">Bank Reconciliation</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('recurring-transaction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/recurring-transaction') }}" class="menu-link">
                                <div data-i18n="Recurring Transactions">Recurring Transactions</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset-category.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset-category') }}" class="menu-link">
                                <div data-i18n="Fixed Asset Categories">Fixed Asset Categories</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset') }}" class="menu-link">
                                <div data-i18n="Fixed Assets">Fixed Assets</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('fixed-asset-depreciation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/fixed-asset-depreciation') }}" class="menu-link">
                                <div data-i18n="Depreciation">Depreciation</div>
                            </a>
                        </li>
                    @endcanAccess
                    {{-- Advanced Accounting Mode - hidden by default, see
                         CommonFunctions::businessAccountingAdvancedModeEnabled() --}}
                    @if (businessAccountingAdvancedModeEnabled())
                        @canAccess('fiscal-year.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/fiscal-year') }}" class="menu-link">
                                    <div data-i18n="Fiscal Years">Fiscal Years</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('accounting-period.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/accounting-period') }}" class="menu-link">
                                    <div data-i18n="Accounting Periods">Accounting Periods</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('period-closing-rule.manage')
                            <li class="menu-item">
                                <a href="{{ url('/admin/period-closing-rule') }}" class="menu-link">
                                    <div data-i18n="Closing Rules">Closing Rules</div>
                                </a>
                            </li>
                        @endcanAccess
                        @canAccess('budget.view')
                            <li class="menu-item">
                                <a href="{{ url('/admin/budget') }}" class="menu-link">
                                    <div data-i18n="Budgets">Budgets</div>
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
                                <div data-i18n="Accounting Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.accounts-payable.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/accounts-payable') }}" class="menu-link">
                                            <div data-i18n="Accounts Payable">Accounts Payable</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.general-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/general-ledger') }}" class="menu-link">
                                            <div data-i18n="General Ledger">General Ledger</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.trial-balance.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/trial-balance') }}" class="menu-link">
                                            <div data-i18n="Trial Balance">Trial Balance</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.journal-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/journal-register') }}" class="menu-link">
                                            <div data-i18n="Journal Register">Journal Register</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.account-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/account-ledger') }}" class="menu-link">
                                            <div data-i18n="Account Ledger">Account Ledger</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.account-balance.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/account-balance') }}" class="menu-link">
                                            <div data-i18n="Account Balance">Account Balance</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.day-book.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/day-book') }}" class="menu-link">
                                            <div data-i18n="Day Book">Day Book</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.profit-loss.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/profit-loss') }}" class="menu-link">
                                            <div data-i18n="Profit & Loss">Profit & Loss</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.balance-sheet.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/balance-sheet') }}" class="menu-link">
                                            <div data-i18n="Balance Sheet">Balance Sheet</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cash-flow.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cash-flow') }}" class="menu-link">
                                            <div data-i18n="Cash Flow Statement">Cash Flow Statement</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.fixed-asset-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/fixed-asset-register') }}" class="menu-link">
                                            <div data-i18n="Fixed Asset Register">Fixed Asset Register</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.depreciation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/depreciation-report') }}" class="menu-link">
                                            <div data-i18n="Depreciation Report">Depreciation Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-valuation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-valuation-report') }}" class="menu-link">
                                            <div data-i18n="Asset Valuation Report">Asset Valuation Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-disposal-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-disposal-report') }}" class="menu-link">
                                            <div data-i18n="Asset Disposal Report">Asset Disposal Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cash-bank-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cash-bank-ledger') }}" class="menu-link">
                                            <div data-i18n="Cash & Bank Ledger">Cash & Bank Ledger</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.income-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/income-report') }}" class="menu-link">
                                            <div data-i18n="Income Report">Income Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.sales-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/sales-report') }}" class="menu-link">
                                            <div data-i18n="Sales Report">Sales Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.voucher-usage.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/voucher-usage-report') }}" class="menu-link">
                                            <div data-i18n="Voucher Usage Report">Voucher Usage Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.expense-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/expense-report') }}" class="menu-link">
                                            <div data-i18n="Expense Report">Expense Report (By Account)</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.tax-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/tax-report') }}" class="menu-link">
                                            <div data-i18n="Tax Reports">Tax Reports</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.equity-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/equity-report') }}" class="menu-link">
                                            <div data-i18n="Equity Report">Equity Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                {{-- Visible in both Simple and Advanced Accounting Mode -
                                     read-only, speaks "budget/actual/variance" not debit/credit. --}}
                                @canAccess('reports.budget-vs-actual.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/budget-vs-actual') }}" class="menu-link">
                                            <div data-i18n="Budget vs Actual">Budget vs Actual</div>
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
                    <div data-i18n="Expense">Expense</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('expense-category.manage')
                        <li class="menu-item">
                            <a href="{{ url('/admin/expense-category') }}" class="menu-link">
                                <div data-i18n="Expense Category">Expense Category</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('expense.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/expense') }}" class="menu-link">
                                <div data-i18n="Expense Detail (POS)">Expense Detail (POS)</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('admin-expense.manage')
                        <li class="menu-item">
                            <a href="{{ url('/admin/admin-expense') }}" class="menu-link">
                                <div data-i18n="Admin Expenses">Admin Expenses</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('reports.expense-detail-report.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/reports/expense-detail-report') }}" class="menu-link">
                                <div data-i18n="Expense Reports">Expense Reports</div>
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
                    <div data-i18n="HR & Payroll">HR &amp; Payroll</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('reports.hr-dashboard-report.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/reports/hr-dashboard-report') }}" class="menu-link">
                                <div data-i18n="HR Dashboard">HR Dashboard</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee') }}" class="menu-link">
                                <div data-i18n="Employees">Employees</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('department.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/department') }}" class="menu-link">
                                <div data-i18n="Departments">Departments</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('designation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/designation') }}" class="menu-link">
                                <div data-i18n="Designations">Designations</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('shift.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/shift') }}" class="menu-link">
                                <div data-i18n="Shifts">Shifts</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('attendance.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/attendance') }}" class="menu-link">
                                <div data-i18n="Attendance">Attendance</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('leave-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/leave-type') }}" class="menu-link">
                                <div data-i18n="Leave Types">Leave Types</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('leave-request.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/leave-request') }}" class="menu-link">
                                <div data-i18n="Leave Requests">Leave Requests</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('salary-component.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/salary-component') }}" class="menu-link">
                                <div data-i18n="Salary Components">Salary Components</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('salary-structure.view')
                        <li class="menu-item">
                            <a href="{{ route('salary-structure.index') }}" class="menu-link">
                                <div data-i18n="Salary Structures">Salary Structures</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payroll.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payroll') }}" class="menu-link">
                                <div data-i18n="Payroll">Payroll</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-advance.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-advance') }}" class="menu-link">
                                <div data-i18n="Employee Advances">Employee Advances</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-deduction.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-deduction') }}" class="menu-link">
                                <div data-i18n="Employee Deductions">Employee Deductions</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-ledger.view')
                        <li class="menu-item">
                            <a href="{{ route('employee-ledger.index') }}" class="menu-link">
                                <div data-i18n="Employee Ledger">Employee Ledger</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('employee-exit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/employee-exit') }}" class="menu-link">
                                <div data-i18n="Resignation / Termination">Resignation / Termination</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('asset.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/asset') }}" class="menu-link">
                                <div data-i18n="Assets">Assets</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('asset-allocation.view')
                        <li class="menu-item">
                            <a href="{{ route('asset-allocation.index') }}" class="menu-link">
                                <div data-i18n="Asset Allocation">Asset Allocation</div>
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
                                <div data-i18n="HR Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.employee-master-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-master-report') }}" class="menu-link">
                                            <div data-i18n="Employee Master Report">Employee Master Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-directory-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-directory-report') }}" class="menu-link">
                                            <div data-i18n="Employee Directory Report">Employee Directory Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-joining-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-joining-report') }}" class="menu-link">
                                            <div data-i18n="Employee Joining Report">Employee Joining Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-exit-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-exit-report') }}" class="menu-link">
                                            <div data-i18n="Employee Exit Report">Employee Exit Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Employee Report">Department-wise Employee Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.designation-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/designation-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Designation-wise Employee Report">Designation-wise Employee Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-wise-employee-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-wise-employee-report') }}" class="menu-link">
                                            <div data-i18n="Branch-wise Employee Report">Branch-wise Employee Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-status-report') }}" class="menu-link">
                                            <div data-i18n="Employee Status Report">Employee Status Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-summary-report') }}" class="menu-link">
                                            <div data-i18n="Attendance Summary Report">Attendance Summary Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.daily-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/daily-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Daily Attendance Report">Daily Attendance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.monthly-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/monthly-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Monthly Attendance Report">Monthly Attendance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-register') }}" class="menu-link">
                                            <div data-i18n="Attendance Register">Attendance Register</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.late-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/late-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Late Attendance Report">Late Attendance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.early-checkout-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/early-checkout-report') }}" class="menu-link">
                                            <div data-i18n="Early Checkout Report">Early Checkout Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.absent-employees-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/absent-employees-report') }}" class="menu-link">
                                            <div data-i18n="Absent Employees Report">Absent Employees Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.missing-checkin-checkout-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/missing-checkin-checkout-report') }}" class="menu-link">
                                            <div data-i18n="Missing Check-In/Check-Out Report">Missing Check-In/Check-Out Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.overtime-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/overtime-report') }}" class="menu-link">
                                            <div data-i18n="Overtime Report">Overtime Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.shift-wise-attendance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/shift-wise-attendance-report') }}" class="menu-link">
                                            <div data-i18n="Shift-wise Attendance Report">Shift-wise Attendance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.shift-assignment-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/shift-assignment-report') }}" class="menu-link">
                                            <div data-i18n="Shift Assignment Report">Shift Assignment Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-summary-report') }}" class="menu-link">
                                            <div data-i18n="Leave Summary Report">Leave Summary Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-leave-history-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-leave-history-report') }}" class="menu-link">
                                            <div data-i18n="Employee Leave History Report">Employee Leave History Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-type-wise-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-type-wise-report') }}" class="menu-link">
                                            <div data-i18n="Leave Type-wise Report">Leave Type-wise Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-leave-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-leave-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Leave Report">Department-wise Leave Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.pending-leave-approval-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/pending-leave-approval-report') }}" class="menu-link">
                                            <div data-i18n="Pending Leave Approval Report">Pending Leave Approval Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-approval-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-approval-status-report') }}" class="menu-link">
                                            <div data-i18n="Leave Approval Status Report">Leave Approval Status Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-balance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-balance-report') }}" class="menu-link">
                                            <div data-i18n="Leave Balance Report">Leave Balance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-structure-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-structure-report') }}" class="menu-link">
                                            <div data-i18n="Salary Structure Report">Salary Structure Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-component-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-component-report') }}" class="menu-link">
                                            <div data-i18n="Salary Component Report">Salary Component Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.deduction-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/deduction-report') }}" class="menu-link">
                                            <div data-i18n="Deduction Report">Deduction Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-advance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-advance-report') }}" class="menu-link">
                                            <div data-i18n="Employee Advance Report">Employee Advance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.advance-recovery-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/advance-recovery-report') }}" class="menu-link">
                                            <div data-i18n="Advance Recovery Report">Advance Recovery Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-ledger-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-ledger-report') }}" class="menu-link">
                                            <div data-i18n="Employee Ledger Report">Employee Ledger Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-summary-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-summary-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Summary Report">Payroll Summary Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Employee-wise Payroll Report">Employee-wise Payroll Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Department-wise Payroll Report">Department-wise Payroll Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-wise-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-wise-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Branch-wise Payroll Report">Branch-wise Payroll Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.monthly-payroll-register.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/monthly-payroll-register') }}" class="menu-link">
                                            <div data-i18n="Monthly Payroll Register">Monthly Payroll Register</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Cost Report">Payroll Cost Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.pending-payroll-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/pending-payroll-report') }}" class="menu-link">
                                            <div data-i18n="Pending Payroll Report">Pending Payroll Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.salary-slip-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/salary-slip-report') }}" class="menu-link">
                                            <div data-i18n="Salary Slip Report">Salary Slip Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payroll-disbursement-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payroll-disbursement-report') }}" class="menu-link">
                                            <div data-i18n="Payroll Payment/Disbursement Report">Payroll Payment/Disbursement Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.resignation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/resignation-report') }}" class="menu-link">
                                            <div data-i18n="Resignation Report">Resignation Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.termination-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/termination-report') }}" class="menu-link">
                                            <div data-i18n="Termination Report">Termination Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-clearance-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-clearance-report') }}" class="menu-link">
                                            <div data-i18n="Employee Clearance Report">Employee Clearance Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.asset-allocation-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/asset-allocation-report') }}" class="menu-link">
                                            <div data-i18n="Asset Allocation Report">Asset Allocation Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-asset-return-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-asset-return-report') }}" class="menu-link">
                                            <div data-i18n="Employee Asset Return Report">Employee Asset Return Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-document-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-document-report') }}" class="menu-link">
                                            <div data-i18n="Employee Document Report">Employee Document Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-lifecycle-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-lifecycle-report') }}" class="menu-link">
                                            <div data-i18n="Employee Lifecycle Report">Employee Lifecycle Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.attendance-payroll-comparison-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/attendance-payroll-comparison-report') }}" class="menu-link">
                                            <div data-i18n="Attendance & Payroll Comparison Report">Attendance & Payroll Comparison Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.leave-payroll-impact-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/leave-payroll-impact-report') }}" class="menu-link">
                                            <div data-i18n="Leave & Payroll Impact Report">Leave & Payroll Impact Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.employee-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/employee-cost-report') }}" class="menu-link">
                                            <div data-i18n="Employee Cost Report">Employee Cost Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.department-payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/department-payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Department Payroll Cost Report">Department Payroll Cost Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-payroll-cost-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-payroll-cost-report') }}" class="menu-link">
                                            <div data-i18n="Branch Payroll Cost Report">Branch Payroll Cost Report</div>
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
                    <div data-i18n="Documentation">Documentation</div>
                </a>
            </li>
        @endcanAccess

        {{-- Notifications --}}
        @canAccess('notification.view')
            <li class="menu-item">
                <a href="{{ url('/admin/notifications') }}" class="menu-link">
                    <i class="menu-icon tf-icons fa fa-bell"></i>
                    <div data-i18n="Notifications">Notifications</div>
                </a>
            </li>
        @endcanAccess

        {{-- Push Notifications (FCM) --}}
        @canAccessAny(['notification-template.view', 'broadcast-notification.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-mobile-alt"></i>
                    <div data-i18n="Push Notifications">Push Notifications</div>
                </a>
                <ul class="menu-sub">
                    @canAccess('notification-template.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/notification-template') }}" class="menu-link">
                                <div data-i18n="Notification Templates">Notification Templates</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('broadcast-notification.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/broadcast-notification') }}" class="menu-link">
                                <div data-i18n="Broadcast Notifications">Broadcast Notifications</div>
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
                    <div data-i18n="Audit & Security">Audit &amp; Security</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('activity-log.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/activity-log') }}" class="menu-link">
                                <div data-i18n="Activity Log">Activity Log</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('login-history.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/login-history') }}" class="menu-link">
                                <div data-i18n="Login History">Login History</div>
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
                    <div data-i18n="Procurement">Procurement</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('supplier.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/supplier') }}" class="menu-link">
                                <div data-i18n="Suppliers">Suppliers</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-request.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-request') }}" class="menu-link">
                                <div data-i18n="Purchase Requests">Purchase Requests</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-request-quotation.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-request-quotation') }}" class="menu-link">
                                <div data-i18n="Purchase Request Quotations">Quotations</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase') }}" class="menu-link">
                                <div data-i18n="Purchases">Purchases</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('good-receipt-note.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/good-receipt-note') }}" class="menu-link">
                                <div data-i18n="Goods Receipt Notes">Goods Receipt Notes</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('purchase-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/purchase-return') }}" class="menu-link">
                                <div data-i18n="Purchase Returns">Purchase Returns</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('supplier-payment.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/supplier-payment') }}" class="menu-link">
                                <div data-i18n="Supplier Payments">Supplier Payments</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.supplier-ledger.view', 'reports.supplier-aging.view',
                        'reports.supplier-payment-history.view', 'reports.purchase-return-summary.view',
                        'reports.purchase-return-detail.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Purchase Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.supplier-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-ledger') }}" class="menu-link">
                                            <div data-i18n="Supplier Ledger">Supplier Ledger</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.supplier-aging.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-aging') }}" class="menu-link">
                                            <div data-i18n="Supplier Aging">Supplier Aging</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.supplier-payment-history.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/supplier-payment-history') }}" class="menu-link">
                                            <div data-i18n="Supplier Payment History">Supplier Payment History</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.purchase-return-summary.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/purchase-return-summary') }}" class="menu-link">
                                            <div data-i18n="Purchase Return Summary">Purchase Return Summary</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.purchase-return-detail.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/purchase-return-detail') }}" class="menu-link">
                                            <div data-i18n="Purchase Return Detail">Purchase Return Detail</div>
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
                    <div data-i18n="Service Management">Service Management</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('service-purchase.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-purchase') }}" class="menu-link">
                                <div data-i18n="Service Purchases">Service Purchases</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-purchase-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-purchase-return') }}" class="menu-link">
                                <div data-i18n="Service Purchase Returns">Service Purchase Returns</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-sale.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-sale') }}" class="menu-link">
                                <div data-i18n="Service Sales">Service Sales</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('service-sale-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/service-sale-return') }}" class="menu-link">
                                <div data-i18n="Service Sale Returns">Service Sale Returns</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.service-sale-report.view', 'reports.service-purchase-report.view',
                        'reports.service-transaction-summary.view', 'reports.service-payment-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Service Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.service-sale-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-sale-report') }}" class="menu-link">
                                            <div data-i18n="Sale Service Report">Sale Service Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-purchase-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-purchase-report') }}" class="menu-link">
                                            <div data-i18n="Purchase Service Report">Purchase Service Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-transaction-summary.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-transaction-summary') }}" class="menu-link">
                                            <div data-i18n="Service Transaction Summary">Service Transaction Summary</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.service-payment-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/service-payment-report') }}" class="menu-link">
                                            <div data-i18n="Service Payment Report">Service Payment Report</div>
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
                    <div data-i18n="Customers">Customers</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('customer.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/customer') }}" class="menu-link">
                                <div data-i18n="Customers">Customers</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('customer-payment.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/customer-payment') }}" class="menu-link">
                                <div data-i18n="Customer Payments">Customer Payments</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccessAny(['reports.customer-ledger.view', 'reports.customer-aging.view', 'reports.customer-payment-history.view', 'reports.customer-loyalty-report.view'])
                        <li class="menu-item">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <div data-i18n="Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.customer-ledger.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-ledger') }}" class="menu-link">
                                            <div data-i18n="Customer Ledger">Customer Ledger</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-aging.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-aging') }}" class="menu-link">
                                            <div data-i18n="Customer Aging">Customer Aging</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-payment-history.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-payment-history') }}" class="menu-link">
                                            <div data-i18n="Customer Payment History">Customer Payment History</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-loyalty-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-loyalty-report') }}" class="menu-link">
                                            <div data-i18n="Customer Loyalty History">Customer Loyalty History</div>
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
        @canAccessAny(['pos.access', 'order-return.view', 'order-type.view', 'order-source.view', 'payment-method.view', 'discount.view', 'voucher.view',
            'reports.order-detail.view', 'reports.product-sales.view', 'reports.variation-sales.view', 'reports.customer-sales.view',
            'reports.branch-sales.view', 'reports.order-source-sales.view', 'reports.payment-method-sales.view',
            'reports.order-status-report.view', 'reports.cancelled-orders.view', 'reports.due-credit-sales.view',
            'reports.discount-report.view', 'reports.loyalty-report.view', 'reports.order-tax-report.view', 'reports.top-selling.view', 'reports.offline-orders-report.view',
            'reports.order-correction-report.view'])
            <li class="menu-item">
                <a href="javascript:void(0);" class="menu-link menu-toggle">
                    <i class="menu-icon tf-icons fa fa-receipt"></i>
                    <div data-i18n="Orders">Orders</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('pos.access')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order') }}" class="menu-link">
                                <div data-i18n="Orders">Orders</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-return.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-return') }}" class="menu-link">
                                <div data-i18n="Order Returns">Order Returns</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-type.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-type') }}" class="menu-link">
                                <div data-i18n="Order Types">Order Types</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('order-source.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/order-source') }}" class="menu-link">
                                <div data-i18n="Order Sources">Order Sources</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('payment-method.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/payment-method') }}" class="menu-link">
                                <div data-i18n="Payment Methods">Payment Methods</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('discount.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/discount') }}" class="menu-link">
                                <div data-i18n="Discounts">Discounts</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('voucher.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/voucher') }}" class="menu-link">
                                <div data-i18n="Vouchers">Vouchers</div>
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
                                <div data-i18n="Order Reports">Reports</div>
                            </a>
                            <ul class="menu-sub">
                                @canAccess('reports.order-detail.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-detail') }}" class="menu-link">
                                            <div data-i18n="Order Detail Report">Order Detail Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.product-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/product-sales') }}" class="menu-link">
                                            <div data-i18n="Product Sales">Product-wise Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.variation-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/variation-sales') }}" class="menu-link">
                                            <div data-i18n="Variation Sales">Variation-wise Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.customer-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/customer-sales') }}" class="menu-link">
                                            <div data-i18n="Customer Sales">Customer-wise Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.branch-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/branch-sales') }}" class="menu-link">
                                            <div data-i18n="Branch Sales">Branch-wise Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-source-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-source-sales') }}" class="menu-link">
                                            <div data-i18n="Order Source Sales">Order Source Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.payment-method-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/payment-method-sales') }}" class="menu-link">
                                            <div data-i18n="Payment Method Sales">Payment Method Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-status-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-status-report') }}" class="menu-link">
                                            <div data-i18n="Order Status Report">Order Status Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.cancelled-orders.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/cancelled-orders') }}" class="menu-link">
                                            <div data-i18n="Cancelled Orders">Cancelled Orders</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.due-credit-sales.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/due-credit-sales') }}" class="menu-link">
                                            <div data-i18n="Due Credit Sales">Due / Credit Sales</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.discount-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/discount-report') }}" class="menu-link">
                                            <div data-i18n="Discount Report">Discount Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.loyalty-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/loyalty-report') }}" class="menu-link">
                                            <div data-i18n="Loyalty Report">Loyalty Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-tax-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-tax-report') }}" class="menu-link">
                                            <div data-i18n="Order Tax Report">Order Tax Report</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.top-selling.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/top-selling') }}" class="menu-link">
                                            <div data-i18n="Top Selling">Top Selling</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.offline-orders-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/offline-orders-report') }}" class="menu-link">
                                            <div data-i18n="Offline Orders Report">Offline Orders</div>
                                        </a>
                                    </li>
                                @endcanAccess
                                @canAccess('reports.order-correction-report.view')
                                    <li class="menu-item">
                                        <a href="{{ url('/admin/reports/order-correction-report') }}" class="menu-link">
                                            <div data-i18n="Order Correction Report">Order Corrections</div>
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
                    <div data-i18n="Website CMS">Website CMS</div>
                </a>

                <ul class="menu-sub">
                    @canAccess('website-section.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-section') }}" class="menu-link">
                                <div data-i18n="Homepage Sections">Homepage Sections</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-page.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-page') }}" class="menu-link">
                                <div data-i18n="Pages & Policies">Pages & Policies</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-faq.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-faq') }}" class="menu-link">
                                <div data-i18n="FAQ / Help Center">FAQ / Help Center</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('social-media.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/social-media') }}" class="menu-link">
                                <div data-i18n="Social Media">Social Media</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-hero-stat.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-hero-stat') }}" class="menu-link">
                                <div data-i18n="Hero Stats">Hero Stats</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-benefit.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-benefit') }}" class="menu-link">
                                <div data-i18n="Content Cards">Content Cards</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('website-testimonial.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/website-testimonial') }}" class="menu-link">
                                <div data-i18n="Testimonials">Testimonials</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('contact-message.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/contact-message') }}" class="menu-link">
                                <div data-i18n="Contact Messages">Contact Messages</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('product-review.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/product-review') }}" class="menu-link">
                                <div data-i18n="Reviews">Reviews</div>
                            </a>
                        </li>
                    @endcanAccess
                    @canAccess('newsletter-subscriber.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/newsletter-subscriber') }}" class="menu-link">
                                <div data-i18n="Newsletter Subscribers">Newsletter Subscribers</div>
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
                    <div data-i18n="POS">POS</div>
                </a>

                <ul class="menu-sub">
                    <li class="menu-item">
                        <a href="{{ url('/admin/pos-screen') }}" class="menu-link">
                            <div data-i18n="POS Screen">POS Screen</div>
                        </a>
                    </li>
                    @canAccess('pos-register.view')
                        <li class="menu-item">
                            <a href="{{ url('/admin/pos-register') }}" class="menu-link">
                                <div data-i18n="Registers">Registers</div>
                            </a>
                        </li>
                    @endcanAccess
                    <li class="menu-item">
                        <a href="{{ url('/admin/pos-register-session') }}" class="menu-link">
                            <div data-i18n="Register Sessions">Register Sessions</div>
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
