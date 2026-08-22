<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\AccountingSetting;
use App\Models\CustomerProfile;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\ServiceSale;
use App\Models\ServiceSaleDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Non-stock "service sale" transactions (gas cylinders, decoration, rental/
 * installation/delivery charges, etc), sold to a Customer. Structurally
 * cloned from ServicePurchaseService (itself cloned from Purchase's direct
 * flow), with Supplier -> Customer swapped and the JV legs flipped to a
 * receivable posting. Never touches ProductVariationStock/
 * ProductVariationStockTransaction - there is no stock code path here at all.
 */
class ServiceSaleService
{
    use Auditable;

    protected $model_service_sale;
    protected $model_service_sale_details;
    protected $with = [
        'business',
        'branch',
        'customer',
        'serviceSaleDetails',
        'serviceSaleDetails.product',
    ];

    public function __construct()
    {
        $this->model_service_sale = new Repository(new ServiceSale());
        $this->model_service_sale_details = new Repository(new ServiceSaleDetail());
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
        if (isset($obj['customer_id']) && $obj['customer_id'] != 0 && $obj['customer_id'] != "") {
            $wh[] = ['customer_id', $obj['customer_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['service_sale_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['service_sale_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_service_sale->getModel()::with($this->with)
            ->withCount('serviceSaleDetails as total_items')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('service_sale_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('service_sale_date', function ($item) {
                return !empty($item->service_sale_date)
                    ? localDate($item->service_sale_date)
                    : 'N/A';
            })
            ->addColumn('customer', function ($item) {
                return $item->customer->name ?? '';
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
                data-id='{$item->service_sale_id}'>";

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
                        href='" . route('service-sale.edit', $item->service_sale_id) . "'
                        id='editServiceSale'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending service sales can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $viewJvButton = $item->status === Status::APPROVED
                    ? "<button type='button' class='btn btn-icon btn-outline-dark mr-2 view-jv-btn'
                        data-source-type='" . JournalSourceTypes::SERVICE_SALE . "' data-source-id='{$item->service_sale_id}' title='View JV'>
                        <i class='fa fa-book'></i>
                        </button>"
                    : '';

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('service-sale.print', $item->service_sale_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteServiceSale'
                    data-id='{$item->service_sale_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $viewJvButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'customer', 'total_items', 'total', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['service_sale_id'])) {

                $service_sale = $this->model_service_sale
                    ->getModel()::findOrFail($obj['service_sale_id']);

                if ($service_sale->status !== Status::PENDING) {
                    throw new Exception('Only pending service sales can be updated.');
                }

                $service_sale->update([
                    'business_id'        => $obj['business_id'],
                    'customer_id'        => $obj['customer_id'],
                    'service_sale_date'  => $obj['service_sale_date'],
                    'description'        => $obj['description'] ?? null,
                    'subtotal'           => $obj['subtotal'],
                    'discount'           => $obj['discount'] ?? 0,
                    'discount_amount'    => $obj['discount_amount'],
                    'tax'                => $obj['tax'] ?? 0,
                    'tax_amount'         => $obj['tax_amount'],
                    'total'              => $obj['total'],
                    'updatedby_id'       => Auth::id(),
                    'date_updated'       => now(),
                ]);

                $this->model_service_sale_details->getModel()::where('service_sale_id', $service_sale->service_sale_id)
                    ->delete();

                $action = 'updated';
            }

            //====================================
            // Create
            //====================================

            else {

                $service_sale = $this->model_service_sale->create([
                    'service_sale_id'   => generateUuid(),
                    'business_id'        => $obj['business_id'],
                    'customer_id'        => $obj['customer_id'],
                    'branch_id'          => $obj['branch_id'] ?? null,
                    'service_sale_no'    => $obj['service_sale_no'],
                    'service_sale_date'  => $obj['service_sale_date'],
                    'description'        => $obj['description'] ?? null,
                    'subtotal'           => $obj['subtotal'],
                    'discount'           => $obj['discount'] ?? 0,
                    'discount_amount'    => $obj['discount_amount'],
                    'tax'                => $obj['tax'] ?? 0,
                    'tax_amount'         => $obj['tax_amount'],
                    'total'              => $obj['total'],
                    'status'             => Status::PENDING,
                    'createdby_id'       => Auth::id(),
                    'date_created'       => now(),
                ]);

                $action = 'created';
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['items'] as $item) {

                $this->model_service_sale_details->create([
                    'service_sale_detail_id' => generateUuid(),
                    'service_sale_id'        => $service_sale->service_sale_id,
                    'product_id'             => $item['product_id'] ?: null,
                    'item_name'              => $item['item_name'],
                    'quantity'               => $item['quantity'],
                    'unit_price'             => $item['unit_price'],
                    'subtotal'               => $item['subtotal'],
                    'discount'               => $item['discount'] ?? 0,
                    'discount_amount'        => $item['discount_amount'] ?? 0,
                    'tax'                    => $item['tax'] ?? 0,
                    'tax_amount'             => $item['tax_amount'] ?? 0,
                    'total'                  => $item['total'],
                    'description'            => $item['description'] ?? null,
                    'createdby_id'           => Auth::id(),
                    'date_created'           => now(),
                ]);
            }

            DB::commit();

            $this->logActivity('service_sale', $service_sale->service_sale_id, $action, null, ['total' => $service_sale->total]);

            return $service_sale;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($service_sale_id)
    {
        return $this->model_service_sale->with($this->with)->find($service_sale_id);
    }

    public function getDetails($service_sale_id)
    {
        $service_sale = $this->model_service_sale->getModel()::with($this->with)->findOrFail($service_sale_id);

        $data = [
            'header' => [
                'service_sale_id'   => $service_sale->service_sale_id,
                'customer_id'       => $service_sale->customer_id,
                'branch_id'         => $service_sale->branch_id,
                'service_sale_no'   => $service_sale->service_sale_no,
                'service_sale_date' => $service_sale->service_sale_date,
                'subtotal'          => $service_sale->subtotal,
                'discount'          => $service_sale->discount,
                'discount_amount'   => $service_sale->discount_amount,
                'tax'               => $service_sale->tax,
                'tax_amount'        => $service_sale->tax_amount,
                'total'             => $service_sale->total,
                'description'       => $service_sale->description,
                'status'            => $service_sale->status,
            ],
            'items' => [],
        ];

        foreach ($service_sale->serviceSaleDetails as $detail) {
            $data['items'][] = [
                'service_sale_detail_id' => $detail->service_sale_detail_id,
                'product_id'             => $detail->product_id,
                'product_name'           => $detail->product->name ?? '',
                'item_name'              => $detail->item_name,
                'quantity'               => $detail->quantity,
                'unit_price'             => $detail->unit_price,
                'subtotal'               => $detail->subtotal,
                'discount'               => $detail->discount,
                'discount_amount'        => $detail->discount_amount,
                'tax'                    => $detail->tax,
                'tax_amount'             => $detail->tax_amount,
                'total'                  => $detail->total,
                'description'            => $detail->description,
            ];
        }

        return $data;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $service_sale = $this->model_service_sale->getModel()::with($this->with)->findOrFail($obj['service_sale_id']);
            $old_status = $service_sale->status;
            $new_status = $obj['status'];

            $service_sale->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyServiceSaleApproval($service_sale);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseServiceSaleApproval($service_sale);
            }

            DB::commit();

            $this->logActivity(
                'service_sale',
                $service_sale->service_sale_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $service_sale;
    }

    public function delete($service_sale_id)
    {
        DB::beginTransaction();

        try {
            $service_sale = $this->model_service_sale->getModel()::with($this->with)->findOrFail($service_sale_id);

            if ($service_sale->status === Status::APPROVED) {
                $this->reverseServiceSaleApproval($service_sale);
            }

            $service_sale->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('service_sale', $service_sale->service_sale_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Service Sale Voucher when a Service Sale is approved.
     * Idempotent: a no-op if an active voucher already exists. There is no
     * stock/COGS code here at all - a Service Sale can never move inventory.
     */
    protected function applyServiceSaleApproval(ServiceSale $service_sale)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_SALE)
            ->where('source_id', $service_sale->service_sale_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $service_sale->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_service_sale_account_id)) {
            throw new Exception('Service Sale Account is not configured in Accounting Settings. Please configure it before approving service sales.');
        }

        app(AccountingPeriodService::class)->assertPostable($service_sale->business_id, now());

        $profile = CustomerProfile::where('user_id', $service_sale->customer_id)
            ->where('business_id', $service_sale->business_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$profile || empty($profile->account_id)) {
            throw new Exception('The selected customer does not have a linked Chart of Account. Please configure it before approving service sales.');
        }

        $journal = Journal::where('short', 'SSV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Service Sale Voucher" journal category found. Please configure it before approving service sales.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $service_sale->business_id,
            'branch_id'        => $service_sale->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $service_sale->service_sale_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated voucher for approved service sale ' . $service_sale->service_sale_no,
            'source_type'      => JournalSourceTypes::SERVICE_SALE,
            'source_id'        => $service_sale->service_sale_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $service_sale->total;

        // Debit the customer's receivable account - a service sale creates
        // what they owe us.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $profile->account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'user_id'                 => $service_sale->customer_id,
            'description'             => 'Service Sale - ' . $service_sale->service_sale_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_service_sale_account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'user_id'                 => $service_sale->customer_id,
            'description'             => 'Service Sale - ' . $service_sale->service_sale_no,
        ]);
    }

    /**
     * Reverse the Service Sale Voucher created when a Service Sale was
     * approved. Idempotent: a no-op if nothing active remains to reverse.
     */
    protected function reverseServiceSaleApproval(ServiceSale $service_sale)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_SALE)
            ->where('source_id', $service_sale->service_sale_id)
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
        return $this->model_service_sale->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
