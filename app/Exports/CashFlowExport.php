<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CashFlowExport implements FromArray, ShouldAutoSize
{
    public function __construct(protected array $result)
    {
    }

    public function array(): array
    {
        $rows = [
            ['Cash Flow Statement'],
            [],
            ['Particulars', 'Inflow', 'Outflow', 'Net'],
            [],
            ['Cash flows from operating activities'],
        ];

        foreach ($this->result['operating'] as $row) {
            $rows[] = [$row->label, decimal($row->inflow), decimal($row->outflow), decimal($row->amount)];
        }
        $rows[] = ['Net cash from operating activities', '', '', decimal($this->result['net_operating'])];
        $rows[] = [];

        $rows[] = ['Cash flows from investing activities'];
        foreach ($this->result['investing'] as $row) {
            $rows[] = [$row->label, decimal($row->inflow), decimal($row->outflow), decimal($row->amount)];
        }
        $rows[] = ['Net cash from investing activities', '', '', decimal($this->result['net_investing'])];
        $rows[] = [];

        $rows[] = ['Cash flows from financing activities'];
        foreach ($this->result['financing'] as $row) {
            $rows[] = [$row->label, decimal($row->inflow), decimal($row->outflow), decimal($row->amount)];
        }
        $rows[] = ['Net cash from financing activities', '', '', decimal($this->result['net_financing'])];
        $rows[] = [];

        $rows[] = ['Net increase / (decrease) in cash', '', '', decimal($this->result['net_increase'])];
        $rows[] = ['Opening cash & bank balance', '', '', decimal($this->result['opening_cash'])];
        $rows[] = ['Closing cash & bank balance', '', '', decimal($this->result['closing_cash'])];
        $rows[] = [];
        $rows[] = ['Reconciliation'];
        $rows[] = ['Opening + Net movement', '', '', decimal($this->result['reconciled_closing'])];
        $rows[] = ['Actual closing balance', '', '', decimal($this->result['closing_cash'])];
        $rows[] = ['Difference', '', '', decimal($this->result['reconciliation_difference'])];

        return $rows;
    }
}
