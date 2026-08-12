<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class BalanceSheetExport implements FromArray, ShouldAutoSize
{
    public function __construct(protected array $result)
    {
    }

    public function array(): array
    {
        $rows = [
            ['Balance Sheet'],
            [],
            ['Assets'],
            ['Current Assets'],
        ];

        foreach ($this->result['current_assets'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Current Assets', decimal($this->result['total_current_assets'])];

        $rows[] = ['Fixed Assets'];
        foreach ($this->result['fixed_assets'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Fixed Assets', decimal($this->result['total_fixed_assets'])];

        $rows[] = ['Other Assets'];
        foreach ($this->result['other_assets'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Other Assets', decimal($this->result['total_other_assets'])];
        $rows[] = ['Total Assets', decimal($this->result['total_assets'])];
        $rows[] = [];

        $rows[] = ['Liabilities'];
        $rows[] = ['Current Liabilities'];
        foreach ($this->result['current_liabilities'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Current Liabilities', decimal($this->result['total_current_liabilities'])];

        $rows[] = ['Long-term Liabilities'];
        foreach ($this->result['long_term_liabilities'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Long-term Liabilities', decimal($this->result['total_long_term_liabilities'])];

        $rows[] = ['Other Liabilities'];
        foreach ($this->result['other_liabilities'] as $row) {
            $rows[] = [$row->account_code . ' ' . $row->account_name, decimal($row->amount)];
        }
        $rows[] = ['Total Other Liabilities', decimal($this->result['total_other_liabilities'])];
        $rows[] = ['Total Liabilities', decimal($this->result['total_liabilities'])];
        $rows[] = [];

        $rows[] = ['Equity'];
        foreach ($this->result['equity'] as $row) {
            $rows[] = [trim($row->account_code . ' ' . $row->account_name), decimal($row->amount)];
        }
        $rows[] = ['Total Equity', decimal($this->result['total_equity'])];
        $rows[] = [];

        $rows[] = ['Total Liabilities & Equity', decimal($this->result['total_liabilities_and_equity'])];

        return $rows;
    }
}
