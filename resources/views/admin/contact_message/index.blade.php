@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('website_cms.contact_messages') }}</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="contact_message_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.email') }}</th>
                            <th>Subject</th>
                            <th>{{ __('common.status') }}</th>
                            <th>Received</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.contact_message.model.view')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/contact_message.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'email' , name: 'email'},
{data: 'subject' , name: 'subject'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'date_created' , name: 'date_created'},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'contact-message/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'contact_message_table',
'variable' => 'contact_message_table',
'order' => '[[4, \'desc\']]',
])
@endsection
