<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\ProductReview;
use App\Repository\Repository;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ProductReviewService
{
    protected $model_review;

    public function __construct()
    {
        $this->model_review = new Repository(new ProductReview());
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_review->getModel()::where($wh)
            ->with('product')
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('product', fn($item) => $item->product->name ?? '-')
            ->addColumn('rating', fn($item) => str_repeat('★', (int) $item->rating) . str_repeat('☆', 5 - (int) $item->rating))
            ->addColumn('status', function ($item) {
                $checked = $item->status == 'published' ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusProductReview" type="checkbox" data-id="' . $item->review_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('date_created', fn($item) => localDateTime($item->date_created))
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-danger' id='deleteProductReview' data-id='{$item->review_id}'><i class='fa fa-trash'></i></a>";
            })
            ->rawColumns(['product', 'rating', 'status', 'action'])
            ->make(true);
    }

    public function status($id)
    {
        $review = $this->model_review->find($id);
        return $this->model_review->update([
            'status' => $review->status === 'published' ? 'hidden' : 'published',
            'date_updated' => now(),
        ], $id);
    }

    public function delete($id)
    {
        return $this->model_review->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    /**
     * Public storefront read - published reviews for a product, plus the
     * average rating and count (used on product detail pages).
     */
    public function getPublicByProduct($business_id, $product_id)
    {
        $reviews = $this->model_review->getModel()::where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->where('status', 'published')
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc')
            ->get();

        return [
            'average' => $reviews->count() ? round($reviews->avg('rating'), 1) : 0,
            'count' => $reviews->count(),
            'reviews' => $reviews->map(fn($r) => [
                'id' => $r->review_id,
                'reviewer_name' => $r->reviewer_name,
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'date' => $r->date_created,
            ])->values(),
        ];
    }

    /**
     * Latest published reviews across all products for a business - used
     * for a homepage "customer reviews" section.
     */
    public function getLatestPublished($business_id, $limit = 6)
    {
        return $this->model_review->getModel()::where('business_id', $business_id)
            ->where('status', 'published')
            ->where('is_deleted', 0)
            ->with('product')
            ->orderBy('date_created', 'desc')
            ->limit($limit)
            ->get()
            ->map(fn($r) => [
                'id' => $r->review_id,
                'product_name' => $r->product->name ?? null,
                'reviewer_name' => $r->reviewer_name,
                'rating' => (int) $r->rating,
                'comment' => $r->comment,
                'date' => $r->date_created,
            ])
            ->values();
    }

    /**
     * Public storefront submission - reviews are published by default; the
     * admin can hide them later via status(). One review per customer per
     * product (enforced here and by a DB unique index as a race-safety net).
     */
    public function submit($business_id, array $data)
    {
        $model = $this->model_review->getModel();

        if (!empty($data['customer_id'])) {
            $exists = $model::where('business_id', $business_id)
                ->where('product_id', $data['product_id'])
                ->where('customer_id', $data['customer_id'])
                ->where('is_deleted', 0)
                ->exists();

            if ($exists) {
                throw new Exception('You have already reviewed this product.');
            }
        }

        return $model::create([
            'review_id' => generateUuid(),
            'business_id' => $business_id,
            'product_id' => $data['product_id'],
            'customer_id' => $data['customer_id'] ?? null,
            'reviewer_name' => $data['reviewer_name'],
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'status' => 'published',
            'date_created' => now(),
        ]);
    }
}
