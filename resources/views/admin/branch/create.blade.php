@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('branches.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($branch) ? __('branches.update_heading') : __('branches.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/branch') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">

                <input type="hidden" name="branch_id" value="{{ isset($branch) ? $branch->branch_id : '' }}">

                <div class="row g-4">
                    <!-- Left Column - Form Fields -->
                    <div class="col-md-12">

                        <!-- Basic Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('common.basic_information') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    @if (!empty($business))
                                    <div class="col-md-6">
                                        <label class="fw-semibold">
                                            Business <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select" name="business_id" id="business_id" required>
                                            <option value="">{{ __('common.select_business') }}</option>
                                            @foreach ($business as $item)
                                            <option value="{{ $item->business_id }}"
                                                {{ old('business_id', $branch->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                {{ $item->code }} {{ $item->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endif
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_name') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ $branch->name ?? '' }}" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_code') }}</label>
                                        <input type="text" class="form-control" name="code"
                                            value="{{ $branch->code ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_email') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ $branch->email ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.branch_phone') }} <span
                                                class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="phone"
                                            value="{{ $branch->phone ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Address Information Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('common.address_information') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.city') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="city"
                                            value="{{ $branch->city ?? '' }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.state') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" required name="state"
                                            value="{{ $branch->state ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.country') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="country"
                                            value="{{ $branch->country ?? '' }}" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('common.address') }} <span class="text-danger">*</span></label>
                                        <textarea class="form-control" name="address" rows="2">{{ $branch->address ?? '' }}</textarea>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('branches.map_location') }}</label>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <small class="text-muted">{{ __('branches.map_location_hint') }}</small>
                                            <button type="button" id="useMyLocationBtn" class="btn btn-sm btn-outline-secondary">
                                                <i class="fa fa-location-arrow"></i> {{ __('branches.use_my_location') }}
                                            </button>
                                        </div>
                                        <div class="position-relative mb-2">
                                            <input type="text" id="branchAddressSearch" class="form-control"
                                                placeholder="{{ __('branches.search_address') }}" autocomplete="off">
                                            <div id="branchAddressSearchResults" class="list-group position-absolute w-100 shadow-sm"
                                                style="z-index: 1000; max-height: 220px; overflow-y: auto; display: none; background-color: var(--bs-body-bg); border: 1px solid var(--bs-border-color);"></div>
                                        </div>
                                        <div id="branchLocationMap" style="height: 320px; border-radius: 8px;"></div>
                                        <input type="hidden" id="latitude" name="latitude" value="{{ old('latitude', $branch->latitude ?? '') }}">
                                        <input type="hidden" id="longitude" name="longitude" value="{{ old('longitude', $branch->longitude ?? '') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.free_delivery_min_order_amount') }}</label>
                                        <input type="number" step="0.01" min="0" class="form-control" name="free_delivery_min_order_amount"
                                            value="{{ old('free_delivery_min_order_amount', $branch->free_delivery_min_order_amount ?? '') }}">
                                        <small class="text-muted">{{ __('branches.free_delivery_hint') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Linked Warehouses Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('branches.linked_warehouses') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('branches.linked_warehouses') }}</label>
                                        <select class="form-select" name="warehouse_ids[]" id="warehouse_ids" multiple>
                                            @foreach (($warehouses ?? []) as $warehouse)
                                            <option value="{{ $warehouse->warehouse_id }}"
                                                {{ in_array($warehouse->warehouse_id, old('warehouse_ids', $linked_warehouse_ids ?? [])) ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">{{ __('branches.linked_warehouses_hint') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- POS Register Mode Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('branches.pos_register_hours') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.open_time') }}</label>
                                        <input type="time" class="form-control" name="open_time"
                                            value="{{ $branch->open_time ?? '' }}">
                                        <small class="text-muted">{{ __('branches.open_time_hint') }}</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('branches.close_time') }}</label>
                                        <input type="time" class="form-control" name="close_time"
                                            value="{{ $branch->close_time ?? '' }}">
                                        <small class="text-muted">{{ __('branches.open_time_hint') }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Logo & Description Section -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('branches.branch_logo') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">
                                            Logo
                                            @if (!isset($branch))
                                            <span class="text-danger">*</span>
                                            @endif
                                        </label>
                                        <div class="border rounded-3 p-3 text-center bg-light">
                                            <img id="logoPreview"
                                                src="{{ isset($branch) && $branch->logo ? asset('public/uploads/branch/' . $branch->logo) : asset('public/assets/img/no-image.png') }}"
                                                class="img-fluid rounded-3 mb-2"
                                                style="max-height: 120px; object-fit: contain;">
                                            @if (isset($branch) && $branch->logo)
                                            <div class="mb-2">
                                                <small class="text-muted">{{ __('common.previous_logo') }}</small>
                                            </div>
                                            @endif
                                            <input type="file" id="logoInput" class="form-control" name="logo"
                                                accept="image/*" {{ !isset($branch) ? 'required' : '' }}>
                                            <small class="text-muted d-block mt-2">
                                                <i class="fa fa-info-circle"></i> {{ __('common.jpg_png_supported') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <!-- Form Actions -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('branches.save_branch') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@section('js')
@php
    $__i18nBranches = [
        'please_select_valid_image' => __('branches.please_select_valid_image'),
        'location_not_supported' => __('branches.location_not_supported'),
        'location_permission_denied' => __('branches.location_permission_denied'),
        'search_address_no_results' => __('branches.search_address_no_results'),
    ];
@endphp
<script>window.i18n_branches = @json($__i18nBranches);</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if (session('error'))
<script>
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#warehouse_ids').select2({
            placeholder: '{{ __('branches.linked_warehouses') }}',
            allowClear: true
        });
    });
    (function() {
        const logoInput = document.getElementById('logoInput');
        const logoPreview = document.getElementById('logoPreview');

        // Logo preview handler
        if (logoInput && logoPreview) {
            logoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        logoPreview.src = event.target.result;
                    };
                    reader.readAsDataURL(file);
                } else if (file) {
                    alert(window.i18n_branches?.please_select_valid_image || 'Please select a valid image file (JPG, PNG)');
                    logoInput.value = '';
                }
            });
        }
    })();

    // Branch location picker - Leaflet + OpenStreetMap (no API key required).
    (function() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const hasSavedLocation = !!(latInput.value && lngInput.value);
        const startLat = hasSavedLocation ? parseFloat(latInput.value) : 24.8607; // Karachi fallback center
        const startLng = hasSavedLocation ? parseFloat(lngInput.value) : 67.0011;

        const map = L.map('branchLocationMap').setView([startLat, startLng], hasSavedLocation ? 15 : 5);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        let marker = hasSavedLocation ? L.marker([startLat, startLng], { draggable: true }).addTo(map) : null;

        function setMarker(lat, lng) {
            latInput.value = lat.toFixed(7);
            lngInput.value = lng.toFixed(7);
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', function() {
                    const pos = marker.getLatLng();
                    setMarker(pos.lat, pos.lng);
                });
            }
        }

        map.on('click', function(e) {
            setMarker(e.latlng.lat, e.latlng.lng);
        });

        if (marker) {
            marker.on('dragend', function() {
                const pos = marker.getLatLng();
                setMarker(pos.lat, pos.lng);
            });
        }

        document.getElementById('useMyLocationBtn').addEventListener('click', function() {
            if (!navigator.geolocation) {
                errorMessage(window.i18n_branches.location_not_supported);
                return;
            }
            navigator.geolocation.getCurrentPosition(function(pos) {
                map.setView([pos.coords.latitude, pos.coords.longitude], 16);
                setMarker(pos.coords.latitude, pos.coords.longitude);
            }, function() {
                errorMessage(window.i18n_branches.location_permission_denied);
            });
        });

        // Address search - Nominatim (OpenStreetMap) geocoding, no API key required.
        const searchInput = document.getElementById('branchAddressSearch');
        const searchResults = document.getElementById('branchAddressSearchResults');
        let searchDebounce = null;
        let searchAbort = null;

        function hideSearchResults() {
            searchResults.style.display = 'none';
            searchResults.innerHTML = '';
        }

        function renderSearchResults(places) {
            searchResults.innerHTML = '';
            if (!places.length) {
                const empty = document.createElement('div');
                empty.className = 'list-group-item text-muted small';
                empty.textContent = window.i18n_branches.search_address_no_results;
                searchResults.appendChild(empty);
            } else {
                places.forEach(function(place) {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'list-group-item list-group-item-action small';
                    item.textContent = place.display_name;
                    item.addEventListener('click', function() {
                        const lat = parseFloat(place.lat);
                        const lng = parseFloat(place.lon);
                        map.setView([lat, lng], 16);
                        setMarker(lat, lng);
                        searchInput.value = place.display_name;
                        hideSearchResults();
                    });
                    searchResults.appendChild(item);
                });
            }
            searchResults.style.display = 'block';
        }

        searchInput.addEventListener('input', function() {
            const query = searchInput.value.trim();
            clearTimeout(searchDebounce);
            if (query.length < 3) {
                hideSearchResults();
                return;
            }
            searchDebounce = setTimeout(function() {
                if (searchAbort) searchAbort.abort();
                searchAbort = new AbortController();
                fetch('https://nominatim.openstreetmap.org/search?format=json&limit=5&q=' + encodeURIComponent(query), {
                    signal: searchAbort.signal,
                    headers: { 'Accept-Language': document.documentElement.lang || 'en' },
                })
                    .then(res => res.json())
                    .then(renderSearchResults)
                    .catch(function(err) {
                        if (err.name !== 'AbortError') hideSearchResults();
                    });
            }, 400);
        });

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                hideSearchResults();
            }
        });
    })();
</script>
@endsection

@section('css')
<style>
    .sticky-top {
        position: sticky;
        top: 20px;
        z-index: 1;
    }

    .package-detail-item {
        transition: all 0.2s ease;
    }

    .package-detail-item:hover {
        background-color: #f8f9fa;
        margin-left: 5px;
        padding-left: 5px;
        border-radius: 8px;
    }

    #packageInfoBox {
        min-height: 400px;
    }

    #nextExpiryBox {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }

    .card-header.bg-primary {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    }
</style>
@endsection