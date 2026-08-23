<?php

namespace App\Enums;

class  AccountTypes
{
    const ASSETS = 'Assets';
    const LIABILITIES = 'Liabilities';
    const EQUITY = 'Equity';
    const REVENUE = 'Revenue';
    const EXPENSES = 'Expenses';

    /**
     * Stable classification codes, one per default Account Type - matches
     * the codes AccountTypeService::resetBusinessAccountType() assigns when
     * seeding/resetting a business's defaults. Reports/classification must
     * key off `account_types.code` (via these), never `account_types.name` -
     * name is a free-text label a Business Admin can rename at any time,
     * while code is the stable identifier that survives a rename.
     */
    const CODES = [
        self::ASSETS => '1000',
        self::LIABILITIES => '2000',
        self::EQUITY => '3000',
        self::REVENUE => '4000',
        self::EXPENSES => '5000',
    ];
}
