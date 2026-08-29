<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\Business;
use App\Models\IntroBusinessRegistration;
use App\Models\Package;
use App\Repository\Repository;
use App\Services\Concrete\Admin\AccountingSettingCloneService;
use App\Services\Concrete\Admin\ChartOfAccountsCloneService;
use App\Services\Concrete\Admin\SubscriptionService;
use App\Services\Concrete\Admin\WebsiteCmsDefaultsService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BusinessRegistrationService
{
    protected $repo;
    protected $subscription_service;
    protected $chart_of_accounts_clone_service;
    protected $accounting_setting_clone_service;
    protected $website_cms_defaults_service;

    public function __construct(
        SubscriptionService $subscription_service,
        ChartOfAccountsCloneService $chart_of_accounts_clone_service,
        AccountingSettingCloneService $accounting_setting_clone_service,
        WebsiteCmsDefaultsService $website_cms_defaults_service
    ) {
        $this->repo = new Repository(new IntroBusinessRegistration());
        $this->subscription_service = $subscription_service;
        $this->chart_of_accounts_clone_service = $chart_of_accounts_clone_service;
        $this->accounting_setting_clone_service = $accounting_setting_clone_service;
        $this->website_cms_defaults_service = $website_cms_defaults_service;
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with(['business.currentSubscription', 'package'])
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['search'])) {
            $s = $obj['search'];
            $q->where(function ($w) use ($s) {
                $w->where('business_name', 'like', "%{$s}%")
                    ->orWhere('owner_email', 'like', "%{$s}%")
                    ->orWhere('owner_name', 'like', "%{$s}%");
            });
        }

        return DataTables::of($q)
            ->addColumn('package_name', fn ($item) => $item->package?->name ?? '-')
            ->addColumn('subscription_status', fn ($item) => $item->business?->currentSubscription?->status ?? '-')
            ->addColumn('status_badge', function ($item) {
                return '<span class="badge bg-label-info">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-primary' id='viewIntroRegistration' data-id='{$item->intro_business_registration_id}'><i class='fa fa-eye'></i></a>";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load(['business.currentSubscription', 'business.package', 'package']);
    }

    public function updateStatus($id, string $status)
    {
        return $this->repo->update([
            'status' => $status,
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $id);
    }

    /**
     * Public Intro registration — reuses existing Package + SubscriptionService.
     * Does not modify package schema. Creates business with payment_pending.
     */
    public function registerFromIntro(array $data): IntroBusinessRegistration
    {
        $package = Package::where('package_id', $data['package_id'])
            ->where('is_deleted', 0)
            ->where('status', 1)
            ->first();

        if (!$package) {
            throw new Exception('Selected package is not available.');
        }

        if ($package->is_custom) {
            throw new Exception('This package requires a sales consultation. Please contact Dukanaz.');
        }

        $billingCycle = $package->duration_type ?: ($data['billing_cycle'] ?? 'monthly');
        if (!in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw new Exception('Invalid billing cycle.');
        }

        if ($package->priceForCycle($billingCycle) === null) {
            throw new Exception('Selected package does not have a valid price.');
        }

        return DB::transaction(function () use ($data, $package, $billingCycle) {
            $business = Business::create([
                'business_id' => generateUuid(),
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'] ?? null,
                'name' => $data['business_name'],
                'email' => $data['business_email'] ?? $data['owner_email'],
                'phone' => $data['business_phone'] ?? ($data['owner_phone'] ?? null),
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'description' => $data['business_type'] ?? null,
                'status' => 'active',
                'is_deleted' => 0,
                'createdby_id' => Auth::id(),
                'date_created' => now(),
            ]);

            $this->subscription_service->createInitial($business, $package, [
                'billing_cycle' => $billingCycle,
                'mark_paid' => false,
                'payment_method' => 'bank_transfer',
                'payment_reference' => 'intro registration',
            ]);

            $accountMap = $this->chart_of_accounts_clone_service->cloneTemplateToBusiness($business->business_id);
            $this->accounting_setting_clone_service->cloneTemplateToBusiness($business->business_id, $accountMap);
            $this->website_cms_defaults_service->seed($business->business_id);

            return $this->repo->create([
                'intro_business_registration_id' => generateUuid(),
                'business_id' => $business->business_id,
                'package_id' => $package->package_id,
                'billing_cycle' => $billingCycle,
                'business_name' => $data['business_name'],
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'] ?? null,
                'business_email' => $data['business_email'] ?? null,
                'business_phone' => $data['business_phone'] ?? null,
                'business_type' => $data['business_type'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'meta' => $data['meta'] ?? null,
                'date_created' => now(),
            ]);
        });
    }
}
