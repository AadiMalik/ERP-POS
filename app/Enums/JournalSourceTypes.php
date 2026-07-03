<?php

namespace App\Enums;

class JournalSourceTypes
{
      const MANUAL = 'Manual';

      // Sales
      const SALE = 'Sale';
      const SALE_RETURN = 'Sale Return';
      const SALE_ORDER = 'Sale Order';
      const SALE_QUOTATION = 'Sale Quotation';
      const POS_SALE = 'POS Sale';
      const POS_RETURN = 'POS Return';
      const DELIVERY = 'Delivery';

      // Purchase
      const PURCHASE = 'Purchase';
      const PURCHASE_RETURN = 'Purchase Return';
      const PURCHASE_ORDER = 'Purchase Order';
      const PURCHASE_QUOTATION = 'Purchase Quotation';
      const GOODS_RECEIPT = 'Goods Receipt';

      // Inventory
      const STOCK_ADJUSTMENT = 'Stock Adjustment';
      const STOCK_TRANSFER = 'Stock Transfer';
      const OPENING_STOCK = 'Opening Stock';
      const INVENTORY_COUNT = 'Inventory Count';
      const PRODUCTION = 'Production';
      const PRODUCTION_CONSUMPTION = 'Production Consumption';
      const PRODUCTION_FINISHED_GOODS = 'Production Finished Goods';

      // Accounting
      const JOURNAL_VOUCHER = 'Journal Voucher';
      const PAYMENT_VOUCHER = 'Payment Voucher';
      const RECEIPT_VOUCHER = 'Receipt Voucher';
      const CONTRA_VOUCHER = 'Contra Voucher';

      const CUSTOMER_PAYMENT = 'Customer Payment';
      const SUPPLIER_PAYMENT = 'Supplier Payment';
      const CUSTOMER_RECEIPT = 'Customer Receipt';
      const SUPPLIER_RECEIPT = 'Supplier Receipt';

      const OPENING_BALANCE = 'Opening Balance';
      const CLOSING_BALANCE = 'Closing Balance';
      const YEAR_END_CLOSING = 'Year End Closing';

      const EXPENSE = 'Expense';
      const INCOME = 'Income';

      const BANK_TRANSFER = 'Bank Transfer';
      const BANK_DEPOSIT = 'Bank Deposit';
      const BANK_WITHDRAWAL = 'Bank Withdrawal';
      const BANK_CHARGE = 'Bank Charge';
      const INTEREST = 'Interest';

      // Finance
      const LOAN = 'Loan';
      const LOAN_REPAYMENT = 'Loan Repayment';
      const LOAN_DISBURSEMENT = 'Loan Disbursement';

      // Assets
      const ASSET_ACQUISITION = 'Asset Acquisition';
      const ASSET_DEPRECIATION = 'Asset Depreciation';
      const ASSET_TRANSFER = 'Asset Transfer';
      const ASSET_DISPOSAL = 'Asset Disposal';

      // HR
      const PAYROLL = 'Payroll';
      const SALARY = 'Salary';
      const ADVANCE_SALARY = 'Advance Salary';
      const EMPLOYEE_LOAN = 'Employee Loan';
      const EMPLOYEE_LOAN_RECOVERY = 'Employee Loan Recovery';
      const BONUS = 'Bonus';
      const OVERTIME = 'Overtime';

      // CRM
      const CUSTOMER_ADVANCE = 'Customer Advance';
      const SUPPLIER_ADVANCE = 'Supplier Advance';
      const CUSTOMER_REFUND = 'Customer Refund';
      const SUPPLIER_REFUND = 'Supplier Refund';

      // Misc
      const ADJUSTMENT = 'Adjustment';
      const MIGRATION = 'Migration';
      const SYSTEM = 'System';
}
