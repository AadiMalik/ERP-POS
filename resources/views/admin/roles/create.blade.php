@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('roles.title') }}</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header">
                <h5>{{ isset($role) ? __('roles.update_heading') : __('roles.new_heading') }}</h5>
            </div>
            <div class="card-body">
                <form action="{{ url('admin/roles') }}" method="POST">
                    @csrf
                    <input type="hidden" name="id" id="id" value="{{ isset($role) ? $role->id : '' }}">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">{{ __('roles.role_name') }}:<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="{{ __('roles.role_name') }}"
                                value="{{ isset($role) ? $role->name : '' }}"
                                {{ getRoleName() != RoleNames::SUPERADMIN ? 'readonly' : '' }} required>
                        </div>
                        @if (getRoleName() == RoleNames::SUPERADMIN)
                            <div class="col-md-6 mb-3">
                                <label for="business" class="form-label">{{ __('common.business') }}:</label>
                                <select class="form-select" name="business_id" id="business_id">
                                    <option value="" selected>{{ __('common.select_business') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->id }}"
                                            {{ isset($role) && $role->business_id == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">{{ __('common.description') }}:<span class="text-danger">*</span></label>
                            <textarea name="description" id="description" class="form-control" cols="20" rows="5" required>{{ isset($role) ? $role->description : '' }}</textarea>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">{{ __('roles.permissions') }}:<span class="text-danger">*</span></label>
                            <div id="permission-modules">
                                @foreach ($groupedPermissions as $moduleKey => $module)
                                    <div class="card mb-2">
                                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                                            <span class="fw-semibold" role="button" data-bs-toggle="collapse"
                                                data-bs-target="#module-{{ $moduleKey }}">
                                                {{ $module['label'] }}
                                            </span>
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input module-toggle-all" type="checkbox"
                                                    data-module="{{ $moduleKey }}" id="module-all-{{ $moduleKey }}">
                                                <label class="form-check-label" for="module-all-{{ $moduleKey }}">{{ __('roles.enable_all') }}</label>
                                            </div>
                                        </div>
                                        <div id="module-{{ $moduleKey }}" class="collapse show">
                                            <div class="card-body row">
                                                @foreach ($module['actions'] as $actionKey => $perm)
                                                    <div class="col-md-3 col-sm-4 col-6 mb-2">
                                                        <div class="form-check form-switch">
                                                            <input class="form-check-input permission-toggle" type="checkbox"
                                                                data-module="{{ $moduleKey }}"
                                                                name="permissions[]" value="{{ $perm['name'] }}"
                                                                id="perm-{{ $moduleKey }}-{{ $actionKey }}"
                                                                {{ isset($role) && $role->hasPermissionTo($perm['name']) ? 'checked' : '' }}>
                                                            <label class="form-check-label"
                                                                for="perm-{{ $moduleKey }}-{{ $actionKey }}">{{ $perm['label'] }}</label>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-12 mt-4">
                            <button type="submit" class="btn btn-primary">{{ __('roles.save_role') }}</button>
                        </div>
                    </div>
            </div>
        </div>
    </div>
    <!-- ========== Form components end ========== -->
@endsection
@section('js')
    <script>
        // Sync each module's "Enable All" switch with its individual permission switches.
        $(document).on('change', '.module-toggle-all', function () {
            var module = $(this).data('module');
            $('.permission-toggle[data-module="' + module + '"]').prop('checked', this.checked);
        });

        $(document).on('change', '.permission-toggle', function () {
            var module = $(this).data('module');
            var all = $('.permission-toggle[data-module="' + module + '"]');
            var checked = all.filter(':checked');
            $('.module-toggle-all[data-module="' + module + '"]').prop('checked', all.length === checked.length);
        });

        // Pre-check each module's "Enable All" switch on load if every permission in it is already checked.
        $('.module-toggle-all').each(function () {
            var module = $(this).data('module');
            var all = $('.permission-toggle[data-module="' + module + '"]');
            var checked = all.filter(':checked');
            $(this).prop('checked', all.length > 0 && all.length === checked.length);
        });

        @if (session('error'))
            errorMessage("{{ session('error') }}");
        @endif
    </script>
@endsection
