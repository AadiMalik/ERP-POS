@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $days = $rows->first()->days ?? [];
@endphp
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
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
            padding: 2px 3px;
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
        'title' => 'Monthly Attendance Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                @foreach (array_keys($days) as $day)
                    <th class="text-center">{{ $day }}</th>
                @endforeach
                <th class="text-right">Present</th>
                <th class="text-right">Absent</th>
                <th class="text-right">Leave</th>
                <th class="text-right">Hours</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->employee_code }}</td>
                    <td>{{ $row->name }}</td>
                    @foreach ($row->days as $value)
                        <td class="text-center">{{ $value }}</td>
                    @endforeach
                    <td class="text-right">{{ $row->present_count }}</td>
                    <td class="text-right">{{ $row->absent_count }}</td>
                    <td class="text-right">{{ $row->leave_count }}</td>
                    <td class="text-right">{{ $row->total_working_hours }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 6 + count($days) }}" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>

</html>
