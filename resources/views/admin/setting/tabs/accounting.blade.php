@php
    $coa = [
        'default_cash_account_id' => 'Cash Account',
        'default_bank_account_id' => 'Bank Account',
        'default_discount_account_id' => 'Discount Account',
        'default_tax_account_id' => 'Tax Account',
        'default_revenue_account_id' => 'Revenue Account',
        'default_purchase_account_id' => 'Purchase Account',
        'default_expense_account_id' => 'Expense Account',
        'default_supplier_account_id' => 'Supplier Account',
        'default_customer_account_id' => 'Customer Account',
        'default_carriage_account_id' => 'Carriage Account',
        'default_round_off_account_id' => 'Round Off Account',
        'default_purchase_return_account_id' => 'Purchase Return',
        'default_sale_account_id' => 'Sale Account',
        'default_sale_return_account_id' => 'Sale Return',
        'default_inventory_account_id' => 'Inventory Account',
        'default_opening_stock_account_id' => 'Opening Stock Account',
        'default_stock_adjustment_account_id' => 'Stock Adjustment Account',
        'default_withholding_tax_account_id' => 'Withholding Tax Account',
    ];
@endphp
<form id="accountingSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>Accounting Setting</h4>
            <hr>
        </div>
        @foreach ($coa as $field => $label)
            <div class="col-md-6 mb-3">
                <label>{{ $label }}</label>
                <select class="form-select select2" name="{{ $field }}">
                    <option value="">--Select Account--</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->account_id }}"
                            {{ $accounting_setting->$field == $account->account_id ? 'selected' : '' }}>
                            {{ $account->code }} - {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endforeach
        <div class="col-md-3 mb-3">
            <label>Currency</label>
            <input class="form-control" name="currency" value="{{ $accounting_setting->currency }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Currency Symbol</label>
            <input class="form-control" name="currency_symbol" value="{{ $accounting_setting->currency_symbol }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>Currency Position</label>
            <select class="form-select" name="currency_position">
                <option value="before" {{ $accounting_setting->currency_position == 'before' ? 'selected' : '' }}>Before</option>
                <option value="after" {{ $accounting_setting->currency_position == 'after' ? 'selected' : '' }}>After</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label>Decimal Points</label>
            <input type="number" class="form-control" name="decimal_points"
                value="{{ $accounting_setting->decimal_points }}">
        </div>
        <div class="col-md-6 mb-3">
            <label>Supplier Payment Account Selection</label>
            <select class="form-select" name="manual_payment_account_selection">
                <option value="0" {{ !$accounting_setting->manual_payment_account_selection ? 'selected' : '' }}>
                    Automatic (use default Cash/Bank account)
                </option>
                <option value="1" {{ $accounting_setting->manual_payment_account_selection ? 'selected' : '' }}>
                    Manual (let user pick the Cash/Bank account)
                </option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>Supplier Aging Basis</label>
            <select class="form-select" name="aging_basis">
                <option value="due_date" {{ $accounting_setting->aging_basis == 'due_date' ? 'selected' : '' }}>
                    Due Date (Invoice Date + Supplier Credit Days)
                </option>
                <option value="invoice_date" {{ $accounting_setting->aging_basis == 'invoice_date' ? 'selected' : '' }}>
                    Invoice (GRN) Date
                </option>
            </select>
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#accountingSettingForm','{{ url('admin/setting/accounting') }}')">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</form>
