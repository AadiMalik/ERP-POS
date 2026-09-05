@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-1">{{ __('documentation.center_title') }}</h4>
        <p class="text-muted mb-4">{{ __('documentation.center_subtitle') }}</p>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 text-white" style="background: linear-gradient(135deg, #3833C8 0%, #2DD4BF 100%);">
                    <div class="card-body p-4">
                        <i class="fa fa-briefcase fa-2x mb-3"></i>
                        <h5 class="text-white fw-bold">{{ __('documentation.business_docs') }}</h5>
                        <p class="mb-4">
                            {{ __('documentation.business_docs_blurb') }}
                        </p>
                        <div class="d-flex gap-2">
                            @canAccess('documentation.business.view')
                                <a href="{{ route('documentation.business') }}" class="btn btn-light fw-semibold">
                                    {{ __('documentation.browse_business') }}
                                </a>
                            @endcanAccess
                            @canAccess('documentation.business.pdf')
                                <a href="{{ route('documentation.business.pdf') }}" class="btn btn-outline-light" target="_blank">
                                    <i class="fa fa-file-pdf"></i> {{ __('documentation.download_pdf') }}
                                </a>
                            @endcanAccess
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card h-100 border-0 text-white" style="background: linear-gradient(135deg, #2b2d42 0%, #454a63 100%);">
                    <div class="card-body p-4">
                        <i class="fa fa-code fa-2x mb-3" style="color:#03c3ec;"></i>
                        <h5 class="text-white fw-bold">{{ __('documentation.developer_docs') }}</h5>
                        <p class="mb-4">
                            {{ __('documentation.developer_docs_blurb') }}
                        </p>
                        <div class="d-flex gap-2">
                            @canAccess('documentation.developer.view')
                                <a href="{{ route('documentation.developer') }}" class="btn fw-semibold" style="background:#03c3ec; color:#0b2530;">
                                    {{ __('documentation.browse_developer') }}
                                </a>
                            @endcanAccess
                            @canAccess('documentation.developer.pdf')
                                <a href="{{ route('documentation.developer.pdf') }}" class="btn btn-outline-light" target="_blank">
                                    <i class="fa fa-file-pdf"></i> {{ __('documentation.download_pdf') }}
                                </a>
                            @endcanAccess
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            @canAccess('documentation.business.view')
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('documentation.contents_business') }}</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($business_sections as $section)
                                <a href="{{ route('documentation.business', $section['slug']) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    {{ $section['title'] }}
                                    <i class="fa fa-chevron-right text-muted small"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endcanAccess

            @canAccess('documentation.developer.view')
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0">{{ __('documentation.contents_developer') }}</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            @foreach ($developer_sections as $section)
                                <a href="{{ route('documentation.developer', $section['slug']) }}"
                                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    {{ $section['title'] }}
                                    <i class="fa fa-chevron-right text-muted small"></i>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endcanAccess
        </div>
    </div>
@endsection
