<?php

namespace App\Enums;

class  RoleNames
{
    //Admin side
    const SUPERADMIN = 'Super Admin';
    const BUSINESSADMIN = 'Business Admin';
    const BRANCHADMIN = 'Branch Admin';
    const GENERALMANAGER = 'General Manager';
    const OPERATIONMANAGER = 'Operation Manager';
    const INVENTORYMANAGER = 'Inventory Manager';
    const FINANCEMANAGER = 'Finance Manager';
    const SALEMANAGER = 'Sale Manager';
    const PURCHASEMANAGER = 'Purchase Manager';
    const MARKITINGMANAGER = 'Marketing Manager';
    const ACCOUNTANT = 'Accountant';
    const HRMANAGER = 'HR Manager';
    const REPORTINGANALYST = 'Reporting Analyst';
    const STAFF = 'Staff';
    const EMPLOYEE = 'Employee';
    //POS
    const ORDERTAKER = 'Order Taker';
    const POSMANAGER = 'POS Manager';

    //website
    const USER = 'User';

    public static function businessLevelRoles()
    {
        return [
            self::GENERALMANAGER,
            self::OPERATIONMANAGER,
            self::INVENTORYMANAGER,
            self::FINANCEMANAGER,
            self::SALEMANAGER,
            self::PURCHASEMANAGER,
            self::MARKITINGMANAGER,
            self::ACCOUNTANT,
            self::HRMANAGER,
            self::REPORTINGANALYST,
        ];
    }

    public static function branchLevelRoles()
    {
        return [
            self::BRANCHADMIN,
            self::STAFF,
            self::POSMANAGER,
            self::ORDERTAKER,
        ];
    }
}
