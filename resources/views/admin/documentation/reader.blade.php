@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
            <div>
                <a href="{{ route('documentation.index') }}" class="text-muted small">Documentation</a>
                <span class="text-muted small mx-1">/</span>
                <span class="text-muted small">{{ $audience_label }}</span>
                <h4 class="fw-bold mt-1 mb-0">{{ $current['title'] }}</h4>
            </div>
            <div class="d-flex gap-2">
                @if ($audience === 'business')
                    @canAccess('documentation.developer.view')
                        <a href="{{ route('documentation.developer') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-code"></i> Switch to Developer Docs
                        </a>
                    @endcanAccess
                    @canAccess('documentation.business.pdf')
                        <a href="{{ route('documentation.business.pdf') }}" class="btn btn-outline-danger btn-sm" target="_blank">
                            <i class="fa fa-file-pdf"></i> Download PDF
                        </a>
                    @endcanAccess
                @else
                    @canAccess('documentation.business.view')
                        <a href="{{ route('documentation.business') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fa fa-briefcase"></i> Switch to Business Docs
                        </a>
                    @endcanAccess
                    @canAccess('documentation.developer.pdf')
                        <a href="{{ route('documentation.developer.pdf') }}" class="btn btn-outline-danger btn-sm" target="_blank">
                            <i class="fa fa-file-pdf"></i> Download PDF
                        </a>
                    @endcanAccess
                @endif
            </div>
        </div>

        <div class="row g-4 mt-1">
            <div class="col-lg-3">
                <div class="card">
                    <div class="list-group list-group-flush">
                        @foreach ($sections as $section)
                            <a href="{{ route('documentation.' . $audience, $section['slug']) }}"
                                class="list-group-item list-group-item-action {{ $section['slug'] === $current['slug'] ? 'active' : '' }}">
                                {{ $section['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="card">
                    <div class="card-body documentation-content">
                        {!! $html !!}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .documentation-content h1, .documentation-content h2, .documentation-content h3 {
            font-weight: 700;
        }
        .documentation-content h1 { font-size: 1.5rem; margin-bottom: 1rem; }
        .documentation-content h2 { font-size: 1.25rem; margin-top: 1.75rem; margin-bottom: .75rem; }
        .documentation-content h3 { font-size: 1.05rem; margin-top: 1.25rem; margin-bottom: .5rem; }
        .documentation-content table { width: 100%; margin-bottom: 1rem; }
        .documentation-content table th, .documentation-content table td {
            border: 1px solid #eceef1; padding: .5rem .75rem; font-size: .875rem;
        }
        .documentation-content code {
            background: #f4f5fa; padding: .1rem .35rem; border-radius: 4px; font-size: .875em;
        }
        .documentation-content pre {
            background: #f4f5fa; padding: .75rem 1rem; border-radius: 6px; overflow-x: auto;
        }
        .documentation-content ul, .documentation-content ol { padding-left: 1.5rem; }
    </style>
@endsection
