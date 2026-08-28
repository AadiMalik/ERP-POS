<?php
/**
 * Part 2: blogs, media, comments, inquiries, settings, registrations
 */
$base = dirname(__DIR__);
$V = $base . '/resources/views/admin/intro';
$J = $base . '/public/assets/js/admin';

function w($path, $c) {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($path, $c);
    echo basename(dirname($path)) . '/' . basename($path) . "\n";
}

// ===================== BLOGS =====================
w("$V/blogs/model/create.blade.php", <<<'HTML'
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_blog_form" name="intro_blog_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="intro_blog_id" id="intro_blog_id">
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" required></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" id="intro_blog_category_id" name="intro_blog_category_id">
                                <option value="">-- Select --</option>
                                @foreach($categories as $c)
                                <option value="{{ $c->intro_blog_category_id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                                <option value="scheduled">Scheduled</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Featured</label>
                            <select class="form-select" id="is_featured" name="is_featured"><option value="0">No</option><option value="1">Yes</option></select>
                        </div>
                    </div>
                    <div class="mb-3"><label class="form-label">Excerpt</label><textarea class="form-control" id="excerpt" name="excerpt" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="8" placeholder="HTML or JSON content blocks"></textarea></div>
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <select class="form-select" id="tag_ids" name="tag_ids[]" multiple>
                            @foreach($tags as $t)
                            <option value="{{ $t->intro_blog_tag_id }}">{{ $t->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Reading Time (min)</label><input type="number" class="form-control" id="reading_time" name="reading_time"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Published At</label><input type="datetime-local" class="form-control" id="published_at" name="published_at"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Featured Image</label><input type="file" class="form-control" id="featured_image" name="featured_image" accept="image/*"><img id="featured_image_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
                    <hr>
                    <div class="mb-3"><label class="form-label">SEO Title</label><input type="text" class="form-control" id="seo_title" name="seo_title"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Meta Keywords</label><input type="text" class="form-control" id="meta_keywords" name="meta_keywords"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Canonical URL</label><input type="text" class="form-control" id="canonical_url" name="canonical_url"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">OG Image</label><input type="file" class="form-control" id="og_image" name="og_image" accept="image/*"><img id="og_image_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">OG Title</label><input type="text" class="form-control" id="og_title" name="og_title"></div>
                    <div class="mb-3"><label class="form-label">OG Description</label><textarea class="form-control" id="og_description" name="og_description" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
HTML);

w("$V/blogs/index.blade.php", <<<'BLADE'
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Blog Posts</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <select id="filter_status" class="form-select" style="width:160px">
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                    <option value="scheduled">Scheduled</option>
                </select>
                <select id="filter_category" class="form-select" style="width:200px">
                    <option value="">All Categories</option>
                    @foreach($categories as $c)
                    <option value="{{ $c->intro_blog_category_id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <a href="javascript:void(0)" id="createIntroBlog" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Add New</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_blogs_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Published</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.blogs.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_blogs.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'title',name:'title'},
{data:'category',name:'category',sortable:false,searchable:false},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'published_at',name:'published_at'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'blogs/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_blogs_table',
'variable' => 'intro_blogs_table',
'params' => "status_filter:$('#filter_status').val(),category_id:$('#filter_category').val()",
])
<script>
$(function(){
    $('#tag_ids').select2({dropdownParent:$('#ajaxModel'), width:'100%'});
    $('#filter_status,#filter_category').on('change', function(){ initDataTableintro_blogs_table(); });
});
</script>
@endsection
BLADE);

w("$J/intro_blogs.js", <<<'JS'
function previewImage(inputId, previewId) {
    $("#" + inputId).on("change", function () {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $("#" + previewId).attr("src", e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
}
previewImage("featured_image", "featured_image_preview");
previewImage("og_image", "og_image_preview");

function toLocalInput(dt) {
    if (!dt) return '';
    let d = new Date(dt);
    if (isNaN(d.getTime())) return String(dt).slice(0, 16);
    let pad = n => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

$("#createIntroBlog").click(function () {
    $("#intro_blog_form")[0].reset();
    $("#intro_blog_id").val('');
    $("#tag_ids").val(null).trigger('change');
    $("#featured_image_preview,#og_image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Blog Post");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editIntroItem",
    url: url_local + "/admin/intro/blogs",
    suffix: "",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_blog_id").val(data.intro_blog_id);
        $("#title").val(data.title);
        $("#slug").val(data.slug);
        $("#intro_blog_category_id").val(data.intro_blog_category_id);
        $("#status").val(data.status || 'draft');
        $("#is_featured").val(data.is_featured ? 1 : 0);
        $("#excerpt").val(data.excerpt);
        $("#content").val(typeof data.content === 'string' ? data.content : JSON.stringify(data.content || '', null, 2));
        let tags = (data.tags || []).map(t => t.intro_blog_tag_id || t.id);
        $("#tag_ids").val(tags).trigger('change');
        $("#reading_time").val(data.reading_time);
        $("#published_at").val(toLocalInput(data.published_at));
        $("#seo_title").val(data.seo_title);
        $("#meta_description").val(data.meta_description);
        $("#meta_keywords").val(data.meta_keywords);
        $("#canonical_url").val(data.canonical_url);
        $("#og_title").val(data.og_title);
        $("#og_description").val(data.og_description);
        if (data.featured_image_url) { $("#featured_image_preview").attr("src", data.featured_image_url).show(); } else { $("#featured_image_preview").hide(); }
        if (data.og_image_url) { $("#og_image_preview").attr("src", data.og_image_url).show(); } else { $("#og_image_preview").hide(); }
        $("#modelHeading").html("Edit Blog Post");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_blog_form",
    url: url_local + "/admin/intro/blogs",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_blogs_table(); },
    beforeSubmit: function () {
        if (!$("#title").val()) { errorMessage("Title is required"); return false; }
        return true;
    }
});

deleteRecord({
    buttonClass: "#deleteIntroItem",
    url: url_local + "/admin/intro/blogs",
    tableCallback: function () { initDataTableintro_blogs_table(); }
});
JS);

// ===================== MEDIA =====================
w("$V/media/model/create.blade.php", <<<'HTML'
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading">Upload Media</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="intro_media_form" name="intro_media_form" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3"><label class="form-label">File <span class="text-danger">*</span></label><input type="file" class="form-control" id="file" name="file" required></div>
                    <div class="mb-3"><label class="form-label">Collection</label><input type="text" class="form-control" id="collection" name="collection" value="general"></div>
                    <div class="mb-3"><label class="form-label">Alt Text</label><input type="text" class="form-control" id="alt_text" name="alt_text"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>
HTML);

w("$V/media/index.blade.php", <<<'BLADE'
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Media</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <input type="text" id="filter_collection" class="form-control" style="width:200px" placeholder="Filter collection">
            <a href="javascript:void(0)" id="createIntroMedia" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>Upload</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_media_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Preview</th>
                            <th>Original Name</th>
                            <th>Collection</th>
                            <th>Mime</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.media.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_media.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'preview',name:'preview',sortable:false,searchable:false},
{data:'original_name',name:'original_name'},
{data:'collection',name:'collection'},
{data:'mime_type',name:'mime_type'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'media/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_media_table',
'variable' => 'intro_media_table',
'params' => "collection:$('#filter_collection').val()",
])
<script>$(function(){ $('#filter_collection').on('change keyup', function(){ initDataTableintro_media_table(); }); });</script>
@endsection
BLADE);

w("$J/intro_media.js", <<<'JS'
$("#createIntroMedia").click(function () {
    $("#intro_media_form")[0].reset();
    $("#collection").val('general');
    $("#saveBtn").show();
    $("#modelHeading").html("Upload Media");
    $("#ajaxModel").modal("show");
});

saveRecord({
    formId: "#intro_media_form",
    url: url_local + "/admin/intro/media",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_media_table(); },
    beforeSubmit: function () {
        if (!$("#file")[0].files.length) { errorMessage("Select a file"); return false; }
        return true;
    }
});

deleteRecord({
    buttonClass: "#deleteIntroItem",
    url: url_local + "/admin/intro/media",
    tableCallback: function () { initDataTableintro_media_table(); }
});
JS);

// ===================== BLOG COMMENTS =====================
w("$V/blog_comments/index.blade.php", <<<'BLADE'
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Blog Comments</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="spam">Spam</option>
                <option value="hidden">Hidden</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_comments_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Blog</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_blog_comments.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'email',name:'email'},
{data:'blog_title',name:'blog_title',sortable:false,searchable:false},
{data:'comment',name:'comment'},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'blog-comments/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_comments_table',
'variable' => 'intro_comments_table',
'params' => "status_filter:$('#filter_status').val()",
])
<script>$(function(){ $('#filter_status').on('change', function(){ initDataTableintro_comments_table(); }); });</script>
@endsection
BLADE);

w("$J/intro_blog_comments.js", <<<'JS'
function moderateComment(id, status) {
    ajaxRequest({
        url: url_local + "/admin/intro/blog-comments/" + id + "/moderate",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: status }
    }).then(function (response) {
        successMessage(response.Message || "Updated");
        initDataTableintro_comments_table();
    }).catch(function (err) {
        errorMessage(err.Message || "Failed");
    });
}

$("body").on("click", "#approveComment", function () { moderateComment($(this).data("id"), "approved"); });
$("body").on("click", "#rejectComment", function () { moderateComment($(this).data("id"), "rejected"); });
$("body").on("click", "#spamComment", function () { moderateComment($(this).data("id"), "spam"); });

deleteRecord({
    buttonClass: "#deleteIntroItem",
    url: url_local + "/admin/intro/blog-comments",
    tableCallback: function () { initDataTableintro_comments_table(); }
});
JS);

// ===================== CONTACT INQUIRIES =====================
w("$V/contact_inquiries/model/view.blade.php", <<<'HTML'
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading">Inquiry</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="intro_contact_inquiry_id">
                <div class="mb-2"><strong>From:</strong> <span id="inq_name"></span> &lt;<span id="inq_email"></span>&gt;</div>
                <div class="mb-2"><strong>Subject:</strong> <span id="inq_subject"></span></div>
                <div class="mb-3 p-3 border rounded bg-light" id="inq_message" style="white-space:pre-wrap"></div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select id="inq_status" class="form-select">
                        <option value="new">New</option>
                        <option value="read">Read</option>
                        <option value="replied">Replied</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>
                <div id="inq_replies" class="mb-3"></div>
                <div class="mb-3">
                    <label class="form-label">Reply</label>
                    <textarea id="reply_message" class="form-control" rows="4"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnUpdateStatus" class="btn btn-outline-primary">Update Status</button>
                <button type="button" id="btnSendReply" class="btn btn-primary">Send Reply</button>
            </div>
        </div>
    </div>
</div>
HTML);

w("$V/contact_inquiries/index.blade.php", <<<'BLADE'
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Contact Inquiries</h4>
    <div class="card">
        <div class="card-header">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="new">New</option>
                <option value="read">Read</option>
                <option value="replied">Replied</option>
                <option value="closed">Closed</option>
            </select>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_inquiries_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.contact_inquiries.model.view')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_contact_inquiries.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'name',name:'name'},
{data:'email',name:'email'},
{data:'subject',name:'subject'},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'date_created',name:'date_created'},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'contact-inquiries/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_inquiries_table',
'variable' => 'intro_inquiries_table',
'params' => "status_filter:$('#filter_status').val()",
])
<script>$(function(){ $('#filter_status').on('change', function(){ initDataTableintro_inquiries_table(); }); });</script>
@endsection
BLADE);

w("$J/intro_contact_inquiries.js", <<<'JS'
$("body").on("click", "#viewIntroInquiry", function () {
    let id = $(this).data("id");
    ajaxRequest({ url: url_local + "/admin/intro/contact-inquiries/" + id }).then(function (response) {
        let d = response.Data;
        $("#intro_contact_inquiry_id").val(d.intro_contact_inquiry_id);
        $("#inq_name").text(d.name);
        $("#inq_email").text(d.email);
        $("#inq_subject").text(d.subject || '-');
        $("#inq_message").text(d.message || '');
        $("#inq_status").val(d.status);
        $("#reply_message").val('');
        let html = '';
        (d.replies || []).forEach(function (r) {
            html += '<div class="border rounded p-2 mb-2"><small class="text-muted">' + (r.date_created || '') + ' · ' + (r.send_status || '') + '</small><div>' + (r.reply_message || '') + '</div></div>';
        });
        $("#inq_replies").html(html || '<p class="text-muted mb-0">No replies yet.</p>');
        $("#ajaxModel").modal("show");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnUpdateStatus").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/status",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: $("#inq_status").val() }
    }).then(function (r) {
        successMessage(r.Message || "Status updated");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnSendReply").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    let msg = $("#reply_message").val();
    if (!msg) { errorMessage("Reply message is required"); return; }
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/reply",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), reply_message: msg }
    }).then(function (r) {
        successMessage(r.Message || "Reply sent");
        $("#ajaxModel").modal("hide");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

deleteRecord({
    buttonClass: "#deleteIntroItem",
    url: url_local + "/admin/intro/contact-inquiries",
    tableCallback: function () { initDataTableintro_inquiries_table(); }
});
JS);

// ===================== BUSINESS REGISTRATIONS =====================
w("$V/business_registrations/model/view.blade.php", <<<'HTML'
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Business Registration</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="intro_business_registration_id">
                <div class="row mb-2"><div class="col-4 text-muted">Business</div><div class="col-8" id="reg_business_name"></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Owner</div><div class="col-8"><span id="reg_owner_name"></span> · <span id="reg_owner_email"></span> · <span id="reg_owner_phone"></span></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Type / City</div><div class="col-8"><span id="reg_business_type"></span> · <span id="reg_city"></span></div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Package</div><div class="col-8"><span id="reg_package"></span> (<span id="reg_cycle"></span>)</div></div>
                <div class="row mb-2"><div class="col-4 text-muted">Subscription</div><div class="col-8" id="reg_sub_status"></div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Notes</div><div class="col-8" id="reg_notes" style="white-space:pre-wrap"></div></div>
                <div class="mb-3">
                    <label class="form-label">Registration Status</label>
                    <select id="reg_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="under_review">Under Review</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                        <option value="activated">Activated</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" id="btnRegStatus" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>
HTML);

w("$V/business_registrations/index.blade.php", <<<'BLADE'
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Intro Business Registrations</h4>
    <div class="card">
        <div class="card-header d-flex gap-2 flex-wrap">
            <select id="filter_status" class="form-select" style="width:180px">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="under_review">Under Review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="activated">Activated</option>
            </select>
            <input type="text" id="filter_search" class="form-control" style="width:220px" placeholder="Search name / email">
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="intro_registrations_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Business</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Package</th>
                            <th>Cycle</th>
                            <th>Sub Status</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.intro.business_registrations.model.view')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/intro_business_registrations.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data:'business_name',name:'business_name'},
{data:'owner_name',name:'owner_name'},
{data:'owner_email',name:'owner_email'},
{data:'package_name',name:'package_name',sortable:false,searchable:false},
{data:'billing_cycle',name:'billing_cycle'},
{data:'subscription_status',name:'subscription_status',sortable:false,searchable:false},
{data:'status_badge',name:'status',sortable:false,searchable:false},
{data:'action',name:'action',sortable:false,searchable:false},
",
'route' => 'business-registrations/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'intro_registrations_table',
'variable' => 'intro_registrations_table',
'params' => "status_filter:$('#filter_status').val(),search:$('#filter_search').val()",
])
<script>
$(function(){
    $('#filter_status').on('change', function(){ initDataTableintro_registrations_table(); });
    let t; $('#filter_search').on('keyup', function(){ clearTimeout(t); t=setTimeout(function(){ initDataTableintro_registrations_table(); }, 400); });
});
</script>
@endsection
BLADE);

w("$J/intro_business_registrations.js", <<<'JS'
$("body").on("click", "#viewIntroRegistration", function () {
    let id = $(this).data("id");
    ajaxRequest({ url: url_local + "/admin/intro/business-registrations/" + id }).then(function (response) {
        let d = response.Data;
        $("#intro_business_registration_id").val(d.intro_business_registration_id);
        $("#reg_business_name").text(d.business_name || '-');
        $("#reg_owner_name").text(d.owner_name || '-');
        $("#reg_owner_email").text(d.owner_email || '-');
        $("#reg_owner_phone").text(d.owner_phone || '-');
        $("#reg_business_type").text(d.business_type || '-');
        $("#reg_city").text(d.city || '-');
        $("#reg_package").text((d.package && d.package.name) || '-');
        $("#reg_cycle").text(d.billing_cycle || '-');
        $("#reg_sub_status").text((d.business && d.business.current_subscription && d.business.current_subscription.status) || '-');
        $("#reg_notes").text(d.notes || '-');
        $("#reg_status").val(d.status || 'pending');
        $("#ajaxModel").modal("show");
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnRegStatus").click(function () {
    let id = $("#intro_business_registration_id").val();
    ajaxRequest({
        url: url_local + "/admin/intro/business-registrations/" + id + "/status",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: $("#reg_status").val() }
    }).then(function (r) {
        successMessage(r.Message || "Status updated");
        $("#ajaxModel").modal("hide");
        initDataTableintro_registrations_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});
JS);

// ===================== WEBSITE SETTINGS =====================
w("$V/website_settings/index.blade.php", <<<'BLADE'
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
                        <div class="mb-3"><label class="form-label">Branch</label><input type="text" class="form-control" name="bank_branch" value="{{ $map['bank_branch'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Branch Code</label><input type="text" class="form-control" name="bank_branch_code" value="{{ $map['bank_branch_code'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">SWIFT</label><input type="text" class="form-control" name="bank_swift" value="{{ $map['bank_swift'] ?? '' }}"></div>
                        <div class="mb-3"><label class="form-label">Bank Instructions (JSON array)</label><textarea class="form-control font-monospace" name="bank_instructions" rows="5">{{ $map['bank_instructions'] ?? '[]' }}</textarea></div>
                    </div>
                </div>
                <div class="card mb-4">
                    <div class="card-header"><strong>Assets</strong></div>
                    <div class="card-body">
                        <div class="mb-3"><label class="form-label">Logo</label><input type="file" class="form-control" name="logo" accept="image/*"></div>
                        <div class="mb-3"><label class="form-label">Logo Light</label><input type="file" class="form-control" name="logo_light" accept="image/*"></div>
                        <div class="mb-3"><label class="form-label">Favicon</label><input type="file" class="form-control" name="favicon" accept="image/*"></div>
                        <div class="mb-3"><label class="form-label">OG Image</label><input type="file" class="form-control" name="og_image" accept="image/*"></div>
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
BLADE);

w("$J/intro_website_settings.js", <<<'JS'
$("#intro_settings_form").on("submit", function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    $("#saveBtn").prop("disabled", true);
    ajaxRequest({
        url: url_local + "/admin/intro/website-settings",
        method: "POST",
        data: formData,
        processData: false,
        contentType: false
    }).then(function (response) {
        successMessage(response.Message || "Settings saved");
        $("#saveBtn").prop("disabled", false);
    }).catch(function (err) {
        errorMessage(err.Message || "Save failed");
        $("#saveBtn").prop("disabled", false);
    });
});
JS);

echo "Part 2 complete\n";
