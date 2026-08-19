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
        'title' => 'Employee Attendance & Payroll Comparison Report',
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
                <th>Period</th>
                <th class="text-right">Payslip Present</th>
                <th class="text-right">Actual Present</th>
                <th class="text-right">Payslip Absent</th>
                <th class="text-right">Actual Absent</th>
                <th>Match Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee?->employee_code }}</td>
                    <td>{{ $row->employee?->user?->name }}</td>
                    <td>{{ $row->employee?->department?->name }}</td>
                    <td>{{ $row->payrollRun ? (date('F', mktime(0, 0, 0, $row->payrollRun->month, 1)) . ' ' . $row->payrollRun->year) : '-' }}</td>
                    <td class="text-right">{{ $row->present_days }}</td>
                    <td class="text-right">{{ $row->actual_present }}</td>
                    <td class="text-right">{{ $row->absent_days }}</td>
                    <td class="text-right">{{ $row->actual_absent }}</td>
                    <td>{{ $row->is_mismatched ? 'Mismatch' : 'Matched' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
