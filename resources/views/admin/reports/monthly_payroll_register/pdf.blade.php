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
            font-size: 10px;
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
        'title' => 'Monthly Payroll Register',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Employee Code</th>
                <th>Name</th>
                <th>Department</th>
                <th class="text-right">Basic</th>
                <th class="text-right">Earnings</th>
                <th class="text-right">Deductions</th>
                <th class="text-right">Net Salary</th>
                <th class="text-right">Present</th>
                <th class="text-right">Absent</th>
                <th class="text-right">Leave</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td class="text-right">{{ currency($row->basic_salary) }}</td>
                    <td class="text-right">{{ currency($row->total_earnings) }}</td>
                    <td class="text-right">{{ currency($row->total_deductions) }}</td>
                    <td class="text-right">{{ currency($row->net_salary) }}</td>
                    <td class="text-right">{{ $row->present_days }}</td>
                    <td class="text-right">{{ $row->absent_days }}</td>
                    <td class="text-right">{{ $row->leave_days }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
