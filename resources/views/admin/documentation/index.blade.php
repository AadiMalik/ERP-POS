@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-1">Documentation Center</h4>
        <p class="text-muted mb-4">Everything about how this ERP works — for your team and for developers.</p>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100 border-0 text-white" style="background: linear-gradient(135deg, #3833C8 0%, #2DD4BF 100%);">
                    <div class="card-body p-4">
                        <i class="fa fa-briefcase fa-2x mb-3"></i>
                        <h5 class="text-white fw-bold">Business Documentation</h5>
                        <p class="mb-4">
                            Modules, workflows, POS, sales, purchases, inventory, accounting,
                            HRM &amp; payroll, reports and settings — explained for owners and
                            managers, no technical detail required.
                        </p>
                        <div class="d-flex gap-2">
                            @canAccess('documentation.business.view')
                                <a href="{{ route('documentation.business') }}" class="btn btn-light fw-semibold">
                                    Browse Business Docs
                                </a>
                            @endcanAccess
                            @canAccess('documentation.business.pdf')
                                <a href="{{ route('documentation.business.pdf') }}" class="btn btn-outline-light" target="_blank">
                                    <i class="fa fa-file-pdf"></i> Download PDF
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
                        <h5 class="text-white fw-bold">Developer Documentation</h5>
                        <p class="mb-4">
                            Architecture, database schema, routes &amp; APIs, controllers,
                            services, permissions, and how modules connect — for building,
                            debugging and extending the system.
                        </p>
                        <div class="d-flex gap-2">
                            @canAccess('documentation.developer.view')
                                <a href="{{ route('documentation.developer') }}" class="btn fw-semibold" style="background:#03c3ec; color:#0b2530;">
                                    Browse Developer Docs
                                </a>
                            @endcanAccess
                            @canAccess('documentation.developer.pdf')
                                <a href="{{ route('documentation.developer.pdf') }}" class="btn btn-outline-light" target="_blank">
                                    <i class="fa fa-file-pdf"></i> Download PDF
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
                            <h6 class="mb-0">Contents · Business Documentation</h6>
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
                            <h6 class="mb-0">Contents · Developer Documentation</h6>
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
