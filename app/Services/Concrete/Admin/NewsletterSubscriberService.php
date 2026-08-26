<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\NewsletterSubscriber;
use App\Repository\Repository;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class NewsletterSubscriberService
{
    protected $model_subscriber;

    public function __construct()
    {
        $this->model_subscriber = new Repository(new NewsletterSubscriber());
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_subscriber->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $checked = $item->status == 'subscribed' ? 'checked' : '';
                return '
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input statusNewsletterSubscriber" type="checkbox" data-id="' . $item->subscriber_id . '" ' . $checked . '>
                </div>';
            })
            ->addColumn('date_created', function ($item) {
                return localDateTime($item->date_created);
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-danger' id='deleteNewsletterSubscriber' data-id='{$item->subscriber_id}'><i class='fa fa-trash'></i></a>";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function status($id)
    {
        $subscriber = $this->model_subscriber->find($id);
        $subscribing = $subscriber->status !== 'subscribed';

        return $this->model_subscriber->update([
            'status' => $subscribing ? 'subscribed' : 'unsubscribed',
            'unsubscribed_at' => $subscribing ? null : now(),
        ], $id);
    }

    public function delete($id)
    {
        return $this->model_subscriber->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    /**
     * Public storefront subscribe. Reactivates an existing (unsubscribed)
     * row for the same email instead of creating a duplicate.
     */
    public function subscribe($business_id, $email, $source = null)
    {
        $model = $this->model_subscriber->getModel();
        $existing = $model::where('business_id', $business_id)
            ->where('email', $email)
            ->where('is_deleted', 0)
            ->first();

        if ($existing) {
            $existing->update([
                'status' => 'subscribed',
                'unsubscribed_at' => null,
                'source' => $existing->source ?? $source,
            ]);
            return $existing;
        }

        try {
            return $model::create([
                'subscriber_id' => generateUuid(),
                'business_id' => $business_id,
                'email' => $email,
                'source' => $source,
                'status' => 'subscribed',
                'date_created' => now(),
            ]);
        } catch (QueryException $e) {
            // Unique constraint race: another request just inserted the same
            // business_id+email. Reactivate that row instead of failing.
            $existing = $model::where('business_id', $business_id)->where('email', $email)->first();
            if ($existing) {
                $existing->update(['status' => 'subscribed', 'unsubscribed_at' => null, 'is_deleted' => 0]);
                return $existing;
            }
            throw $e;
        }
    }
}
