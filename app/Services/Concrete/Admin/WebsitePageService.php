<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\WebsitePage;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

/**
 * Fixed catalog of policy-type pages (privacy-policy, terms-conditions,
 * shipping-information, cancellation-policy, return-policy). Rows are
 * upserted by (business_id, slug) rather than freely created/deleted - the
 * catalog itself is predefined, only the content is admin-editable.
 */
class WebsitePageService
{
    public const SLUGS = [
        'privacy-policy'        => 'Privacy Policy',
        'terms-conditions'      => 'Terms & Conditions',
        'shipping-information'  => 'Shipping Information',
        'cancellation-policy'   => 'Cancellation Policy',
        'return-policy'         => 'Return Policy',
    ];

    protected $model_page;

    public function __construct()
    {
        $this->model_page = new Repository(new WebsitePage());
    }

    /**
     * Ensures every predefined page slug has a row for this business, then
     * returns them all (used to render the admin list).
     */
    public function ensureCatalog($business_id)
    {
        foreach (self::SLUGS as $slug => $title) {
            $model = $this->model_page->getModel();
            $model::firstOrCreate(
                ['business_id' => $business_id, 'slug' => $slug],
                [
                    'page_id' => generateUuid(),
                    'title' => $title,
                    'status' => Status::ACTIVE,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]
            );
        }
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
            $this->ensureCatalog($obj['business_id']);
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_page->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('title');
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusWebsitePage" type="checkbox" data-id="' . $item->page_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='editWebsitePage' href='javascript:void(0)' data-id='{$item->page_id}' data-original-title='Edit'><i class='icon-base fa fa-pencil'></i></a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        $obj['updatedby_id'] = Auth::id();
        $obj['date_updated'] = now();
        $this->model_page->update($obj, $obj['page_id']);
        return $this->model_page->find($obj['page_id']);
    }

    public function getById($page_id)
    {
        return $this->model_page->find($page_id);
    }

    public function status($page_id)
    {
        return $this->model_page->update([
            'status' => ($this->model_page->find($page_id)->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $page_id);
    }

    public function getPublicBySlug($business_id, $slug)
    {
        $page = $this->model_page->getModel()::where('business_id', $business_id)
            ->where('slug', $slug)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->first();

        if (!$page) {
            return null;
        }

        return [
            'slug' => $page->slug,
            'title' => $page->title,
            'content' => $page->content,
            'seo' => [
                'title' => $page->seo_title,
                'description' => $page->seo_description,
                'keywords' => $page->seo_keywords,
                'og_image' => $page->og_image_url,
            ],
        ];
    }

    public function getAllPublic($business_id)
    {
        $this->ensureCatalog($business_id);

        return $this->model_page->getModel()::where('business_id', $business_id)
            ->where('status', Status::ACTIVE)
            ->where('is_deleted', 0)
            ->whereNotNull('content')
            ->get()
            ->map(fn($p) => ['slug' => $p->slug, 'title' => $p->title])
            ->values();
    }
}
