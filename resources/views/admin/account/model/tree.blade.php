@foreach ($accounts as $account)
    @php
        $hasChild = $account->childAccounts->count() > 0;
        $levelClass = 'level-' . $level;
    @endphp
    <div class="tree-node">
        <div class="account-item {{ $levelClass }}" data-account="{{ $account->account_id }}">
            <div class="account-info">
                @if ($hasChild)
                    <button class="toggle-btn" data-target="child{{ $account->account_id }}">
                        <i class="fa fa-chevron-right toggle-icon"></i>
                    </button>
                @else
                    <span class="toggle-placeholder" style="width:28px;"></span>
                @endif
                <div class="account-icon {{ $hasChild ? 'account-icon-folder' : 'account-icon-file' }}">
                    <i class="fa {{ $hasChild ? 'fa-folder' : 'fa-file-lines' }}"></i>
                </div>
                <div>
                    <span class="account-name">
                        {{ $account->code }} - {{ $account->name }}
                    </span>
                </div>
                <span class="account-balance">0.00 DR</span>
            </div>
            <div class="account-actions">
                @if ($account->parent_account_id != null)
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input statusAccount" type="checkbox"
                            data-id="{{ $account->account_id }}" {{ $account->status == 'active' ? 'checked' : '' }}>
                    </div>
                @endif
                <a href="#" class="action-btn action-btn-ledger">
                    <i class="fa fa-book"></i>
                </a>
                <button class="action-btn action-btn-edit"
                    @if ($account->parent_account_id == null) id="editParentAccount" @else  id="editChildAccount" @endif
                    data-id="{{ $account->account_id }}">
                    <i class="fa fa-edit"></i>
                </button>
                <button class="action-btn action-btn-delete"
                    @if ($account->parent_account_id == null) id="deleteParentAccount" @else  id="deleteChildAccount" @endif
                    data-id="{{ $account->account_id }}">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
        </div>
        @if ($hasChild)
            <div class="child-accounts" id="child{{ $account->account_id }}">
                @include('admin.account.model.tree', [
                    'accounts' => $account->childAccounts,
                    'level' => $level + 1,
                ])
            </div>
        @endif
    </div>
@endforeach
