@foreach ($accounts as $account)
    @php
        $hasChild = $account->childAccounts->count() > 0;
        $levelClass = 'level-' . $level;
    @endphp

    <div class="account-item {{ $levelClass }}" data-account="{{ $account->id }}">
        <div class="account-info">
            @if ($hasChild)
                <button class="toggle-btn" data-target="child{{ $account->id }}">
                    <i class="fa fa-chevron-right toggle-icon"></i>
                </button>
            @else
                <span class="toggle-placeholder" style="width: 28px;"></span>
            @endif

            <div class="account-icon {{ $hasChild ? 'account-icon-folder' : 'account-icon-file' }}">
                <i class="fa {{ $hasChild ? 'fa-folder' : 'fa-file-lines' }}"></i>
            </div>

            <div>
                <span class="account-name">{{ $account->code }} - {{ $account->name }}</span>
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

    @if ($hasChild)
        <div class="child-accounts" id="child{{ $account->id }}">
            @include('admin.account.model.tree', [
                'accounts' => $account->childAccounts,
                'level' => $level + 1,
            ])
        </div>
    @endif
@endforeach
