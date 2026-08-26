@extends('layouts.app')
@section('css')
    <style>
        /* Main Container Styles */
        .coa-container {
            --coa-primary: #3833C8;
            --coa-primary-soft: rgba(105, 108, 255, 0.08);
            --coa-primary-soft-hover: rgba(105, 108, 255, 0.13);
            --coa-border: #e7e7f0;
            --coa-line: #e2e1ee;
            --coa-text: #2b2c40;
            --coa-text-muted: #6f6b7d;
            --coa-surface-alt: #f8f7fc;
            --coa-success-soft: rgba(40, 167, 69, 0.1);
            --coa-radius: 12px;
            padding: 8px 0 24px;
        }

        /* Section Styles */
        .coa-section {
            margin-bottom: 16px;
            border: 1px solid var(--coa-border);
            border-radius: var(--coa-radius);
            overflow: hidden;
            background: #fff;
            box-shadow: 0 1px 2px rgba(43, 44, 64, 0.04);
            transition: box-shadow 0.25s ease, border-color 0.25s ease;
        }

        .coa-section:hover {
            box-shadow: 0 10px 24px rgba(43, 44, 64, 0.08);
            border-color: #dcdcf0;
        }

        .coa-section-header {
            background: linear-gradient(180deg, #fcfcff 0%, var(--coa-surface-alt) 100%);
            padding: 15px 22px;
            border-bottom: 1px solid var(--coa-border);
            cursor: pointer;
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .coa-section-header:hover {
            background: var(--coa-primary-soft);
        }

        .coa-section-header .section-title {
            font-weight: 600;
            color: var(--coa-text);
            font-size: 15px;
            letter-spacing: 0.1px;
        }

        .coa-section-header .section-title i.fa-folder-open {
            color: #fff;
            background: var(--coa-primary);
            font-size: 12px;
            width: 26px;
            height: 26px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(105, 108, 255, 0.35);
        }

        .coa-section-header .badge.bg-primary {
            background: var(--coa-primary) !important;
            font-weight: 500;
            font-size: 11.5px;
            padding: 4px 10px;
        }

        .coa-section-header .section-badge {
            background: var(--coa-primary);
            color: white;
            padding: 3px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .coa-section-header small.text-success {
            font-weight: 600;
            font-size: 12.5px;
            opacity: 0.9;
        }

        .coa-section-content {
            padding: 0;
            background: #fff;
            display: none;
        }

        /* Account Item Styles */
        .account-item {
            position: relative;
            padding: 12px 22px;
            border-bottom: 1px solid #f1f0f7;
            transition: background 0.15s ease, box-shadow 0.15s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-item:hover {
            background: var(--coa-primary-soft);
            box-shadow: inset 3px 0 0 var(--coa-primary);
        }

        /* Level-based indentation */
        .level-0 {
            padding-left: 22px;
            font-weight: 600;
            background: var(--coa-surface-alt);
        }

        .level-0:hover {
            background: var(--coa-primary-soft-hover);
        }

        .level-1 {
            padding-left: 54px;
        }

        .level-2 {
            padding-left: 84px;
        }

        .level-3 {
            padding-left: 114px;
        }

        .level-4 {
            padding-left: 144px;
        }

        /* Tree line indicators */
        .level-1::before,
        .level-2::before,
        .level-3::before,
        .level-4::before {
            content: '';
            position: absolute;
            left: 36px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: var(--coa-line);
        }

        .level-2::before {
            left: 66px;
        }

        .level-3::before {
            left: 96px;
        }

        .level-4::before {
            left: 126px;
        }

        /* Tree elbow connectors */
        .level-1::after,
        .level-2::after,
        .level-3::after,
        .level-4::after {
            content: '';
            position: absolute;
            left: 36px;
            top: 50%;
            width: 14px;
            height: 1px;
            background: var(--coa-line);
        }

        .level-2::after {
            left: 66px;
        }

        .level-3::after {
            left: 96px;
        }

        .level-4::after {
            left: 126px;
        }

        /* Action Buttons Container - Hidden by default */
        .account-actions {
            display: none;
            gap: 6px;
            align-items: center;
        }

        /* Show actions on hover */
        .account-item:hover .account-actions {
            display: flex;
        }

        /* Action Button Styles */
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--coa-text-muted);
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(43, 44, 64, 0.12);
        }

        .action-btn-edit:hover {
            background: #fff4dd;
            color: #a86400;
            border-color: #ffc107;
        }

        .action-btn-delete:hover {
            background: #fde3e6;
            color: #b3141e;
            border-color: #dc3545;
        }

        .action-btn-ledger:hover {
            background: var(--coa-primary-soft);
            color: var(--coa-primary);
            border-color: var(--coa-primary);
        }

        /* Account Info Styles */
        .account-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            min-width: 0;
        }

        .account-icon {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .account-icon-folder {
            background: #fff2d6;
            color: #a86400;
        }

        .account-icon-file {
            background: var(--coa-surface-alt);
            color: var(--coa-text-muted);
        }

        .account-name {
            font-weight: 500;
            color: var(--coa-text);
        }

        .account-code {
            color: var(--coa-text-muted);
            font-size: 13px;
            margin-left: 8px;
        }

        .account-balance {
            font-size: 12px;
            font-weight: 600;
            color: #1e7e34;
            background: var(--coa-success-soft);
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 15px;
            white-space: nowrap;
        }

        /* Arrow indicator for expandable sections */
        .expand-icon {
            transition: transform 0.25s ease;
            margin-right: 4px;
            color: var(--coa-text-muted);
        }

        .expand-icon.expanded {
            transform: rotate(90deg);
        }

        /* Child accounts container - hidden by default */
        .child-accounts {
            display: none;
        }

        .child-accounts.open {
            display: block;
            animation: coaFadeIn 0.2s ease;
        }

        @keyframes coaFadeIn {
            from {
                opacity: 0;
                transform: translateY(-4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Toggle button */
        .toggle-btn {
            background: none;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 6px;
            padding: 0;
            cursor: pointer;
            color: var(--coa-text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: background 0.2s ease, color 0.2s ease, transform 0.25s ease;
        }

        .toggle-btn:hover {
            background: var(--coa-primary-soft);
            color: var(--coa-primary);
        }

        .toggle-btn.rotated {
            transform: rotate(90deg);
        }

        /* Header action buttons */
        #createParentAccount,
        #createChildAccount {
            font-weight: 500;
            box-shadow: 0 2px 6px rgba(105, 108, 255, 0.25);
        }
    </style>
@endsection

@php
    use App\Enums\RoleNames;
@endphp
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Chart of Accounts (COA)</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                @if (RoleNames::SUPERADMIN == getRoleName())
                    <div>
                        <label class="form-label mb-1">Business</label>
                        <select id="coa_business_filter" class="form-select">
                            <option value="" {{ empty($selected_business_id) ? 'selected' : '' }}>
                                System Template (Global)
                            </option>
                            @foreach ($business as $item)
                                <option value="{{ $item->business_id }}"
                                    {{ $selected_business_id == $item->business_id ? 'selected' : '' }}>
                                    {{ isset($item->code) ? $item->code : '' }} {{ $item->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <!-- Action Buttons -->
                    <a href="javascript:void(0)" id="createParentAccount" class="btn rounded-pill btn-primary me-2">
                        <i class="icon-base fa fa-plus mr-5"></i> Add Parent
                    </a>
                    <a href="javascript:void(0)" id="createChildAccount" class="btn rounded-pill btn-primary">
                        <i class="icon-base fa fa-plus mr-5"></i> Add Child
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- COA Sections -->
                <div class="coa-container">
                    @foreach ($data as $type)
                        <div class="coa-section">
                            <!-- Section Header -->
                            <div class="coa-section-header" data-section="{{ $type->account_type_id }}">
                                <div class="d-flex align-items-center">
                                    <span class="section-title d-flex align-items-center gap-2">

                                        <i class="fa fa-folder-open text-primary"></i>

                                        <span>{{ $type->code }} - {{ $type->name }}</span>

                                        <span class="badge bg-primary">
                                            {{ $type->accountSubTypes->count() }}
                                        </span>

                                        <a href="#" class="action-btn action-btn-ledger" title="Ledger">
                                            <i class="fa fa-book"></i>
                                        </a>

                                    </span>
                                </div>
                                <div>
                                    <small class="text-success me-3">Balance: 0.00 DR</small>
                                    <i class="fa fa-chevron-right expand-icon"
                                        id="sectionIcon{{ $type->account_type_id }}"></i>
                                </div>
                            </div>

                            <!-- Section Content -->
                            <div class="coa-section-content" id="sectionContent{{ $type->account_type_id }}">
                                @foreach ($type->accountSubTypes as $subType)
                                    <!-- Sub Type Header -->
                                    <div class="tree-node">

                                        <div class="account-item level-0"
                                            data-account="{{ $subType->account_sub_type_id }}">

                                            <div class="account-info">

                                                @php
                                                    $hasAccounts = $subType->accounts->count() > 0;
                                                @endphp

                                                @if ($hasAccounts)
                                                    <button class="toggle-btn"
                                                        data-target="subType{{ $subType->account_sub_type_id }}">
                                                        <i class="fa fa-chevron-right toggle-icon"></i>
                                                    </button>
                                                @else
                                                    <span class="toggle-placeholder" style="width:28px;"></span>
                                                @endif

                                                <div class="account-icon account-icon-folder">
                                                    <i class="fa fa-folder"></i>
                                                </div>

                                                <div>
                                                    <span class="account-name">
                                                        {{ $subType->code }} - {{ $subType->name }}
                                                    </span>
                                                </div>

                                                <span class="account-balance">
                                                    0.00 DR
                                                </span>

                                            </div>

                                            <div class="account-actions">

                                                <button class="action-btn action-btn-ledger">
                                                    <i class="fa fa-book"></i>
                                                </button>

                                            </div>

                                        </div>

                                        @if ($hasAccounts)
                                            <div class="child-accounts"
                                                id="subType{{ $subType->account_sub_type_id }}">

                                                @include('admin.account.model.tree', [
                                                    'accounts' => $subType->accounts,
                                                    'level' => 1,
                                                ])

                                            </div>
                                        @endif

                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        @include('admin.account.model.parent')
        @include('admin.account.model.child')
    </div>
@endsection
@section('js')
    <script src="{{ asset('public/assets/js/admin/account.js') }}"></script>
    <script>
        (function() {
        //================= BUSINESS FILTER =================//

        const coaBusinessFilter = document.getElementById("coa_business_filter");

        if (coaBusinessFilter) {
            coaBusinessFilter.addEventListener("change", function() {
                const businessId = this.value;
                window.location.href = businessId ?
                    "{{ url('admin/account') }}?business_id=" + encodeURIComponent(businessId) :
                    "{{ url('admin/account') }}";
            });
        }

        //================= SECTION =================//

        let sections = document.querySelectorAll(".coa-section");

        sections.forEach(function(section, index) {

            const header = section.querySelector(".coa-section-header");
            const body = section.querySelector(".coa-section-content");
            const icon = section.querySelector(".expand-icon");

            if (index == 0) {

                body.style.display = "block";
                icon.classList.add("expanded");

            }

            header.onclick = function() {

                sections.forEach(function(sec) {

                    sec.querySelector(".coa-section-content").style.display = "none";
                    sec.querySelector(".expand-icon").classList.remove("expanded");

                });

                body.style.display = "block";
                icon.classList.add("expanded");

            }

        });

        //================ TREE =================//
        // Each toggle only opens/closes its own target node. Sibling branches
        // are left untouched so expanding one parent never collapses another.

        document.addEventListener("click", function(e) {

            const btn = e.target.closest(".toggle-btn");

            if (!btn) return;

            e.stopPropagation();

            const target = document.getElementById(btn.dataset.target);

            if (!target) return;

            if (target.classList.contains("open")) {

                closeNode(target);

            } else {

                openNode(target);

            }

        });

        function openNode(node) {

            node.classList.add("open");

            const btn = document.querySelector(
                '.toggle-btn[data-target="' + node.id + '"]'
            );

            if (btn) {
                btn.classList.add('rotated');
            }

        }

        function closeNode(node) {

            node.querySelectorAll(".child-accounts.open").forEach(function(child) {
                closeNode(child);
            });

            node.classList.remove("open");

            const btn = document.querySelector(
                '.toggle-btn[data-target="' + node.id + '"]'
            );

            if (btn) {
                btn.classList.remove('rotated');
            }

        }

        //=========== FIRST SUBTYPE OPEN ============//

        window.onload = function() {

            const first = document.querySelector(
                ".coa-section-content .child-accounts"
            );

            if (first) {

                openNode(first);

            }

        };
        })();
    </script>
@endsection
