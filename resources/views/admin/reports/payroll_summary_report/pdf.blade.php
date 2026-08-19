@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th,
        table.data-table td {
            border: 1px solid #ccc;
            padding: 4px 6px;
        }

        table.data-table th {
            background-color: #f2f2f2;
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>

<body>
    @include('admin.partials.print.pdf_header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Payroll Summary Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Period</th>
                <th class="text-right">Employees</th>
                <th class="text-right">Gross Salary</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Advance Deduction</th>
                <th class="text-right">Overtime</th>
                <th class="text-right">Net Salary</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ date('F', mktime(0, 0, 0, $row->month, 1)) }} {{ $row->year }}</td>
                    <td class="text-right">{{ $row->employee_count }}</td>
                    <td class="text-right">{{ currency($row->total_gross) }}</td>
                    <td class="text-right">{{ currency($row->total_deductions) }}</td>
                    <td class="text-right">{{ currency($row->total_advance_deduction) }}</td>
                    <td class="text-right">{{ currency($row->total_overtime) }}</td>
                    <td class="text-right">{{ currency($row->total_net_salary) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
