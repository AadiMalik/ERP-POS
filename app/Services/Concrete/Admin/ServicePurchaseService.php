<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ServicePurchase;
use App\Models\ServicePurchaseDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Non-stock "service purchase" transactions (gas cylinders, rentals,
 * installation/delivery charges, etc). Structurally cloned from
 * PurchaseService's direct-purchase flow (save/status/delete + JV posting on
 * approval), but never touches ProductVariationStock/
 * ProductVariationStockTransaction - there is no stock code path here at all,
 * so approving a Service Purchase can never move inventory.
 */
class ServicePurchaseService
{
    use Auditable;

    protected $model_service_purchase;
    protected $model_service_purchase_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'servicePurchaseDetails',
        'servicePurchaseDetails.product',
    ];

    public function __construct()
    {
        $this->model_service_purchase = new Repository(new ServicePurchase());
        $this->model_service_purchase_details = new Repository(new ServicePurchaseDetail());
    }

    public function getData($obj)
    {
        $wh = [];
        $orderBy = Filter::ORDERBY;

        if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
            $orderBy = $obj['orderBy'];
        }
        if (isset($obj['business_id']) && $obj['business_id'] != 0 && $obj['business_id'] != "") {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (isset($obj['branch_id']) && $obj['branch_id'] != 0 && $obj['branch_id'] != "") {
            $wh[] = ['branch_id', $obj['branch_id']];
        }
        if (isset($obj['supplier_id']) && $obj['supplier_id'] != 0 && $obj['supplier_id'] != "") {
            $wh[] = ['supplier_id', $obj['supplier_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['service_purchase_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['service_purchase_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_service_purchase->getModel()::with($this->with)
            ->withCount('servicePurchaseDetails as total_items')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('service_purchase_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('service_purchase_date', function ($item) {
                return !empty($item->service_purchase_date)
                    ? localDate($item->service_purchase_date)
                    : 'N/A';
            })
            ->addColumn('supplier', function ($item) {
                return ($item->supplier->code ?? '') . ' ' . ($item->supplier->name ?? '');
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('total_items', function ($item) {
                return decimal($item->total_items ?? 0);
            })
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->service_purchase_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                $editButton = $item->status === Status::PENDING
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('service-purchase.edit', $item->service_purchase_id) . "'
                        id='editServicePurchase'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending service purchases can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::APPROVED
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::SERVICE_PURCHASE . "' data-source-id='{$item->service_purchase_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('service-purchase.print', $item->service_purchase_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteServicePurchase'
                    data-id='{$item->service_purchase_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'supplier', 'total_items', 'total', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['service_purchase_id'])) {

                $service_purchase = $this->model_service_purchase
                    ->getModel()::findOrFail($obj['service_purchase_id']);

                if ($service_purchase->status !== Status::PENDING) {
                    throw new Exception('Only pending service purchases can be updated.');
                }

                $service_purchase->update([
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'service_purchase_date'  => $obj['service_purchase_date'],
                    'description'            => $obj['description'] ?? null,
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'] ?? 0,
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'] ?? 0,
                    'tax_amount'             => $obj['tax_amount'],
                    'total'                  => $obj['total'],
                    'updatedby_id'           => Auth::id(),
                    'date_updated'           => now(),
                ]);

                $this->model_service_purchase_details->getModel()::where('service_purchase_id', $service_purchase->service_purchase_id)
                    ->delete();

                $action = 'updated';
            }

            //====================================
            // Create
            //====================================

            else {

                $service_purchase = $this->model_service_purchase->create([
                    'service_purchase_id'   => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'branch_id'              => $obj['branch_id'] ?? null,
                    'service_purchase_no'    => $obj['service_purchase_no'],
                    'service_purchase_date'  => $obj['service_purchase_date'],
                    'description'            => $obj['description'] ?? null,
                    'subtotal'               => $obj['subtotal'],
                    'discount'               => $obj['discount'] ?? 0,
                    'discount_amount'        => $obj['discount_amount'],
                    'tax'                    => $obj['tax'] ?? 0,
                    'tax_amount'             => $obj['tax_amount'],
                    'total'                  => $obj['total'],
                    'status'                 => Status::PENDING,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);

                $action = 'created';
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['items'] as $item) {

                $this->model_service_purchase_details->create([
                    'service_purchase_detail_id' => generateUuid(),
                    'service_purchase_id'        => $service_purchase->service_purchase_id,
                    'product_id'                 => $item['product_id'] ?: null,
                    'item_name'                  => $item['item_name'],
                    'quantity'                   => $item['quantity'],
                    'unit_price'                 => $item['unit_price'],
                    'subtotal'                   => $item['subtotal'],
                    'discount'                   => $item['discount'] ?? 0,
                    'discount_amount'            => $item['discount_amount'] ?? 0,
                    'tax'                        => $item['tax'] ?? 0,
                    'tax_amount'                 => $item['tax_amount'] ?? 0,
                    'total'                      => $item['total'],
                    'description'                => $item['description'] ?? null,
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            DB::commit();

            $this->logActivity('service_purchase', $service_purchase->service_purchase_id, $action, null, ['total' => $service_purchase->total]);

            return $service_purchase;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($service_purchase_id)
    {
        return $this->model_service_purchase->with($this->with)->find($service_purchase_id);
    }

    public function getDetails($service_purchase_id)
    {
        $service_purchase = $this->model_service_purchase->getModel()::with($this->with)->findOrFail($service_purchase_id);

        $data = [
            'header' => [
                'service_purchase_id'   => $service_purchase->service_purchase_id,
                'supplier_id'           => $service_purchase->supplier_id,
                'branch_id'             => $service_purchase->branch_id,
                'service_purchase_no'   => $service_purchase->service_purchase_no,
                'service_purchase_date' => $service_purchase->service_purchase_date,
                'subtotal'              => $service_purchase->subtotal,
                'discount'              => $service_purchase->discount,
                'discount_amount'       => $service_purchase->discount_amount,
                'tax'                   => $service_purchase->tax,
                'tax_amount'            => $service_purchase->tax_amount,
                'total'                 => $service_purchase->total,
                'description'           => $service_purchase->description,
                'status'                => $service_purchase->status,
            ],
            'items' => [],
        ];

        foreach ($service_purchase->servicePurchaseDetails as $detail) {
            $data['items'][] = [
                'service_purchase_detail_id' => $detail->service_purchase_detail_id,
                'product_id'                 => $detail->product_id,
                'product_name'               => $detail->product->name ?? '',
                'item_name'                  => $detail->item_name,
                'quantity'                   => $detail->quantity,
                'unit_price'                 => $detail->unit_price,
                'subtotal'                   => $detail->subtotal,
                'discount'                   => $detail->discount,
                'discount_amount'            => $detail->discount_amount,
                'tax'                        => $detail->tax,
                'tax_amount'                 => $detail->tax_amount,
                'total'                      => $detail->total,
                'description'                => $detail->description,
            ];
        }

        return $data;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $service_purchase = $this->model_service_purchase->getModel()::with($this->with)->findOrFail($obj['service_purchase_id']);
            $old_status = $service_purchase->status;
            $new_status = $obj['status'];

            $service_purchase->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyServicePurchaseApproval($service_purchase);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseServicePurchaseApproval($service_purchase);
            }

            DB::commit();

            $this->logActivity(
                'service_purchase',
                $service_purchase->service_purchase_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $service_purchase;
    }

    public function delete($service_purchase_id)
    {
        DB::beginTransaction();

        try {
            $service_purchase = $this->model_service_purchase->getModel()::with($this->with)->findOrFail($service_purchase_id);

            if ($service_purchase->status === Status::APPROVED) {
                $this->reverseServicePurchaseApproval($service_purchase);
            }

            $service_purchase->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('service_purchase', $service_purchase->service_purchase_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Service Purchase Voucher when a Service Purchase is
     * approved. Idempotent: a no-op if an active voucher already exists.
     * Unlike PurchaseService::applyDirectPurchaseApproval(), there is no
     * ProductVariationStock/ProductVariationStockTransaction code here at
     * all - a Service Purchase can never move inventory.
     */
    protected function applyServicePurchaseApproval(ServicePurchase $service_purchase)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_PURCHASE)
            ->where('source_id', $service_purchase->service_purchase_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $service_purchase->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_service_purchase_account_id)) {
            throw new Exception('Service Purchase Account is not configured in Accounting Settings. Please configure it before approving service purchases.');
        }

        app(AccountingPeriodService::class)->assertPostable($service_purchase->business_id, now());

        if (empty($service_purchase->supplier) || empty($service_purchase->supplier->account_id)) {
            throw new Exception('The selected supplier does not have a linked Chart of Account. Please configure it before approving service purchases.');
        }

        $journal = Journal::where('short', 'SPV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Service Purchase Voucher" journal category found. Please configure it before approving service purchases.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $service_purchase->business_id,
            'branch_id'        => $service_purchase->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $service_purchase->service_purchase_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated voucher for approved service purchase ' . $service_purchase->service_purchase_no,
            'source_type'      => JournalSourceTypes::SERVICE_PURCHASE,
            'source_id'        => $service_purchase->service_purchase_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $service_purchase->total;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_service_purchase_account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'supplier_id'             => $service_purchase->supplier_id,
            'description'             => 'Service Purchase - ' . $service_purchase->service_purchase_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $service_purchase->supplier->account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'supplier_id'             => $service_purchase->supplier_id,
            'description'             => 'Service Purchase - ' . $service_purchase->service_purchase_no,
        ]);
    }

    /**
     * Reverse the Service Purchase Voucher created when a Service Purchase
     * was approved. Idempotent: a no-op if nothing active remains to reverse.
     */
    protected function reverseServicePurchaseApproval(ServicePurchase $service_purchase)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_PURCHASE)
            ->where('source_id', $service_purchase->service_purchase_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_service_purchase->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
