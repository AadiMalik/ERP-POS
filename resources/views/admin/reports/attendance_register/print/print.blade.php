@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
    $days = $rows->first()->days ?? [];
@endphp
@extends('layouts.print')

@section('title', 'Attendance Register')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Attendance Register',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table" style="font-size: 9px;">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                @foreach (array_keys($days) as $date)
                    <th class="text-center">{{ \Carbon\Carbon::parse($date)->format('d M') }}</th>
                @endforeach
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
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 2 + count($days) }}" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
