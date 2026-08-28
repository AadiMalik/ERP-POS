<?php
/**
 * Generate complete Intro CMS admin Blade + JS CRUD UI.
 * php tools/generate_intro_admin_ui.php
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

function modal($formId, $pk, $body, $multipart = false, $size = 'modal-lg') {
    $enc = $multipart ? ' enctype="multipart/form-data"' : '';
    return <<<HTML
<div class="modal fade" id="ajaxModel" data-bs-backdrop="static" tabindex="-1">
    <div class="modal-dialog {$size}">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modelHeading"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="{$formId}" name="{$formId}"{$enc}>
                <div class="modal-body">
                    <input type="hidden" name="{$pk}" id="{$pk}">
{$body}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" id="saveBtn" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
HTML;
}

function indexBlade($title, $createHtml, $tableId, $ths, $modalInclude, $js, $cols, $route, $var, $headerExtra = '', $params = '', $extraJs = '') {
    $paramsLine = $params !== '' ? "\n'params' => \"{$params}\"," : '';
    $thsHtml = '';
    foreach ($ths as $th) $thsHtml .= "                            <th>{$th}</th>\n";
    return <<<BLADE
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{$title}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap align-items-center">{$headerExtra}</div>
            {$createHtml}
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-2">
                <table id="{$tableId}" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
{$thsHtml}                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    {$modalInclude}
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/{$js}') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{$cols}
",
'route' => '{$route}',
'buttons' => false,
'pageLength' => 25,
'class' => '{$tableId}',
'variable' => '{$var}',{$paramsLine}
])
{$extraJs}
@endsection
BLADE;
}

function stdCrudJs($cfg) {
    $create = $cfg['create'];
    $form = $cfg['form'];
    $pk = $cfg['pk'];
    $path = $cfg['path'];
    $table = $cfg['table'];
    $label = $cfg['label'];
    $fill = $cfg['fill'];
    $reset = $cfg['reset'] ?? '';
    $validate = $cfg['validate'] ?? 'return true;';
    $status = !empty($cfg['status']);
    $preview = $cfg['preview'] ?? '';
    $statusJs = $status ? <<<JS

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/{$path}/change-status",
    tableCallback: function () { initDataTable{$table}(); }
});
JS : '';

    return <<<JS
{$preview}
$("#{$create}").click(function () {
    $("#{$form}")[0].reset();
    $("#{$pk}").val('');
    {$reset}
    $("#saveBtn").show();
    $("#modelHeading").html("Add {$label}");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editIntroItem",
    url: url_local + "/admin/{$path}",
    suffix: "",
    onSuccess: function (response) {
        let data = response.Data;
        $("#{$pk}").val(data.{$pk});
{$fill}
        $("#modelHeading").html("Edit {$label}");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#{$form}",
    url: url_local + "/admin/{$path}",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTable{$table}(); },
    beforeSubmit: function () {
        {$validate}
    }
});
{$statusJs}

deleteRecord({
    buttonClass: "#deleteIntroItem",
    url: url_local + "/admin/{$path}",
    tableCallback: function () { initDataTable{$table}(); }
});
JS;
}

$previewHelper = <<<'JS'
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
JS;

$addBtn = fn($id, $text = 'Add New') => '<a href="javascript:void(0)" id="' . $id . '" class="btn rounded-pill btn-primary"><i class="fa fa-plus me-1"></i>' . $text . '</a>';
$emptyCreate = '<span></span>';

// ===================== MODULES =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="3"></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Category</label><input type="text" class="form-control" id="category" name="category" placeholder="rail / sales / inventory"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Featured</label><select class="form-select" id="is_featured" name="is_featured"><option value="0">No</option><option value="1">Yes</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Icon</label><input type="file" class="form-control" id="icon" name="icon" accept="image/*"><img id="icon_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
HTML;
w("$V/modules/model/create.blade.php", modal('intro_module_form', 'intro_module_id', $body, true));
w("$V/modules/index.blade.php", indexBlade('Intro Modules', $addBtn('createIntroModule'), 'intro_modules_table', ['Name','Category','Order','Status','Action'], "@include('admin.intro.modules.model.create')", 'intro_modules.js', "{data:'name',name:'name'},{data:'category',name:'category'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'modules/data', 'intro_modules_table'));
w("$J/intro_modules.js", $previewHelper . "\npreviewImage('icon','icon_preview');\npreviewImage('image','image_preview');\n" . stdCrudJs([
    'create'=>'createIntroModule','form'=>'intro_module_form','pk'=>'intro_module_id','path'=>'intro/modules','table'=>'intro_modules_table','label'=>'Module','status'=>true,
    'reset'=>'$("#icon_preview,#image_preview").hide();',
    'validate'=>'if(!$("#name").val()){errorMessage("Name is required");return false;}return true;',
    'fill'=>"        $(\"#name\").val(data.name);\n        $(\"#slug\").val(data.slug);\n        $(\"#description\").val(data.description);\n        $(\"#category\").val(data.category);\n        $(\"#display_order\").val(data.display_order);\n        $(\"#is_featured\").val(data.is_featured?1:0);\n        $(\"#status\").val(data.status||'active');\n        if(data.icon_url){\$(\"#icon_preview\").attr(\"src\",data.icon_url).show();}else{\$(\"#icon_preview\").hide();}\n        if(data.image_url){\$(\"#image_preview\").attr(\"src\",data.image_url).show();}else{\$(\"#image_preview\").hide();}",
]));

// ===================== BLOG CATEGORIES =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">SEO Title</label><input type="text" class="form-control" id="seo_title" name="seo_title"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea></div>
HTML;
w("$V/blog_categories/model/create.blade.php", modal('intro_blog_category_form', 'intro_blog_category_id', $body));
w("$V/blog_categories/index.blade.php", indexBlade('Intro Blog Categories', $addBtn('createIntroBlogCategory'), 'intro_blog_categories_table', ['Name','Slug','Order','Status','Action'], "@include('admin.intro.blog_categories.model.create')", 'intro_blog_categories.js', "{data:'name',name:'name'},{data:'slug',name:'slug'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'blog-categories/data', 'intro_blog_categories_table'));
w("$J/intro_blog_categories.js", stdCrudJs([
    'create'=>'createIntroBlogCategory','form'=>'intro_blog_category_form','pk'=>'intro_blog_category_id','path'=>'intro/blog-categories','table'=>'intro_blog_categories_table','label'=>'Category','status'=>true,
    'validate'=>'if(!$("#name").val()){errorMessage("Name is required");return false;}return true;',
    'fill'=>"        $(\"#name\").val(data.name);\n        $(\"#slug\").val(data.slug);\n        $(\"#description\").val(data.description);\n        $(\"#display_order\").val(data.display_order);\n        $(\"#status\").val(data.status||'active');\n        $(\"#seo_title\").val(data.seo_title);\n        $(\"#meta_description\").val(data.meta_description);",
]));

// ===================== BLOG TAGS =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
HTML;
w("$V/blog_tags/model/create.blade.php", modal('intro_blog_tag_form', 'intro_blog_tag_id', $body));
w("$V/blog_tags/index.blade.php", indexBlade('Intro Blog Tags', $addBtn('createIntroBlogTag'), 'intro_blog_tags_table', ['Name','Slug','Status','Action'], "@include('admin.intro.blog_tags.model.create')", 'intro_blog_tags.js', "{data:'name',name:'name'},{data:'slug',name:'slug'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'blog-tags/data', 'intro_blog_tags_table'));
w("$J/intro_blog_tags.js", stdCrudJs([
    'create'=>'createIntroBlogTag','form'=>'intro_blog_tag_form','pk'=>'intro_blog_tag_id','path'=>'intro/blog-tags','table'=>'intro_blog_tags_table','label'=>'Tag','status'=>true,
    'validate'=>'if(!$("#name").val()){errorMessage("Name is required");return false;}return true;',
    'fill'=>"        $(\"#name\").val(data.name);\n        $(\"#slug\").val(data.slug);\n        $(\"#status\").val(data.status||'active');",
]));

// ===================== TESTIMONIALS =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Customer Name <span class="text-danger">*</span></label><input type="text" class="form-control" id="customer_name" name="customer_name" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Designation</label><input type="text" class="form-control" id="designation" name="designation"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Business Name</label><input type="text" class="form-control" id="business_name" name="business_name"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Business Type</label><input type="text" class="form-control" id="business_type" name="business_type" placeholder="Retail / Mart / Wholesale"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Review <span class="text-danger">*</span></label><textarea class="form-control" id="review_text" name="review_text" rows="4" required></textarea></div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Rating</label><input type="number" min="1" max="5" class="form-control" id="rating" name="rating" value="5"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
HTML;
w("$V/testimonials/model/create.blade.php", modal('intro_testimonial_form', 'intro_testimonial_id', $body, true));
w("$V/testimonials/index.blade.php", indexBlade('Intro Testimonials', $addBtn('createIntroTestimonial'), 'intro_testimonials_table', ['Customer','Business','Rating','Order','Status','Action'], "@include('admin.intro.testimonials.model.create')", 'intro_testimonials.js', "{data:'customer_name',name:'customer_name'},{data:'business_name',name:'business_name'},{data:'rating',name:'rating'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'testimonials/data', 'intro_testimonials_table'));
w("$J/intro_testimonials.js", $previewHelper . "\npreviewImage('image','image_preview');\n" . stdCrudJs([
    'create'=>'createIntroTestimonial','form'=>'intro_testimonial_form','pk'=>'intro_testimonial_id','path'=>'intro/testimonials','table'=>'intro_testimonials_table','label'=>'Testimonial','status'=>true,
    'reset'=>'$("#image_preview").hide();',
    'validate'=>'if(!$("#customer_name").val()||!$("#review_text").val()){errorMessage("Customer name and review are required");return false;}return true;',
    'fill'=>"        $(\"#customer_name\").val(data.customer_name);\n        $(\"#designation\").val(data.designation);\n        $(\"#business_name\").val(data.business_name);\n        $(\"#business_type\").val(data.business_type);\n        $(\"#review_text\").val(data.review_text);\n        $(\"#rating\").val(data.rating);\n        $(\"#display_order\").val(data.display_order);\n        $(\"#status\").val(data.status||'active');\n        if(data.image_url){\$(\"#image_preview\").attr(\"src\",data.image_url).show();}else{\$(\"#image_preview\").hide();}",
]));

// ===================== NAVIGATION =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Label <span class="text-danger">*</span></label><input type="text" class="form-control" id="label" name="label" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">URL</label><input type="text" class="form-control" id="url" name="url" placeholder="/pricing"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Location</label><select class="form-select" id="location" name="location"><option value="header">Header</option><option value="footer">Footer</option><option value="deck">Deck</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Section Key</label><input type="text" class="form-control" id="section_key" name="section_key"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Match Key</label><input type="text" class="form-control" id="match_key" name="match_key"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Parent ID</label><input type="text" class="form-control" id="parent_id" name="parent_id"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
HTML;
w("$V/navigation/model/create.blade.php", modal('intro_navigation_form', 'intro_navigation_item_id', $body));
w("$V/navigation/index.blade.php", indexBlade('Intro Navigation', $addBtn('createIntroNav'), 'intro_navigation_table', ['Label','URL','Location','Order','Status','Action'], "@include('admin.intro.navigation.model.create')", 'intro_navigation.js', "{data:'label',name:'label'},{data:'url',name:'url'},{data:'location',name:'location'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'navigation/data', 'intro_navigation_table'));
w("$J/intro_navigation.js", stdCrudJs([
    'create'=>'createIntroNav','form'=>'intro_navigation_form','pk'=>'intro_navigation_item_id','path'=>'intro/navigation','table'=>'intro_navigation_table','label'=>'Nav Item','status'=>true,
    'validate'=>'if(!$("#label").val()){errorMessage("Label is required");return false;}return true;',
    'fill'=>"        $(\"#label\").val(data.label);\n        $(\"#url\").val(data.url);\n        $(\"#location\").val(data.location||'header');\n        $(\"#section_key\").val(data.section_key);\n        $(\"#match_key\").val(data.match_key);\n        $(\"#parent_id\").val(data.parent_id);\n        $(\"#display_order\").val(data.display_order);\n        $(\"#status\").val(data.status||'active');",
]));

// ===================== HOMEPAGE SECTIONS =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Section Key <span class="text-danger">*</span></label><input type="text" class="form-control" id="section_key" name="section_key" required placeholder="hero / ticker / faq"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Title</label><input type="text" class="form-control" id="title" name="title"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Subtitle</label><input type="text" class="form-control" id="subtitle" name="subtitle"></div>
                    <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="3"></textarea></div>
                    <div class="mb-3"><label class="form-label">Content JSON</label><textarea class="form-control font-monospace" id="content_json" name="content_json" rows="5" placeholder='{"items":[]}'></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Button Text</label><input type="text" class="form-control" id="button_text" name="button_text"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Button Link</label><input type="text" class="form-control" id="button_link" name="button_link"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3"><label class="form-label">Display Order</label><input type="number" class="form-control" id="display_order" name="display_order" value="0"></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Enabled</label><select class="form-select" id="is_enabled" name="is_enabled"><option value="1">Yes</option><option value="0">No</option></select></div>
                        <div class="col-md-4 mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Image</label><input type="file" class="form-control" id="image" name="image" accept="image/*"><img id="image_preview" src="" style="max-height:80px;display:none;" class="img-thumbnail mt-2"></div>
HTML;
w("$V/homepage_sections/model/create.blade.php", modal('intro_section_form', 'intro_homepage_section_id', $body, true));
w("$V/homepage_sections/index.blade.php", indexBlade('Intro Homepage Sections', $addBtn('createIntroSection'), 'intro_sections_table', ['Key','Title','Order','Status','Action'], "@include('admin.intro.homepage_sections.model.create')", 'intro_homepage_sections.js', "{data:'section_key',name:'section_key'},{data:'title',name:'title'},{data:'display_order',name:'display_order'},{data:'status',name:'status',sortable:false,searchable:false},{data:'action',name:'action',sortable:false,searchable:false},", 'homepage-sections/data', 'intro_sections_table'));
w("$J/intro_homepage_sections.js", $previewHelper . "\npreviewImage('image','image_preview');\n" . stdCrudJs([
    'create'=>'createIntroSection','form'=>'intro_section_form','pk'=>'intro_homepage_section_id','path'=>'intro/homepage-sections','table'=>'intro_sections_table','label'=>'Section','status'=>true,
    'reset'=>'$("#image_preview").hide();',
    'validate'=>'if(!$("#section_key").val()){errorMessage("Section key is required");return false;}return true;',
    'fill'=>"        $(\"#section_key\").val(data.section_key);\n        $(\"#title\").val(data.title);\n        $(\"#subtitle\").val(data.subtitle);\n        $(\"#content\").val(data.content);\n        $(\"#content_json\").val(data.content_json ? (typeof data.content_json==='string'?data.content_json:JSON.stringify(data.content_json,null,2)) : '');\n        $(\"#button_text\").val(data.button_text);\n        $(\"#button_link\").val(data.button_link);\n        $(\"#display_order\").val(data.display_order);\n        $(\"#is_enabled\").val(data.is_enabled?1:0);\n        $(\"#status\").val(data.status||'active');\n        if(data.image_url){\$(\"#image_preview\").attr(\"src\",data.image_url).show();}else{\$(\"#image_preview\").hide();}",
]));

// ===================== PAGES =====================
$body = <<<'HTML'
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Slug</label><input type="text" class="form-control" id="slug" name="slug"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="6"></textarea></div>
                    <div class="mb-3"><label class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="published">Published</option><option value="draft">Draft</option></select></div>
                    <hr>
                    <div class="mb-3"><label class="form-label">SEO Title</label><input type="text" class="form-control" id="seo_title" name="seo_title"></div>
                    <div class="mb-3"><label class="form-label">Meta Description</label><textarea class="form-control" id="meta_description" name="meta_description" rows="2"></textarea></div>
                    <div class="mb-3"><label class="form-label">Meta Keywords</label><input type="text" class="form-control" id="meta_keywords" name="meta_keywords"></div>
                    <div class="mb-3"><label class="form-label">Canonical URL</label><input type="text" class="form-control" id="canonical_url" name="canonical_url"></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">OG Title</label><input type="text" class="form-control" id="og_title" name="og_title"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">OG Image</label><input type="file" class="form-control" id="og_image" name="og_image" accept="image/*"><img id="og_image_preview" src="" style="max-height:60px;display:none;" class="img-thumbnail mt-2"></div>
                    </div>
                    <div class="mb-3"><label class="form-label">OG Description</label><textarea class="form-control" id="og_description" name="og_description" rows="2"></textarea></div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Robots Index</label><select class="form-select" id="robots_index" name="robots_index"><option value="1">Yes</option><option value="0">No</option></select></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Robots Follow</label><select class="form-select" id="robots_follow" name="robots_follow"><option value="1">Yes</option><option value="0">No</option></select></div>
                    </div>
HTML;
w("$V/pages/model/create.blade.php", modal('intro_page_form', 'intro_page_id', $body, true));
w("$V/pages/index.blade.php", indexBlade('Intro Pages / SEO', $addBtn('createIntroPage'), 'intro_pages_table', ['Title','Slug','Status','Action'], "@include('admin.intro.pages.model.create')", 'intro_pages.js', "{data:'title',name:'title'},{data:'slug',name:'slug'},{data:'status',name:'status'},{data:'action',name:'action',sortable:false,searchable:false},", 'pages/data', 'intro_pages_table'));
w("$J/intro_pages.js", $previewHelper . "\npreviewImage('og_image','og_image_preview');\n" . stdCrudJs([
    'create'=>'createIntroPage','form'=>'intro_page_form','pk'=>'intro_page_id','path'=>'intro/pages','table'=>'intro_pages_table','label'=>'Page','status'=>false,
    'reset'=>'$("#og_image_preview").hide();',
    'validate'=>'if(!$("#title").val()){errorMessage("Title is required");return false;}return true;',
    'fill'=>"        $(\"#title\").val(data.title);\n        $(\"#slug\").val(data.slug);\n        $(\"#content\").val(data.content);\n        $(\"#status\").val(data.status||'published');\n        $(\"#seo_title\").val(data.seo_title);\n        $(\"#meta_description\").val(data.meta_description);\n        $(\"#meta_keywords\").val(data.meta_keywords);\n        $(\"#canonical_url\").val(data.canonical_url);\n        $(\"#og_title\").val(data.og_title);\n        $(\"#og_description\").val(data.og_description);\n        $(\"#robots_index\").val(data.robots_index?1:0);\n        $(\"#robots_follow\").val(data.robots_follow?1:0);\n        if(data.og_image_url){\$(\"#og_image_preview\").attr(\"src\",data.og_image_url).show();}else{\$(\"#og_image_preview\").hide();}",
]));

echo "Part 1 done\n";
