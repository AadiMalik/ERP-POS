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
            padding: 3px 5px;
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
        'title' => __('reports.journal_register'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="data-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_jv_number') }}</th>
                <th>{{ __('reports.col_journal_type') }}</th>
                <th>{{ __('reports.col_reference') }}</th>
                <th>{{ __('reports.col_narration') }}</th>
                <th class="text-right">{{ __('reports.col_debit') }}</th>
                <th class="text-right">{{ __('reports.col_credit') }}</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ localDate($row->entry_date) }}</td>
                    <td>{{ $row->entry_no }}</td>
                    <td>{{ $row->journal_name ?? $row->journal_short }}</td>
                    <td>{{ $row->reference_no }}</td>
                    <td>{{ $row->description }}</td>
                    <td class="text-right">{{ currency($row->total_debit) }}</td>
                    <td class="text-right">{{ currency($row->total_credit) }}</td>
                    <td>{{ ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No records found</td>
                </tr>
            @endforelse
            <tr>
                <td colspan="5"><strong>Total</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_debit')) }}</strong></td>
                <td class="text-right"><strong>{{ currency($rows->sum('total_credit')) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
</body>

</html>
