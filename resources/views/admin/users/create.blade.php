@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Admin User</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($user) ? 'Update' : 'New' }} Admin User</h5>
        </div>

        <form action="{{ url('admin/users') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <input type="hidden" name="id" value="{{ isset($user)? $user->id : '' }}">

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="fw-semibold">Full Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ $user->name ?? '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Email<span
                                class="text-danger">**</span></label>
                        <input type="email" class="form-control" name="email"
                            value="{{ $user->email ?? '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone"
                            value="{{ $user->phone ?? '' }}">
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save</button>
                </div>
            </div>
            <!-- Form Actions -->

        </form>
    </div>
</div>
@endsection

@section('js')
@if(session('error'))
<script>
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
@endsection

@section('css')
@endsection