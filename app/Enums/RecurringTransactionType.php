<?php

namespace App\Enums;

/**
 * Single extension point for the recurring-transaction engine's generator
 * registry (App\Support\Recurring\Generators\RecurringGeneratorRegistry) and
 * template-validator registry. Adding a new recurring type in a future phase
 * (sales invoice, purchase bill, payment, subscription billing) means adding
 * a constant here plus one registry entry - not a schema change.
 */
class RecurringTransactionType
{
    const EXPENSE = 'expense';
    const JOURNAL_ENTRY = 'journal_entry';

    public static function all(): array
    {
        return [
            self::EXPENSE,
            self::JOURNAL_ENTRY,
        ];
    }

    public static function labels(): array
    {
        return [
            self::EXPENSE       => 'Expense',
            self::JOURNAL_ENTRY => 'Journal Entry',
        ];
    }
}
