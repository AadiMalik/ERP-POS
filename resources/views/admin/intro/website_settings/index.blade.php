@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Website Settings</h4>
    <form id="intro_settings_form" enctype="multipart/form-data">
        <div class="row">
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header"><strong>Brand</strong></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Brand Name</label><input type="text" class="form-control" name="brand_name" value="{{ $map['brand_name'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Brand Description</label><textarea class="form-control" name="brand_description" rows="2">{{ $map['brand_description'] ?? '' }}</textarea></div>
                        <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="{{ $map['email'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="{{ $map['phone'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Phone Hours</label><input type="text" class="form-control" name="phone_hours" value="{{ $map['phone_hours'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Copyright Note</label><input type="text" class="form-control" name="copyright_note" value="{{ $map['copyright_note'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Currency</label><input type="text" class="form-control" name="currency" value="{{ $map['currency'] ?? 'PKR' }}"></div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><strong>SEO / Comments</strong></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Default SEO Title</label><input type="text" class="form-control" name="default_seo_title" value="{{ $map['default_seo_title'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Default Meta Description</label><textarea class="form-control" name="default_meta_description" rows="2">{{ $map['default_meta_description'] ?? '' }}</textarea></div>
                        <div class="mb-3"><label class="form-label">Comments Enabled</label><select class="form-select" name="comments_enabled"><option value="1" @if(($map['comments_enabled'] ?? '1')=='1') selected @endif>Yes</option><option value="0" @if(($map['comments_enabled'] ?? '')=='0') selected @endif>No</option></select></div>
                        <div class="mb-3"><label class="form-label">Comments Require Moderation</label><select class="form-select" name="comments_require_moderation"><option value="1" @if(($map['comments_require_moderation'] ?? '1')=='1') selected @endif>Yes</option><option value="0" @if(($map['comments_require_moderation'] ?? '')=='0') selected @endif>No</option></select></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card mb-4">
                    <div class="card-header"><strong>Bank Transfer</strong></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Bank Name</label><input type="text" class="form-control" name="bank_name" value="{{ $map['bank_name'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Account Title</label><input type="text" class="form-control" name="bank_account_title" value="{{ $map['bank_account_title'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Account Number</label><input type="text" class="form-control" name="bank_account_number" value="{{ $map['bank_account_number'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">IBAN</label><input type="text" class="form-control" name="bank_iban" value="{{ $map['bank_iban'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">{{ __('common.branch') }}</label><input type="text" class="form-control" name="bank_branch" value="{{ $map['bank_branch'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Branch Code</label><input type="text" class="form-control" name="bank_branch_code" value="{{ $map['bank_branch_code'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">SWIFT</label><input type="text" class="form-control" name="bank_swift" value="{{ $map['bank_swift'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Bank Instructions (JSON array)</label><textarea class="form-control font-monospace" name="bank_instructions" rows="5">{{ $map['bank_instructions'] ?? '[]' }}</textarea></div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><strong>Social Media</strong></div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">These links appear under the logo in the intro website footer.</p>
                        <div class="mb-3"><label class="form-label">Facebook</label><input type="url" class="form-control" name="social_facebook" value="{{ $map['social_facebook'] ?? '' }}" placeholder="https://facebook.com/..."></div>
                        <div class="mb-3"><label class="form-label">Instagram</label><input type="url" class="form-control" name="social_instagram" value="{{ $map['social_instagram'] ?? '' }}" placeholder="https://instagram.com/..."></div>
                        <div class="mb-3"><label class="form-label">LinkedIn</label><input type="url" class="form-control" name="social_linkedin" value="{{ $map['social_linkedin'] ?? '' }}" placeholder="https://linkedin.com/..."></div>
                        <div class="mb-3"><label class="form-label">Twitter / X</label><input type="url" class="form-control" name="social_twitter" value="{{ $map['social_twitter'] ?? '' }}" placeholder="https://x.com/..."></div>
                        <div class="mb-3"><label class="form-label">YouTube</label><input type="url" class="form-control" name="social_youtube" value="{{ $map['social_youtube'] ?? '' }}" placeholder="https://youtube.com/..."></div>
                        <div class="mb-3"><label class="form-label">GitHub</label><input type="url" class="form-control" name="social_github" value="{{ $map['social_github'] ?? '' }}" placeholder="https://github.com/..."></div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><strong>Assets</strong></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" name="logo" accept="image/*">
                            @if(!empty($map['logo']))
                                <img src="{{ asset('public/uploads/intro/settings/' . $map['logo']) }}" alt="Logo" class="mt-2" style="max-height:48px;">
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo Light</label>
                            <input type="file" class="form-control" name="logo_light" accept="image/*">
                            @if(!empty($map['logo_light']))
                                <img src="{{ asset('public/uploads/intro/settings/' . $map['logo_light']) }}" alt="Logo light" class="mt-2" style="max-height:48px;">
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Favicon</label>
                            <input type="file" class="form-control" name="favicon" accept="image/*">
                            @if(!empty($map['favicon']))
                                <img src="{{ asset('public/uploads/intro/settings/' . $map['favicon']) }}" alt="Favicon" class="mt-2" style="max-height:32px;">
                            @endif
                        </div>
                        <div class="mb-3">
                            <label class="form-label">OG Image</label>
                            <input type="file" class="form-control" name="og_image" accept="image/*">
                            @if(!empty($map['og_image']))
                                <img src="{{ asset('public/uploads/intro/settings/' . $map['og_image']) }}" alt="OG" class="mt-2" style="max-height:64px;">
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button type="submit" id="saveBtn" class="btn btn-primary">Save Settings</button>
    </form>
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_website_settings.js') }}"></script>
@endsection