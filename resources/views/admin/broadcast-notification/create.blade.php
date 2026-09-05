@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('New Broadcast Notification') }}</h4>

    @if (!$hasFirebase && $businessId)
        <div class="alert alert-warning">
            Firebase configuration is not configured for this business. You can still save a draft,
            but starting the campaign will be blocked until Firebase is configured.
            <a href="{{ url('admin/setting') }}#firebase">
                Configure Firebase
            </a>
        </div>
    @endif

    <div class="card">
        <form action="{{ url('admin/broadcast-notification') }}" method="POST">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="row g-4">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $businessId) == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @else
                        <input type="hidden" name="business_id" id="business_id" value="{{ $businessId }}">
                    @endif

                    <div class="col-md-6">
                        <label class="fw-semibold">Template</label>
                        <select class="form-select" name="template_id" id="template_id">
                            <option value="">-- Optional: load from template --</option>
                            @foreach ($templates as $tpl)
                            <option value="{{ $tpl->notification_template_id }}"
                                data-title="{{ e($tpl->title) }}"
                                data-body="{{ e($tpl->body) }}"
                                data-image="{{ e($tpl->image) }}"
                                data-data='@json($tpl->data)'
                                {{ old('template_id') == $tpl->notification_template_id ? 'selected' : '' }}>
                                {{ $tpl->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" id="title"
                            value="{{ old('title') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Image URL</label>
                        <input type="text" class="form-control" name="image" id="image"
                            value="{{ old('image') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Body <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="body" id="body" rows="3" required>{{ old('body') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Custom Data (JSON)</label>
                        <textarea class="form-control font-monospace" name="data" id="data" rows="3">{{ old('data') }}</textarea>
                    </div>

                    <div class="col-md-12">
                        <label class="fw-semibold">Target Users (with active FCM tokens) <span class="text-danger">*</span></label>
                        <div class="mb-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="select_all_users">Select All</button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="clear_users">Clear</button>
                            <span class="text-muted ms-2" id="token_hint">
                                {{ $usersWithTokens->count() }} user(s) with active tokens
                            </span>
                        </div>
                        <select class="form-select" name="user_ids[]" id="user_ids" multiple size="12" required>
                            @foreach ($usersWithTokens as $u)
                            <option value="{{ $u['user_id'] }}"
                                {{ collect(old('user_ids', []))->contains($u['user_id']) ? 'selected' : '' }}>
                                {{ $u['name'] }}
                                @if($u['email']) — {{ $u['email'] }} @endif
                                ({{ $u['token_count'] }} device{{ $u['token_count'] > 1 ? 's' : '' }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Each active device token becomes a separate recipient.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                <button class="btn btn-primary px-4">Save as Draft</button>
            </div>
        </form>
    </div>
</div>
@endsection
@section('js')
<script>
    function loadTemplateFields() {
        let opt = $('#template_id option:selected');
        if (!opt.val()) return;
        $('#title').val(opt.data('title') || '');
        $('#body').val(opt.data('body') || '');
        $('#image').val(opt.data('image') || '');
        let d = opt.attr('data-data');
        try {
            let parsed = d ? JSON.parse(d) : null;
            $('#data').val(parsed ? JSON.stringify(parsed, null, 2) : '');
        } catch (e) {
            $('#data').val('');
        }
    }

    function reloadUsersAndTemplates(businessId) {
        if (!businessId) return;
        ajaxRequest({
            url: url_local + '/admin/broadcast-notification/templates-by-business',
            method: 'GET',
            data: { business_id: businessId }
        }).then(function(res) {
            let options = '<option value="">-- Optional: load from template --</option>';
            (res.Data || []).forEach(function(tpl) {
                options += `<option value="${tpl.notification_template_id}"
                    data-title="${$('<div>').text(tpl.title || '').html()}"
                    data-body="${$('<div>').text(tpl.body || '').html()}"
                    data-image="${$('<div>').text(tpl.image || '').html()}"
                    data-data='${JSON.stringify(tpl.data || null)}'>${$('<div>').text(tpl.name).html()}</option>`;
            });
            $('#template_id').html(options);
        });

        ajaxRequest({
            url: url_local + '/admin/broadcast-notification/users-with-tokens',
            method: 'GET',
            data: { business_id: businessId }
        }).then(function(res) {
            let users = res.Data || [];
            let options = '';
            users.forEach(function(u) {
                options += `<option value="${u.user_id}">${$('<div>').text(u.name).html()}`
                    + (u.email ? ' — ' + $('<div>').text(u.email).html() : '')
                    + ` (${u.token_count} device${u.token_count > 1 ? 's' : ''})</option>`;
            });
            $('#user_ids').html(options);
            $('#token_hint').text(users.length + ' user(s) with active tokens');
        }).catch(function(err) {
            errorMessage(err.Message || 'Failed to load users');
        });
    }

    $('#template_id').on('change', loadTemplateFields);
    $('#business_id').on('change', function() {
        reloadUsersAndTemplates($(this).val());
    });
    $('#select_all_users').on('click', function() {
        $('#user_ids option').prop('selected', true);
    });
    $('#clear_users').on('click', function() {
        $('#user_ids option').prop('selected', false);
    });
</script>
@endsection
