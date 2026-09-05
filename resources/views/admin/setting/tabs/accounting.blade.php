@php
    $coa = [
        'default_cash_account_id' => __('settings.coa_cash_account'),
        'default_bank_account_id' => __('settings.coa_bank_account'),
        'default_discount_account_id' => __('settings.coa_discount_account'),
        'default_loyalty_discount_account_id' => __('settings.coa_loyalty_discount_account'),
        'default_tax_account_id' => __('settings.coa_tax_account'),
        'default_revenue_account_id' => __('settings.coa_revenue_account'),
        'default_purchase_account_id' => __('settings.coa_purchase_account'),
        'default_expense_account_id' => __('settings.coa_expense_account'),
        'default_supplier_account_id' => __('settings.coa_supplier_account'),
        'default_customer_account_id' => __('settings.coa_customer_account'),
        'default_store_credit_account_id' => __('settings.coa_store_credit_account'),
        'default_carriage_account_id' => __('settings.coa_carriage_account'),
        'default_round_off_account_id' => __('settings.coa_round_off_account'),
        'default_purchase_return_account_id' => __('settings.coa_purchase_return'),
        'default_service_purchase_account_id' => __('settings.coa_service_purchase_account'),
        'default_service_purchase_return_account_id' => __('settings.coa_service_purchase_return'),
        'default_service_sale_account_id' => __('settings.coa_service_sale_account'),
        'default_service_sale_return_account_id' => __('settings.coa_service_sale_return'),
        'default_sale_account_id' => __('settings.coa_sale_account'),
        'default_sale_return_account_id' => __('settings.coa_sale_return'),
        'default_inventory_account_id' => __('settings.coa_inventory_account'),
        'default_cogs_account_id' => __('settings.coa_cogs_account'),
        'default_opening_stock_account_id' => __('settings.coa_opening_stock_account'),
        'default_stock_adjustment_account_id' => __('settings.coa_stock_adjustment_account'),
        'default_withholding_tax_account_id' => __('settings.coa_withholding_tax_account'),
        'default_fixed_asset_account_id' => __('settings.coa_fixed_asset_account'),
        'default_accumulated_depreciation_account_id' => __('settings.coa_accumulated_depreciation_account'),
        'default_depreciation_expense_account_id' => __('settings.coa_depreciation_expense_account'),
        'default_gain_on_asset_disposal_account_id' => __('settings.coa_gain_on_asset_disposal_account'),
        'default_loss_on_asset_disposal_account_id' => __('settings.coa_loss_on_asset_disposal_account'),
    ];
    $fiscal_months = [
        1 => __('settings.month_january'),
        2 => __('settings.month_february'),
        3 => __('settings.month_march'),
        4 => __('settings.month_april'),
        5 => __('settings.month_may'),
        6 => __('settings.month_june'),
        7 => __('settings.month_july'),
        8 => __('settings.month_august'),
        9 => __('settings.month_september'),
        10 => __('settings.month_october'),
        11 => __('settings.month_november'),
        12 => __('settings.month_december'),
    ];
@endphp
<form id="accountingSettingForm">
    @csrf
    <div class="row">
        <div class="col-md-12">
            <h4>{{ __('settings.accounting_title') }}</h4>
            <hr>
        </div>
        @foreach ($coa as $field => $label)
            <div class="col-md-6 mb-3">
                <label>{{ $label }}</label>
                <select class="form-select select2" name="{{ $field }}">
                    <option value="">{{ __('settings.select_account') }}</option>
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
            <label>{{ __('settings.currency') }}</label>
            <input class="form-control" name="currency" value="{{ $accounting_setting->currency }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.currency_symbol') }}</label>
            <input class="form-control" name="currency_symbol" value="{{ $accounting_setting->currency_symbol }}">
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.currency_position') }}</label>
            <select class="form-select" name="currency_position">
                <option value="before" {{ $accounting_setting->currency_position == 'before' ? 'selected' : '' }}>{{ __('settings.currency_position_before') }}</option>
                <option value="after" {{ $accounting_setting->currency_position == 'after' ? 'selected' : '' }}>{{ __('settings.currency_position_after') }}</option>
            </select>
        </div>
        <div class="col-md-3 mb-3">
            <label>{{ __('settings.decimal_points') }}</label>
            <input type="number" class="form-control" name="decimal_points"
                value="{{ $accounting_setting->decimal_points }}">
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.supplier_payment_account_selection') }}</label>
            <select class="form-select" name="manual_payment_account_selection">
                <option value="0" {{ !$accounting_setting->manual_payment_account_selection ? 'selected' : '' }}>
                    {{ __('settings.payment_account_automatic') }}
                </option>
                <option value="1" {{ $accounting_setting->manual_payment_account_selection ? 'selected' : '' }}>
                    {{ __('settings.payment_account_manual') }}
                </option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.supplier_aging_basis') }}</label>
            <select class="form-select" name="aging_basis">
                <option value="due_date" {{ $accounting_setting->aging_basis == 'due_date' ? 'selected' : '' }}>
                    {{ __('settings.aging_basis_due_date') }}
                </option>
                <option value="invoice_date" {{ $accounting_setting->aging_basis == 'invoice_date' ? 'selected' : '' }}>
                    {{ __('settings.aging_basis_invoice_date') }}
                </option>
            </select>
        </div>
        <div class="col-md-12">
            <hr>
            <h4>{{ __('settings.accounting_automation_title') }}</h4>
            <p class="text-muted">
                {{ __('settings.accounting_automation_help') }}
            </p>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.period_closing_mode') }}</label>
            <select class="form-select" name="period_closing_mode">
                <option value="manual" {{ $accounting_setting->period_closing_mode == 'manual' ? 'selected' : '' }}>
                    {{ __('settings.period_closing_manual') }}
                </option>
                <option value="monthly" {{ $accounting_setting->period_closing_mode == 'monthly' ? 'selected' : '' }}>
                    {{ __('settings.period_closing_monthly') }}
                </option>
                <option value="yearly" {{ $accounting_setting->period_closing_mode == 'yearly' ? 'selected' : '' }}>
                    {{ __('settings.period_closing_yearly') }}
                </option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.fiscal_year_start_month') }}</label>
            <select class="form-select" name="fiscal_year_start_month">
                @foreach ($fiscal_months as $monthNum => $monthLabel)
                    <option value="{{ $monthNum }}" {{ (int) $accounting_setting->fiscal_year_start_month === $monthNum ? 'selected' : '' }}>
                        {{ $monthLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.budgeting') }}</label>
            <select class="form-select" name="budgeting_mode" id="budgetingModeSelect">
                <option value="manual" {{ $accounting_setting->budgeting_mode == 'manual' ? 'selected' : '' }}>
                    {{ __('settings.budgeting_manual') }}
                </option>
                <option value="auto" {{ $accounting_setting->budgeting_mode == 'auto' ? 'selected' : '' }}>
                    {{ __('settings.budgeting_auto') }}
                </option>
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.budget_growth_percent') }}</label>
            <input type="number" step="0.01" class="form-control" name="budget_growth_percent"
                value="{{ $accounting_setting->budget_growth_percent }}">
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.advanced_accounting_mode') }}</label>
            <select class="form-select" name="advanced_accounting_mode">
                <option value="0" {{ !$accounting_setting->advanced_accounting_mode ? 'selected' : '' }}>
                    {{ __('settings.advanced_accounting_off') }}
                </option>
                <option value="1" {{ $accounting_setting->advanced_accounting_mode ? 'selected' : '' }}>
                    {{ __('settings.advanced_accounting_on') }}
                </option>
            </select>
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#accountingSettingForm','{{ url('admin/setting/accounting') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>
