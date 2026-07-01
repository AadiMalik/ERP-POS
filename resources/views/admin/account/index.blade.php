@extends('layouts.app')
@section('css')
    <style>
        /* Main Container Styles */
        .coa-container {
            padding: 20px 0;
        }

        /* Section Styles */
        .coa-section {
            margin-bottom: 30px;
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
                    <a href="javascript:void(0)" id="createAccount" class="btn rounded-pill btn-primary">
                        <i class="icon-base fa fa-plus mr-5"></i> Add Account
                    </a>
            </div>
            <div class="card-body">
                <!-- COA Sections -->
                <div class="coa-container">
                    @foreach ($data as $type)
                        <div class="coa-section">
                            <!-- Section Header -->
                            <div class="coa-section-header" data-section="{{ $type->account_type_id }}">
                                <div>
                                    <span class="section-title">
                                        <i class="fa fa-folder-open me-2 text-primary"></i>
                                        {{ $type->code }} - {{ $type->name }}
                                    </span>
                                    <span class="section-badge ms-2">{{ $type->accountSubTypes->count() }} Sub-types</span>

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
                                    <div class="account-item level-0" data-account="{{ $subType->id }}">
                                        <div class="account-info">
                                            <button class="toggle-btn" data-target="subType{{ $subType->id }}">
                                                <i class="fa fa-chevron-right toggle-icon"></i>
                                            </button>
                                            <div class="account-icon account-icon-folder">
                                                <i class="fa fa-folder"></i>
                                            </div>
                                            <div>
                                                <span class="account-name">{{ $subType->code }} -
                                                    {{ $subType->name }}</span>
                                            </div>
                                            <span class="account-balance">0.00 DR</span>
                                        </div>
                                        <div class="account-actions">
                                            <button class="action-btn action-btn-ledger" title="View Ledger">
                                                <i class="fa fa-book"></i>
                                            </button>
                                            <button class="action-btn action-btn-edit" title="Edit Account">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <button class="action-btn action-btn-delete" title="Delete Account">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Child Accounts -->
                                    <div class="child-accounts" id="subType{{ $subType->id }}">
                                        @include('admin.account.model.tree', [
                                            'accounts' => $subType->accounts,
                                            'level' => 1,
                                        ])
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Functionality 1: Toggle child accounts (one open, others closed)
            const toggleButtons = document.querySelectorAll('.toggle-btn');

            toggleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.stopPropagation();

                    const targetId = this.dataset.target;
                    const targetElement = document.getElementById(targetId);
                    const icon = this.querySelector('.toggle-icon');

                    if (!targetElement) return;

                    // Close all other child containers in the same section
                    const section = this.closest('.coa-section');
                    if (section) {
                        const allChildContainers = section.querySelectorAll('.child-accounts');
                        allChildContainers.forEach(container => {
                            if (container.id !== targetId) {
                                container.classList.remove('open');
                                // Reset toggle icons
                                const siblingToggle = container.parentElement.querySelector(
                                    '.toggle-btn');
                                if (siblingToggle) {
                                    const siblingIcon = siblingToggle.querySelector(
                                        '.toggle-icon');
                                    if (siblingIcon) {
                                        siblingIcon.className =
                                            'fa fa-chevron-right toggle-icon';
                                    }
                                }
                            }
                        });
                    }

                    // Toggle the target
                    targetElement.classList.toggle('open');

                    // Update icon
                    if (targetElement.classList.contains('open')) {
                        icon.className = 'fa fa-chevron-down toggle-icon';
                    } else {
                        icon.className = 'fa fa-chevron-right toggle-icon';
                    }
                });
            });

            // Functionality 2: Section toggle (expand/collapse with icons)
            const sectionHeaders = document.querySelectorAll('.coa-section-header');

            sectionHeaders.forEach(header => {
                header.addEventListener('click', function() {
                    const sectionId = this.dataset.section;
                    const content = document.getElementById('sectionContent' + sectionId);
                    const icon = document.getElementById('sectionIcon' + sectionId);

                    if (!content) return;

                    // Toggle section content visibility
                    if (content.style.display === 'none') {
                        content.style.display = 'block';
                        icon.className = 'fa fa-chevron-down expand-icon';
                    } else {
                        content.style.display = 'none';
                        icon.className = 'fa fa-chevron-right expand-icon';
                    }
                });
            });

            // Open first section by default
            const firstSection = document.querySelector('.coa-section-content');
            if (firstSection) {
                firstSection.style.display = 'block';
            }
        });
    </script>
@endsection
