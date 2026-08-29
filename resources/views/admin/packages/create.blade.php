@extends('layouts.app')

@section('css')
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    <h4 class="fw-bold py-3 mb-4">
        Packages
    </h4>

    <div class="card">

        <div class="card-header bg-white border-bottom">
        <h5 class="mb-0">
                {{ isset($package) ? 'Update' : 'New' }} Package
            </h5>
        </div>


        @php
            $moduleState = isset($package) ? $package->modules->keyBy('module_key') : collect();
            $moduleGroups = \App\Support\Subscription\SubscriptionModuleRegistry::grouped();
        @endphp

        <form action="{{ url('admin/packages') }}" method="POST">

            @csrf
            <div class="card-body">

                <input type="hidden" name="package_id" value="{{ $package->package_id ?? '' }}">

                <div class="row">

                    {{-- Name --}}
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            Name
                        </label>

                        <input type="text" class="form-control" name="name" required
                            value="{{ $package->name ?? '' }}">
                    </div>

                    {{-- Price (list price for this duration) --}}
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Price (PKR)</label>
                        <input type="number" step="0.01" class="form-control" name="price"
                            value="{{ $package->price ?? '' }}" placeholder="e.g. 4500">
                        <small class="text-muted">List price for this package period (monthly amount or yearly annual total).</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Discount %</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control" name="discount"
                            value="{{ $package->discount ?? 0 }}" placeholder="0">
                        <small class="text-muted">Applied to list price. Charged amount = price × (1 − discount/100).</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Currency</label>
                        <input type="text" class="form-control" name="currency" value="{{ $package->currency ?? 'PKR' }}">
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" class="form-control" name="code" value="{{ $package->code ?? '' }}" placeholder="NODE-01">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Badge</label>
                        <input type="text" class="form-control" name="badge" value="{{ $package->badge ?? '' }}" placeholder="Most Provisioned">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">CTA Label</label>
                        <input type="text" class="form-control" name="cta" value="{{ $package->cta ?? '' }}" placeholder="Choose Starter">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tagline</label>
                        <input type="text" class="form-control" name="tagline" value="{{ $package->tagline ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Best For</label>
                        <input type="text" class="form-control" name="best_for" value="{{ $package->best_for ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Support</label>
                        <input type="text" class="form-control" name="support" value="{{ $package->support ?? '' }}">
                    </div>

                    {{-- Order --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Order
                        </label>

                        <input type="number" class="form-control" name="order" value="{{ $package->order ?? '' }}">
                    </div>

                    {{-- Duration Type --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Duration Type
                        </label>

                        <select class="form-select" name="duration_type">

                            <option value="monthly"
                                {{ isset($package) && $package->duration_type == 'monthly' ? 'selected' : '' }}>
                                Monthly
                            </option>

                            <option value="yearly"
                                {{ isset($package) && $package->duration_type == 'yearly' ? 'selected' : '' }}>
                                Yearly
                            </option>

                        </select>

                    </div>

                    {{-- Duration --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Duration Days
                        </label>

                        <input type="number" class="form-control" name="duration_days"
                            value="{{ $package->duration_days ?? '' }}">
                    </div>

                    {{-- Status --}}
                    <div class="col-md-6 mb-3">

                        <label class="form-label">
                            Status
                        </label>

                        <select class="form-select" name="status">

                            <option value="1" {{ isset($package) && $package->status == 1 ? 'selected' : '' }}>
                                Active
                            </option>

                            <option value="0" {{ isset($package) && $package->status == 0 ? 'selected' : '' }}>
                                Inactive
                            </option>

                        </select>

                    </div>

                    {{-- Description --}}
                    <div class="col-md-12 mb-3">

                        <label class="form-label">
                            Description
                        </label>

                        <textarea class="form-control" rows="5" name="description">{{ $package->description ?? '' }}</textarea>

                    </div>

                </div>
            </div>

            <div class="card-header bg-white border-top border-bottom">
                <h5 class="mb-0">Module &amp; Limits</h5>
                <small class="text-muted">Enable the modules this package includes. Limited modules get a numeric
                    cap (default {{ 5 }}) unless marked Unlimited.</small>
            </div>

            <div class="card-body">
                @foreach ($moduleGroups as $category => $modules)
                    <h6 class="mt-3 mb-2 border-bottom pb-2">{{ $category }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm align-middle module-limit-table">
                            <thead>
                                <tr>
                                    <th>Module</th>
                                    <th style="width:100px">Enabled</th>
                                    <th style="width:160px">Limit</th>
                                    <th style="width:110px">Unlimited</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modules as $key => $meta)
                                    @php
                                        $moduleRow = $moduleState->get($key);
                                        $enabled = $moduleRow ? $moduleRow->is_enabled : ($meta['default_enabled'] ?? true);
                                        $unlimited = $moduleRow ? $moduleRow->is_unlimited : false;
                                        $limit = $moduleRow && !$unlimited ? $moduleRow->limit_value : ($meta['default_limit'] ?? 5);
                                    @endphp
                                    <tr>
                                        <td>{{ $meta['label'] }}</td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input type="checkbox" class="form-check-input module-enable-toggle"
                                                    name="modules[{{ $key }}][enabled]" value="1"
                                                    {{ $enabled ? 'checked' : '' }}>
                                            </div>
                                        </td>
                                        @if ($meta['type'] === 'limited')
                                            <td class="module-limit-cell">
                                                <input type="number" min="0" step="1"
                                                    class="form-control form-control-sm module-limit-input"
                                                    name="modules[{{ $key }}][limit]" value="{{ $limit }}">
                                            </td>
                                            <td class="module-unlimited-cell">
                                                @if ($meta['unlimited_allowed'] ?? false)
                                                    <div class="form-check">
                                                        <input type="checkbox"
                                                            class="form-check-input module-unlimited-toggle"
                                                            name="modules[{{ $key }}][unlimited]" value="1"
                                                            {{ $unlimited ? 'checked' : '' }}>
                                                    </div>
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="2" class="text-muted small">&mdash;</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>

            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </form>

    </div>

</div>
@endsection


@section('js')
@if (session('error'))
<script>
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
<script>
    function refreshModuleRow($row) {
        var enabled = $row.find('.module-enable-toggle').is(':checked');
        var unlimited = $row.find('.module-unlimited-toggle').is(':checked');

        $row.find('.module-unlimited-cell').toggleClass('opacity-50 pe-none', !enabled);
        $row.find('.module-limit-input').toggleClass('opacity-50 pe-none', !enabled || unlimited);
    }

    $(document).on('change', '.module-enable-toggle, .module-unlimited-toggle', function() {
        refreshModuleRow($(this).closest('tr'));
    });

    $('.module-limit-table tbody tr').each(function() {
        refreshModuleRow($(this));
    });
</script>
@endsection