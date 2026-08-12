<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ProfitLossExport implements FromArray, ShouldAutoSize
{
    public function __construct(protected array $result)
    {
    }

    public function array(): array
    {
        $rows = [
            ['Profit & Loss Statement'],
            [],
            ['Revenue / Income'],
        ];

        foreach ($this->result['revenue'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Revenue', decimal($this->result['total_revenue'])];
        $rows[] = [];

        $rows[] = ['Cost of Revenue'];
        foreach ($this->result['cost_of_revenue'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Cost of Revenue', decimal($this->result['total_cost_of_revenue'])];
        $rows[] = ['Gross Profit', decimal($this->result['gross_profit'])];
        $rows[] = [];

        $rows[] = ['Direct Expenses'];
        foreach ($this->result['direct_expense'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Direct Expenses', decimal($this->result['total_direct_expense'])];
        $rows[] = [];

        $rows[] = ['Operating Expenses'];
        foreach ($this->result['operating_expense'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Operating Expenses', decimal($this->result['total_operating_expense'])];
        $rows[] = ['Operating Profit', decimal($this->result['operating_profit'])];
        $rows[] = [];

        $rows[] = ['Other Income'];
        foreach ($this->result['other_income'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Other Income', decimal($this->result['total_other_income'])];
        $rows[] = [];

        $rows[] = ['Other Expenses'];
        foreach ($this->result['other_expense'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Other Expenses', decimal($this->result['total_other_expense'])];
        $rows[] = [];

        $rows[] = ['Net Profit / (Loss)', decimal($this->result['net_profit'])];

        return $rows;
    }
}
