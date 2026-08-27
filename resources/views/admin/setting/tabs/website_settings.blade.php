@php
    $wt = $website_theme_setting;
@endphp

<h5 class="mb-3">Website Settings</h5>
<p class="text-muted">
    General storefront configuration — browser tab icon (favicon), business hours and SEO metadata shown on the public website.
    If no tab icon is uploaded, the Dukanaz default icon is used automatically.
    Business name, logo, email, phone and address come from the <a href="{{ url('admin/business') }}">Business profile</a>,
    currency comes from the Accounting tab, and social links are managed under
    <a href="{{ url('admin/social-media') }}">Website CMS &gt; Social Media</a>.
</p>

<form id="websiteSettingsForm" onsubmit="event.preventDefault(); saveWebsiteSettings(this);" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header"><strong>General</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Tab Icon (Favicon)</label>
                <input type="file" class="form-control" name="favicon" accept="image/*">
                <small class="text-muted d-block mt-1">
                    Shown in the browser tab on your public website.
                    If you leave this empty, the Dukanaz default icon is used.
                </small>
                @if (!empty($wt->favicon))
                    <img src="{{ asset('public/uploads/website/' . $wt->favicon) }}" alt="Tab icon" style="height:32px;width:32px;object-fit:contain;" class="mt-2">
                @else
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <img src="{{ asset('public/assets/img/favicon/favicon-32.png') }}" alt="Dukanaz default tab icon" style="height:32px;width:32px;object-fit:contain;">
                        <small class="text-muted">Currently using Dukanaz default</small>
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">Business Hours</label>
                <input type="text" class="form-control" name="business_hours" value="{{ $wt->business_hours }}"
                    placeholder="Mon - Sun, 9am - 9pm">
            </div>
            <div class="col-md-6">
                <label class="form-label">WhatsApp Number</label>
                <input type="text" class="form-control" name="whatsapp_number" value="{{ $wt->whatsapp_number }}"
                    placeholder="+1 555 010 2024">
                <small class="text-muted">Used for the storefront's click-to-chat WhatsApp link.</small>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>SEO</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-12">
                <label class="form-label">Default SEO Title</label>
                <input type="text" class="form-control" name="seo_title" value="{{ $wt->seo_title }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Default SEO Description</label>
                <textarea class="form-control" name="seo_description" rows="3">{{ $wt->seo_description }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">SEO Keywords</label>
                <input type="text" class="form-control" name="seo_keywords" value="{{ $wt->seo_keywords }}"
                    placeholder="Comma-separated keywords">
            </div>
            <div class="col-md-6">
                <label class="form-label">OG Image</label>
                <input type="file" class="form-control" name="og_image" accept="image/*">
                @if (!empty($wt->og_image))
                    <img src="{{ asset('public/uploads/website/' . $wt->og_image) }}" alt="OG Image" style="height:60px;margin-top:8px;" class="d-block">
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Delivery</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="free_delivery_enabled"
                        name="free_delivery_enabled" value="1" {{ $wt->free_delivery_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="free_delivery_enabled">Enable Free Delivery</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Minimum Order Amount for Free Delivery</label>
                <input type="number" step="0.01" min="0" class="form-control" name="free_delivery_min_amount"
                    value="{{ $wt->free_delivery_min_amount }}">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Bank Transfer Details (Website)</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Bank Name</label>
                <input type="text" class="form-control" name="bank_name" value="{{ $wt->bank_name }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Account Title</label>
                <input type="text" class="form-control" name="bank_account_title" value="{{ $wt->bank_account_title }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Account Number</label>
                <input type="text" class="form-control" name="bank_account_number" value="{{ $wt->bank_account_number }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">IBAN</label>
                <input type="text" class="form-control" name="bank_iban" value="{{ $wt->bank_iban }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Bank Branch</label>
                <input type="text" class="form-control" name="bank_branch" value="{{ $wt->bank_branch }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">SWIFT / BIC</label>
                <input type="text" class="form-control" name="bank_swift_code" value="{{ $wt->bank_swift_code }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">Payment Instructions</label>
                <textarea class="form-control" name="bank_instructions" rows="3"
                    placeholder="Shown to customers at website checkout when they select Bank Transfer.">{{ $wt->bank_instructions }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Website Settings</button>
</form>
