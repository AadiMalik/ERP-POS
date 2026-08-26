@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Newsletter Subscribers</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="newsletter_subscriber_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Email</th>
                            <th>Source</th>
                            <th>Subscribed</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/newsletter_subscriber.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'email' , name: 'email'},
{data: 'source' , name: 'source'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'date_created' , name: 'date_created'},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'newsletter-subscriber/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'newsletter_subscriber_table',
'variable' => 'newsletter_subscriber_table',
'order' => '[[3, \'desc\']]',
])
@endsection
