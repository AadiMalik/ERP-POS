<?php

namespace App\Support\Recurring\TemplateData;

use Exception;
use Illuminate\Support\Facades\Validator;

/**
 * Validates the `template_data` payload for transaction_type = journal_entry.
 * Field shape mirrors JournalEntryController::store()'s `details[]` rows
 * (App\Http\Controllers\Admin\JournalEntryController::store()), including the
 * debit=credit balance check that controller already enforces manually -
 * JournalEntryService::save() itself does not validate balance, so this is
 * the only safety net for a system-generated (unattended) entry.
 */
class JournalEntryTemplateValidator
{
    public static function validate(array $data): array
    {
        $validator = Validator::make($data, [
            'journal_id'             => ['required', 'exists:journals,journal_id'],
            'reference_no'           => ['nullable', 'string', 'max:100'],
            'description'            => ['nullable', 'string'],
            'lines'                  => ['required', 'array', 'min:2'],
            'lines.*.account_id'     => ['required', 'exists:accounts,account_id'],
            'lines.*.debit'          => ['required', 'numeric', 'min:0'],
            'lines.*.credit'         => ['required', 'numeric', 'min:0'],
            'lines.*.description'    => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            throw new Exception($validator->errors()->first());
        }

        $validated = $validator->validated();

        self::assertBalanced($validated['lines']);

        return $validated;
    }

    /**
     * sum(debit) === sum(credit), at least 2 lines, and no line with both or
     * neither debit/credit set. Shared by save-time validation above and
     * run-time validation in JournalEntryRecurringGenerator.
     */
    public static function assertBalanced(array $lines): void
    {
        $total_debit = 0;
        $total_credit = 0;

        foreach ($lines as $index => $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($debit == 0 && $credit == 0) {
                throw new Exception('Either Debit or Credit is required on row ' . ($index + 1) . '.');
            }

            if ($debit > 0 && $credit > 0) {
                throw new Exception('Both Debit and Credit cannot have values on row ' . ($index + 1) . '.');
            }

            $total_debit += $debit;
            $total_credit += $credit;
        }

        if (round($total_debit, 2) !== round($total_credit, 2)) {
            throw new Exception('Total Debit and Credit must be equal.');
        }
    }
}
