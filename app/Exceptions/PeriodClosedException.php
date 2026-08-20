<?php

namespace App\Exceptions;

use Exception;

/**
 * Thrown by AccountingPeriodService::assertPostable() when something tries
 * to create/post a JournalEntry dated inside a closed AccountingPeriod.
 * Extends the plain Exception every posting Service/Controller in this
 * codebase already catches (see ExpenseController/JournalEntryController's
 * uniform `catch (Exception $e) { return $this->error($e->getMessage()); }`),
 * so it surfaces through existing error handling with no controller changes.
 */
class PeriodClosedException extends Exception
{
}
