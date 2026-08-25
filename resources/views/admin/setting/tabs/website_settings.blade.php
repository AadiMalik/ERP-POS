@php
    $wt = $website_theme_setting;
    $social = is_array($wt->social_links ?? null) ? $wt->social_links : [];
@endphp

<h5 class="mb-3">Website Settings</h5>
<p class="text-muted">
    General storefront configuration - favicon, business hours, SEO metadata and social/contact links shown on the
    public website. Business name, logo, email, phone and address come from the
    <a href="{{ url('admin/business') }}">Business profile</a>, and currency comes from the Accounting tab.
</p>

<form id="websiteSettingsForm" onsubmit="event.preventDefault(); saveWebsiteSettings(this);" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header"><strong>General</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Favicon</label>
                <input type="file" class="form-control" name="favicon" accept="image/*">
                @if (!empty($wt->favicon))
                    <img src="{{ asset('uploads/website/' . $wt->favicon) }}" alt="Favicon" style="height:32px;width:32px;object-fit:contain;" class="mt-2">
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
                    <img src="{{ asset('uploads/website/' . $wt->og_image) }}" alt="OG Image" style="height:60px;margin-top:8px;" class="d-block">
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>Social Links</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">Facebook</label>
                <input type="url" class="form-control" name="social_links[facebook]" value="{{ $social['facebook'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Instagram</label>
                <input type="url" class="form-control" name="social_links[instagram]" value="{{ $social['instagram'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Twitter / X</label>
                <input type="url" class="form-control" name="social_links[twitter]" value="{{ $social['twitter'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">Pinterest</label>
                <input type="url" class="form-control" name="social_links[pinterest]" value="{{ $social['pinterest'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">YouTube</label>
                <input type="url" class="form-control" name="social_links[youtube]" value="{{ $social['youtube'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">TikTok</label>
                <input type="url" class="form-control" name="social_links[tiktok]" value="{{ $social['tiktok'] ?? '' }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">LinkedIn</label>
                <input type="url" class="form-control" name="social_links[linkedin]" value="{{ $social['linkedin'] ?? '' }}">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Save Website Settings</button>
</form>
