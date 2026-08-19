<?php

namespace App\Support\Recurring\Generators;

use App\Enums\RecurringTransactionType;
use InvalidArgumentException;

/**
 * Maps transaction_type => generator implementation. Adding a future
 * recurring type (sales invoice, purchase bill, payment, subscription
 * billing) is one array entry + one class here, nothing else in the engine
 * changes.
 */
class RecurringGeneratorRegistry
{
    protected static array $map = [
        RecurringTransactionType::EXPENSE       => ExpenseRecurringGenerator::class,
        RecurringTransactionType::JOURNAL_ENTRY => JournalEntryRecurringGenerator::class,
    ];

    public function resolve(string $transactionType): RecurringTransactionGenerator
    {
        if (!isset(self::$map[$transactionType])) {
            throw new InvalidArgumentException("No recurring generator registered for type [{$transactionType}].");
        }

        return app(self::$map[$transactionType]);
    }
}
