@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Notification Template</h4>
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($template) ? 'Update' : 'New' }} Template</h5>
        </div>
        <form action="{{ url('admin/notification-template') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="notification_template_id"
                    value="{{ $template->notification_template_id ?? '' }}">
                <div class="row g-4">
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $template->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ old('name', $template->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title"
                            value="{{ old('title', $template->title ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="active" {{ old('status', $template->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $template->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Body <span class="text-danger">*</span></label>
                        <textarea class="form-control" name="body" rows="3" required>{{ old('body', $template->body ?? '') }}</textarea>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Image URL</label>
                        <input type="text" class="form-control" name="image"
                            value="{{ old('image', $template->image ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Custom Data (JSON)</label>
                        <textarea class="form-control font-monospace" name="data" rows="4"
                            placeholder='{"type":"promo","screen":"home"}'>{{ old('data', isset($template) && $template->data ? json_encode($template->data, JSON_PRETTY_PRINT) : '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                <button class="btn btn-primary px-4">Save Template</button>
            </div>
        </form>
    </div>
</div>
@endsection
