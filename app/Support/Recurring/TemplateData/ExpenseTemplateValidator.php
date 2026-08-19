<?php

namespace App\Support\Recurring\TemplateData;

use App\Enums\PaymentMethod;
use Exception;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Validates the `template_data` payload for transaction_type = expense,
 * mirroring the rule set ExpenseController::store() applies to a manually
 * created expense (App\Http\Controllers\Admin\ExpenseController::store()).
 * Used both when a recurring template is saved (RecurringTransactionService)
 * and, for the account references, again at generation time
 * (App\Support\Recurring\Generators\ExpenseRecurringGenerator).
 */
class ExpenseTemplateValidator
{
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'expense_category_id')->where('is_deleted', 0)],
            'payment_method'      => ['required', 'in:' . PaymentMethod::CASH . ',' . PaymentMethod::BANK_TRANSFER . ',' . PaymentMethod::CHEQUE . ',' . PaymentMethod::ONLINE],
            'payment_account_id'  => ['nullable', Rule::exists('accounts', 'account_id')->where('is_deleted', 0)],
            'reference_no'        => ['nullable', 'string', 'max:191'],
            'amount'              => ['required', 'numeric', 'min:0.01'],
            'description'         => ['nullable', 'string'],
            'user_id'             => ['nullable', Rule::exists('users', 'id')->where('is_deleted', 0)],
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        return $validator->validated();
    }
}
