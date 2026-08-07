{{--
    Shared print letterhead partial.
    Expects: $business, $branch (nullable), $title, $doc_no, $doc_date, $reference (assoc array, optional)
--}}
<div class="print-header">
    <div class="company-block">
        <img class="company-logo"
            src="{{ !empty($business->logo) ? asset('public/uploads/business/' . $business->logo) : asset('public/assets/img/no-image.png') }}"
            alt="Logo">
        <div>
            <p class="company-name">{{ $business->name ?? 'N/A' }}</p>
            <p class="company-meta">
                {{ collect([$business->address ?? null, $business->city ?? null, $business->state ?? null, $business->country ?? null])->filter()->implode(', ') }}
            </p>
            <p class="company-meta">
                @if (!empty($business->phone))
                    Tel: {{ $business->phone }}
                @endif
                @if (!empty($business->email))
                    &nbsp;&nbsp;Email: {{ $business->email }}
                @endif
            </p>
            @if (!empty($branch))
                <p class="branch-meta">
                    <strong>Branch:</strong> {{ $branch->name }}
                    @if (!empty($branch->address))
                        - {{ $branch->address }}
                    @endif
                </p>
            @endif
        </div>
    </div>

    <div class="doc-block">
        <p class="doc-title">{{ $title }}</p>
        <p class="doc-meta"><strong>Document No:</strong> {{ $doc_no ?? 'N/A' }}</p>
        <p class="doc-meta"><strong>Date:</strong> {{ $doc_date ?? 'N/A' }}</p>
        @foreach ($reference ?? [] as $label => $value)
            <p class="doc-meta"><strong>{{ $label }}:</strong> {{ $value ?? 'N/A' }}</p>
        @endforeach
    </div>
</div>

<p class="printed-by">Printed by {{ Auth::user()->name ?? 'N/A' }} on {{ localDateTime(now()) }}</p>
