@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"> Roles</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header">
            <h5>{{ isset($role) ? 'Update' : 'New' }} Role</h5>
        </div>
        <div class="card-body">
            <form action="{{ url('admin/roles') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="id" value="{{ isset($role) ? $role->id : '' }}">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Role Name:<span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Role Name"
                            value="{{ isset($role) ? $role->name : '' }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="permissions" class="form-label">Permissions:<span
                                class="text-danger">*</span></label>
                        <select class="form-select" name="permissions[]" id="permissions" multiple required>
                            @foreach ($permissions as $item)
                            <option value="{{ $item->name }}" {{ (isset($role) && $role->hasPermissionTo($item->name)) ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12 mb-3">
                        <label for="name" class="form-label">Description:<span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control" cols="20" rows="5" required>{{ isset($role) ? $role->description : '' }}</textarea>
                    </div>
                    <div class="col-md-12 mt-4">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
        </div>
    </div>
</div>
<!-- ========== Form components end ========== -->
@endsection
@section('js')
<script>
    $('#permissions').select2();
    @if(session('error'))
    errorMessage("{{ session('error') }}");
    @endif
</script>
@endsection