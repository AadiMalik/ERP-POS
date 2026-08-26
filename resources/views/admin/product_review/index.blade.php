@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Reviews</h4>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="product_review_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Reviewer</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Status</th>
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
<script src="{{ asset('public/assets/js/admin/product_review.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'product' , name: 'product'},
{data: 'reviewer_name' , name: 'reviewer_name'},
{data: 'rating' , name: 'rating', 'sortable': false},
{data: 'comment' , name: 'comment'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'date_created' , name: 'date_created'},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'product-review/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'product_review_table',
'variable' => 'product_review_table',
'order' => '[[5, \'desc\']]',
])
@endsection
