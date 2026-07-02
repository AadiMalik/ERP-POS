@extends('layouts.app')
@section('css')
    <style>
        /* Main Container Styles */
        .coa-container {
            padding: 20px 0;
        }

        /* Section Styles */
        .coa-section {
            margin-bottom: 10px;
            border: 1px solid #e1e5eb;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .coa-section-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e1e5eb;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .coa-section-header:hover {
            background: #f0f2f5;
        }

        .coa-section-header .section-title {
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }

        .coa-section-header .section-badge {
            background: #6c757d;
            color: white;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 12px;
        }

        .coa-section-content {
            padding: 0;
            background: #fff;
        }

        /* Account Item Styles */
        .account-item {
            position: relative;
            padding: 12px 20px;
            border-bottom: 1px solid #f0f2f5;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .account-item:last-child {
            border-bottom: none;
        }

        .account-item:hover {
            background: #f8fbff;
        }

        /* Level-based indentation */
        .level-0 {
            padding-left: 30px;
            font-weight: 600;
            background: #fafbfc;
        }

        .level-1 {
            padding-left: 60px;
        }

        .level-2 {
            padding-left: 90px;
        }

        .level-3 {
            padding-left: 120px;
        }

        .level-4 {
            padding-left: 150px;
        }

        /* Tree line indicators */
        .level-1::before,
        .level-2::before,
        .level-3::before,
        .level-4::before {
            content: '';
            position: absolute;
            left: 40px;
            top: 0;
            bottom: 0;
            width: 1px;
            background: #d8dde3;
        }

        .level-2::before {
            left: 70px;
        }

        .level-3::before {
            left: 100px;
        }

        .level-4::before {
            left: 130px;
        }

        /* Action Buttons Container - Hidden by default */
        .account-actions {
            display: none;
            gap: 8px;
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
            border-radius: 6px;
            border: 1px solid transparent;
            background: transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .action-btn-edit:hover {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }

        .action-btn-delete:hover {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }

        .action-btn-ledger:hover {
            background: #cce5ff;
            color: #004085;
            border-color: #007bff;
        }

        /* Account Info Styles */
        .account-info {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }

        .account-icon {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .account-icon-folder {
            background: #fff3cd;
            color: #856404;
        }

        .account-icon-file {
            background: #e9ecef;
            color: #495057;
        }

        .account-name {
            font-weight: 500;
            color: #2c3e50;
        }

        .account-code {
            color: #6c757d;
            font-size: 13px;
            margin-left: 8px;
        }

        .account-balance {
            font-size: 13px;
            color: #28a745;
            margin-left: 15px;
        }

        /* Arrow indicator for expandable sections */
        .expand-icon {
            transition: transform 0.3s ease;
            margin-right: 8px;
            color: #6c757d;
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
        }

        /* Toggle button */
        .toggle-btn {
            background: none;
            border: none;
            padding: 0 8px;
            cursor: pointer;
            color: #6c757d;
            transition: transform 0.3s ease;
        }

        .toggle-btn:hover {
            color: #495057;
        }

        .toggle-btn.rotated {
            transform: rotate(90deg);
        }

        .coa-section-content {
            display: none;
        }

        .child-accounts {
            display: none;
        }

        .child-accounts.open {
            display: block;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Chart of Accounts (COA)</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <!-- Action Buttons -->
                <a href="javascript:void(0)" id="createParentAccount" class="btn rounded-pill btn-primary me-2">
                    <i class="icon-base fa fa-plus mr-5"></i> Add Parent
                </a>
                <a href="javascript:void(0)" id="createChildAccount" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i> Add Child
                </a>
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
                                    <i class="fa fa-chevron-down expand-icon"
                                        id="sectionIcon{{ $type->account_type_id }}"></i>
                                </div>
                            </div>

                            <!-- Section Content -->
                            <div class="coa-section-content" id="sectionContent{{ $type->account_type_id }}">
                                @foreach ($type->accountSubTypes as $subType)
                                    <!-- Sub Type Header -->
                                    <div class="tree-node">

                                        <div class="account-item level-0" data-account="{{ $subType->id }}">

                                            <div class="account-info">

                                                @php
                                                    $hasAccounts = $subType->accounts->count() > 0;
                                                @endphp

                                                @if ($hasAccounts)
                                                    <button class="toggle-btn" data-target="subType{{ $subType->id }}">
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
                                            <div class="child-accounts" id="subType{{ $subType->id }}">

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
        //================= SECTION =================//

        let sections = document.querySelectorAll(".coa-section");

        sections.forEach(function(section, index) {

            const header = section.querySelector(".coa-section-header");
            const body = section.querySelector(".coa-section-content");
            const icon = section.querySelector(".expand-icon");

            if (index == 0) {

                body.style.display = "block";
                icon.className = "fa fa-chevron-down expand-icon";

            }

            header.onclick = function() {

                sections.forEach(function(sec) {

                    sec.querySelector(".coa-section-content").style.display = "none";
                    sec.querySelector(".expand-icon").className = "fa fa-chevron-right expand-icon";

                });

                body.style.display = "block";
                icon.className = "fa fa-chevron-down expand-icon";

            }

        });

        //================ TREE =================//

        document.addEventListener("click", function(e) {

            const btn = e.target.closest(".toggle-btn");

            if (!btn) return;

            e.stopPropagation();

            const target = document.getElementById(btn.dataset.target);

            const treeNode = btn.closest(".tree-node");

            const wrapper = treeNode.parentElement;

            wrapper.querySelectorAll(":scope > .tree-node > .child-accounts.open")
                .forEach(function(item) {

                    if (item != target) {

                        closeNode(item);

                    }

                });

            if (target.classList.contains("open")) {

                closeNode(target);

            } else {

                openNode(target);

            }

        });

        function openNode(node) {

            node.classList.add("open");

            const icon = document.querySelector(
                '.toggle-btn[data-target="' + node.id + '"] .toggle-icon'
            );

            if (icon) {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-down');
            }

        }

        function closeNode(node) {

            node.querySelectorAll(".child-accounts.open").forEach(function(child) {
                closeNode(child);
            });

            node.classList.remove("open");

            const icon = document.querySelector(
                '.toggle-btn[data-target="' + node.id + '"] .toggle-icon'
            );

            if (icon) {
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-right');
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
    </script>
@endsection
