<?php

namespace App\Support\Recurring\TemplateData;

use App\Enums\RecurringTransactionType;
use InvalidArgumentException;

/**
 * Maps transaction_type => template validator. Extension point for future
 * recurring types alongside App\Support\Recurring\Generators\RecurringGeneratorRegistry.
 */
class RecurringTemplateValidatorRegistry
{
    protected static array $map = [
        RecurringTransactionType::EXPENSE       => ExpenseTemplateValidator::class,
        RecurringTransactionType::JOURNAL_ENTRY => JournalEntryTemplateValidator::class,
    ];

    public static function validate(string $transactionType, array $data): array
    {
        if (!isset(self::$map[$transactionType])) {
            throw new InvalidArgumentException("No template validator registered for recurring transaction type [{$transactionType}].");
        }

        return call_user_func([self::$map[$transactionType], 'validate'], $data);
    }
}
