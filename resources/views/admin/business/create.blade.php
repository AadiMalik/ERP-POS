@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Business</h4>

        <div class="card">
            <div class="card-header">
                <h5>{{ isset($business) ? 'Update' : 'New' }} Business</h5>
            </div>

            <div class="card-body">
                <form action="{{ url('admin/business') }}" method="POST" enctype="multipart/form-data">
                    @csrf

                    <input type="hidden" name="id" value="{{ $business->id ?? '' }}">

                    <div class="row">

                        <div class="col-md-6 mb-3">

                            <label class="d-flex align-items-center gap-2">

                                Subscription Package
                                <span class="text-danger">*</span>

                                <i class="fa fa-info-circle text-primary fs-5" id="packageInfo" style="cursor:pointer"
                                    data-bs-toggle="popover" data-bs-html="true" data-bs-trigger="hover focus click"
                                    title="Subscription Details">
                                </i>

                            </label>

                            <select class="form-select" name="package_id" id="packageSelect" required>

                                <option value="">-- Select Package --</option>

                                @foreach ($packages as $item)
                                    <option value="{{ $item->id }}" data-name="{{ $item->name }}"
                                        data-price="{{ $item->price }}" data-duration_type="{{ $item->duration_type }}"
                                        data-duration_days="{{ $item->duration_days }}"
                                        data-description="{{ $item->description }}"
                                        {{ old('package_id', $business->package_id ?? '') == $item->id ? 'selected' : '' }}>

                                        {{ $item->name }}

                                    </option>
                                @endforeach

                            </select>

                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Business Name:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="name" value="{{ $business->name ?? '' }}"
                                required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Business Code:</label>
                            <input type="text" class="form-control" name="code" value="{{ $business->code ?? '' }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Owner Name:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="owner_name" required
                                value="{{ $business->owner_name ?? '' }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Owner Email:<span style="color:red;">*</span></label>
                            <input type="email" class="form-control" name="owner_email" required
                                value="{{ $business->owner_email ?? '' }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Owner Phone:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="owner_phone" required
                                value="{{ $business->owner_phone ?? '' }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Email:<span style="color:red;">*</span></label>
                            <input type="email" class="form-control" name="email" value="{{ $business->email ?? '' }}">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Phone:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="phone" value="{{ $business->phone ?? '' }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>City:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="city" value="{{ $business->city ?? '' }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>State:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" required name="state"
                                value="{{ $business->state ?? '' }}">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Country:<span style="color:red;">*</span></label>
                            <input type="text" class="form-control" name="country"
                                value="{{ $business->country ?? '' }}" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Address:<span style="color:red;">*</span></label>
                            <textarea class="form-control" name="address">{{ $business->address ?? '' }}</textarea>
                        </div>

                        <div class="col-md-12">
                            <div id="datePreview" class="alert alert-warning d-none"></div>
                        </div>

                        <div class="col-md-12 mb-3">
                            <label>Description</label>
                            <textarea class="form-control" rows="5" name="description">{{ $business->description ?? '' }}</textarea>
                        </div>

                        <div class="col-md-6 mb-4">

                            <label>
                                Logo
                                @if (!isset($business))
                                    <span class="text-danger">*</span>
                                @endif
                            </label>

                            <div class="border rounded p-3 text-center position-relative" style="min-height:230px">

                                <img id="logoPreview"
                                    src="{{ isset($business) && $business->logo ? asset($business->logo) : asset('assets/img/no-image.png') }}"
                                    class="img-fluid rounded mb-3" style="max-height:140px;object-fit:contain;">

                                <input type="file" id="logoInput" class="form-control" name="logo"
                                    accept="image/*" {{ !isset($business) ? 'required' : '' }}>

                                <small class="text-muted d-block mt-2">
                                    JPG, PNG supported
                                </small>

                            </div>

                        </div>
                        <div class="col-md-12">
                            <button class="btn btn-primary">Save</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        (function() {

            const pkgSelect =
                document.getElementById('packageSelect');

            const pkgTooltip =
                document.getElementById('packageInfo');

            const previewBox =
                document.getElementById('datePreview');

            const logoInput =
                document.getElementById('logoInput');

            const logoPreview =
                document.getElementById('logoPreview');

            if (pkgTooltip) {
                new bootstrap.Tooltip(pkgTooltip);
            }

            function updatePackage() {

                if (!pkgSelect)
                    return;

                const selected =
                    pkgSelect.options[
                        pkgSelect.selectedIndex
                    ];

                if (!selected?.value) {

                    previewBox?.classList.add(
                        'd-none'
                    );

                    pkgTooltip?.setAttribute(
                        'data-bs-original-title',
                        'Select package to view details'
                    );

                    return;
                }

                const duration =
                    Number(
                        selected.dataset.duration_days || 0
                    );

                const start =
                    new Date();

                const end =
                    new Date();

                end.setDate(
                    start.getDate() + duration
                );

                const tooltip =
                    `
Package: ${selected.dataset.name}

Price: ${selected.dataset.price}

Type: ${selected.dataset.duration_type}

Duration: ${duration} Days

${selected.dataset.description || ''}
`;

                pkgTooltip?.setAttribute(
                    'data-bs-original-title',
                    tooltip
                );

                if (previewBox) {

                    previewBox.innerHTML = `
                <strong>
                    Subscription Preview
                </strong>
                <br>
                Start:
                ${start.toLocaleDateString()}
                <br>
                End:
                ${end.toLocaleDateString()}
            `;

                    previewBox.classList.remove(
                        'd-none'
                    );

                }

            }

            if (logoInput && logoPreview) {

                logoInput.addEventListener(
                    'change',

                    function(e) {

                        const file =
                            e.target.files[0];

                        if (!file)
                            return;

                        logoPreview.src =
                            URL.createObjectURL(
                                file
                            );

                        logoPreview.classList.remove(
                            'opacity-50'
                        );

                    }

                );

            }

            pkgSelect?.addEventListener(
                'change',
                updatePackage
            );

            updatePackage();

        })();
    </script>
@endsection
