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
use App\Models\ServiceSaleReturn;
use App\Models\ServiceSaleReturnDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Returns/reversals against a previously approved Service Sale. Structurally
 * cloned from ServicePurchaseReturnService with Supplier -> Customer swapped
 * and the JV legs flipped. No stock code path exists here at all.
 */
class ServiceSaleReturnService
{
    use Auditable;

    protected $model_service_sale_return;
    protected $model_service_sale_return_details;
    protected $with = [
        'business',
        'branch',
        'customer',
        'serviceSale',
        'serviceSaleReturnDetails',
        'serviceSaleReturnDetails.product',
    ];

    public function __construct()
    {
        $this->model_service_sale_return = new Repository(new ServiceSaleReturn());
        $this->model_service_sale_return_details = new Repository(new ServiceSaleReturnDetail());
    }

    /**
     * Service Sales eligible as a return source: approved only.
     */
    public function getEligibleServiceSales($business_id = null)
    {
        $query = ServiceSale::with(['customer'])
            ->where('status', Status::APPROVED)
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        } else {
            $query->where('business_id', Auth::user()->business_id);
        }

        return $query->get();
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
            $wh[] = ['service_sale_return_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['service_sale_return_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_service_sale_return->getModel()::with($this->with)
            ->withCount('serviceSaleReturnDetails as total_items')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('service_sale_return_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('service_sale_return_date', function ($item) {
                return !empty($item->service_sale_return_date)
                    ? localDate($item->service_sale_return_date)
                    : 'N/A';
            })
            ->addColumn('source_no', function ($item) {
                return $item->serviceSale->service_sale_no ?? '';
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
                data-id='{$item->service_sale_return_id}'>";

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
                        href='" . route('service-sale-return.edit', $item->service_sale_return_id) . "'
                        id='editServiceSaleReturn'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending service sale returns can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('service-sale-return.print', $item->service_sale_return_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteServiceSaleReturn'
                    data-id='{$item->service_sale_return_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'customer', 'source_no', 'total_items', 'total', 'status', 'action'])
            ->make(true);
    }

    /**
     * Sum of return_quantity across every posted (approved) Service Sale
     * Return line for this service sale line.
     */
    protected function getAlreadyReturnedQuantity($service_sale_detail_id)
    {
        return (float) ServiceSaleReturnDetail::query()
            ->join('service_sale_returns', 'service_sale_returns.service_sale_return_id', '=', 'service_sale_return_details.service_sale_return_id')
            ->where('service_sale_return_details.service_sale_detail_id', $service_sale_detail_id)
            ->where('service_sale_returns.status', Status::APPROVED)
            ->where('service_sale_returns.is_deleted', 0)
            ->sum('service_sale_return_details.return_quantity');
    }

    /**
     * Fresh returnable lines for a Service Sale, used to seed a new Service
     * Sale Return's rows.
     */
    public function getSourceLines($service_sale_id)
    {
        $service_sale = ServiceSale::with([
            'customer',
            'serviceSaleDetails',
            'serviceSaleDetails.product',
        ])->findOrFail($service_sale_id);

        if ($service_sale->status !== Status::APPROVED) {
            throw new Exception('This service sale is not eligible for a return.');
        }

        $lines = [];

        foreach ($service_sale->serviceSaleDetails as $detail) {
            $quantity = (float) ($detail->quantity ?? 0);
            $already_returned = $this->getAlreadyReturnedQuantity($detail->service_sale_detail_id);
            $returnable = $quantity - $already_returned;

            if ($returnable <= 0) {
                continue;
            }

            $lines[] = [
                'service_sale_detail_id'    => $detail->service_sale_detail_id,
                'product_id'                 => $detail->product_id,
                'product_name'               => $detail->product->name ?? '',
                'item_name'                  => $detail->item_name,
                'quantity'                   => $quantity,
                'already_returned_quantity'  => $already_returned,
                'returnable_quantity'        => $returnable,
                'unit_price'                 => $detail->unit_price,
                'discount'                   => $detail->discount ?? 0,
                'tax'                        => $detail->tax ?? 0,
            ];
        }

        return [
            'header' => [
                'service_sale_id' => $service_sale->service_sale_id,
                'source_no'       => $service_sale->service_sale_no,
                'customer_id'     => $service_sale->customer_id,
                'customer_name'   => $service_sale->customer->name ?? '',
                'business_id'     => $service_sale->business_id,
                'branch_id'       => $service_sale->branch_id,
            ],
            'lines' => $lines,
        ];
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $service_sale = ServiceSale::with('serviceSaleDetails.product')->find($obj['service_sale_id'] ?? null);

            if (!$service_sale) {
                throw new Exception('The selected service sale was not found.');
            }

            if ($service_sale->status !== Status::APPROVED) {
                throw new Exception('A Service Sale Return can only be created against an approved service sale.');
            }

            $business_id = $service_sale->business_id;
            $branch_id = $service_sale->branch_id;
            $customer_id = $service_sale->customer_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['service_sale_return_id'])) {

                $service_sale_return = $this->model_service_sale_return->getModel()::findOrFail($obj['service_sale_return_id']);

                if ($service_sale_return->status !== Status::PENDING) {
                    throw new Exception('Only pending service sale returns can be updated.');
                }

                $service_sale_return->update([
                    'business_id'                => $business_id,
                    'branch_id'                  => $branch_id,
                    'customer_id'                => $customer_id,
                    'service_sale_id'            => $service_sale->service_sale_id,
                    'service_sale_return_date'   => $obj['service_sale_return_date'],
                    'reason'                     => $obj['reason'] ?? null,
                    'description'                => $obj['description'] ?? null,
                    'updatedby_id'               => Auth::id(),
                    'date_updated'               => now(),
                ]);

                $this->model_service_sale_return_details->getModel()::where('service_sale_return_id', $service_sale_return->service_sale_return_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $service_sale_return = $this->model_service_sale_return->create([
                    'service_sale_return_id'    => generateUuid(),
                    'business_id'                => $business_id,
                    'branch_id'                  => $branch_id,
                    'customer_id'                => $customer_id,
                    'service_sale_id'            => $service_sale->service_sale_id,
                    'service_sale_return_no'     => $obj['service_sale_return_no'],
                    'service_sale_return_date'   => $obj['service_sale_return_date'],
                    'reason'                     => $obj['reason'] ?? null,
                    'description'                => $obj['description'] ?? null,
                    'status'                     => Status::PENDING,
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $subtotal = 0;
            $discount_amount_total = 0;
            $tax_amount_total = 0;
            $total = 0;
            $has_quantity = false;

            foreach ($obj['items'] as $item) {

                $service_sale_detail = $service_sale->serviceSaleDetails
                    ->firstWhere('service_sale_detail_id', $item['service_sale_detail_id']);

                if (!$service_sale_detail) {
                    throw new Exception('One of the selected lines does not belong to the chosen service sale.');
                }

                $quantity = (float) ($service_sale_detail->quantity ?? 0);
                $already_returned = $this->getAlreadyReturnedQuantity($service_sale_detail->service_sale_detail_id);
                $unit_price = (float) $service_sale_detail->unit_price;
                $discount_percent = (float) ($service_sale_detail->discount ?? 0);
                $tax_percent = (float) ($service_sale_detail->tax ?? 0);
                $item_label = $service_sale_detail->item_name ?: ($service_sale_detail->product->name ?? 'an item');

                $return_quantity = (float) ($item['return_quantity'] ?? 0);

                if ($return_quantity < 0) {
                    throw new Exception('Return quantity cannot be negative.');
                }

                $returnable = $quantity - $already_returned;

                if ($return_quantity > $returnable) {
                    throw new Exception('Return quantity for "' . $item_label . '" exceeds the returnable quantity.');
                }

                if ($return_quantity > 0) {
                    $has_quantity = true;
                }

                $line_subtotal = $return_quantity * $unit_price;
                $line_discount_amount = round($line_subtotal * $discount_percent / 100, 3);
                $taxable = $line_subtotal - $line_discount_amount;
                $line_tax_amount = \App\Support\Tax\TaxCalculator::lineTax($taxable, $tax_percent);
                $line_total = $taxable + $line_tax_amount;

                $subtotal += $line_subtotal;
                $discount_amount_total += $line_discount_amount;
                $tax_amount_total += $line_tax_amount;
                $total += $line_total;

                $this->model_service_sale_return_details->create([
                    'service_sale_return_detail_id' => generateUuid(),
                    'service_sale_return_id'        => $service_sale_return->service_sale_return_id,
                    'service_sale_id'                 => $service_sale->service_sale_id,
                    'service_sale_detail_id'          => $service_sale_detail->service_sale_detail_id,
                    'product_id'                       => $service_sale_detail->product_id,
                    'item_name'                        => $service_sale_detail->item_name,
                    'quantity'                         => $quantity,
                    'already_returned_quantity'        => $already_returned,
                    'return_quantity'                  => $return_quantity,
                    'unit_price'                        => $unit_price,
                    'discount'                          => $discount_percent,
                    'discount_amount'                   => $line_discount_amount,
                    'tax'                                => $tax_percent,
                    'tax_amount'                         => $line_tax_amount,
                    'subtotal'                           => $line_subtotal,
                    'total'                              => $line_total,
                    'reason'                             => $item['reason'] ?? null,
                    'description'                        => $item['description'] ?? null,
                    'createdby_id'                        => Auth::id(),
                    'date_created'                        => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a return quantity for at least one item.');
            }

            $service_sale_return->update([
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount_amount_total,
                'tax_amount'       => $tax_amount_total,
                'total'            => $total,
            ]);

            DB::commit();

            return $service_sale_return;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($service_sale_return_id)
    {
        return $this->model_service_sale_return->with($this->with)->find($service_sale_return_id);
    }

    public function getDetails($service_sale_return_id)
    {
        $service_sale_return = $this->model_service_sale_return->getModel()::with($this->with)->findOrFail($service_sale_return_id);

        $data = [
            'header' => [
                'service_sale_return_id'   => $service_sale_return->service_sale_return_id,
                'service_sale_id'          => $service_sale_return->service_sale_id,
                'service_sale_no'          => $service_sale_return->serviceSale->service_sale_no ?? '',
                'customer_id'              => $service_sale_return->customer_id,
                'branch_id'                => $service_sale_return->branch_id,
                'service_sale_return_no'   => $service_sale_return->service_sale_return_no,
                'service_sale_return_date' => $service_sale_return->service_sale_return_date,
                'reason'                   => $service_sale_return->reason,
                'description'              => $service_sale_return->description,
                'subtotal'                 => $service_sale_return->subtotal,
                'discount_amount'          => $service_sale_return->discount_amount,
                'tax_amount'               => $service_sale_return->tax_amount,
                'total'                    => $service_sale_return->total,
                'status'                   => $service_sale_return->status,
            ],
            'items' => [],
        ];

        foreach ($service_sale_return->serviceSaleReturnDetails as $detail) {
            $data['items'][] = [
                'service_sale_return_detail_id' => $detail->service_sale_return_detail_id,
                'service_sale_detail_id'        => $detail->service_sale_detail_id,
                'product_id'                      => $detail->product_id,
                'product_name'                    => $detail->product->name ?? '',
                'item_name'                        => $detail->item_name,
                'quantity'                         => $detail->quantity,
                'already_returned_quantity'        => $detail->already_returned_quantity,
                'return_quantity'                   => $detail->return_quantity,
                'unit_price'                        => $detail->unit_price,
                'discount'                          => $detail->discount,
                'discount_amount'                   => $detail->discount_amount,
                'tax'                               => $detail->tax,
                'tax_amount'                         => $detail->tax_amount,
                'subtotal'                           => $detail->subtotal,
                'total'                              => $detail->total,
                'reason'                             => $detail->reason,
            ];
        }

        return $data;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $service_sale_return = $this->model_service_sale_return->getModel()::with($this->with)->findOrFail($obj['service_sale_return_id']);
            $old_status = $service_sale_return->status;
            $new_status = $obj['status'];

            $service_sale_return->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyServiceSaleReturnPosting($service_sale_return);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseServiceSaleReturnPosting($service_sale_return);
            }

            DB::commit();

            $this->logActivity(
                'service_sale_return',
                $service_sale_return->service_sale_return_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $service_sale_return;
    }

    public function delete($service_sale_return_id)
    {
        DB::beginTransaction();

        try {
            $service_sale_return = $this->model_service_sale_return->getModel()::with($this->with)->findOrFail($service_sale_return_id);

            if ($service_sale_return->status === Status::APPROVED) {
                $this->reverseServiceSaleReturnPosting($service_sale_return);
            }

            $service_sale_return->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('service_sale_return', $service_sale_return->service_sale_return_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Service Sale Return Voucher when a Service Sale Return is
     * approved. Idempotent: a no-op if an active voucher already exists. No
     * stock code path exists here at all.
     */
    protected function applyServiceSaleReturnPosting(ServiceSaleReturn $service_sale_return)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_SALE_RETURN)
            ->where('source_id', $service_sale_return->service_sale_return_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $service_sale_return->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_service_sale_return_account_id)) {
            throw new Exception('Service Sale Return Account is not configured in Accounting Settings. Please configure it before approving service sale returns.');
        }

        app(AccountingPeriodService::class)->assertPostable($service_sale_return->business_id, now());

        $profile = CustomerProfile::where('user_id', $service_sale_return->customer_id)
            ->where('business_id', $service_sale_return->business_id)
            ->where('is_deleted', 0)
            ->first();

        if (!$profile || empty($profile->account_id)) {
            throw new Exception('The customer does not have a linked Chart of Account. Please configure it before approving service sale returns.');
        }

        $journal = Journal::where('short', 'SSRV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Service Sale Return Voucher" journal category found. Please configure it before approving service sale returns.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $service_sale_return->business_id,
            'branch_id'        => $service_sale_return->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $service_sale_return->service_sale_return_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated return voucher for approved service sale return ' . $service_sale_return->service_sale_return_no,
            'source_type'      => JournalSourceTypes::SERVICE_SALE_RETURN,
            'source_id'        => $service_sale_return->service_sale_return_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $service_sale_return->total;

        // Debit the Service Sale Return account - contra to the original
        // Service Sale Account credit.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_service_sale_return_account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'user_id'                 => $service_sale_return->customer_id,
            'description'             => 'Service Sale Return - ' . $service_sale_return->service_sale_return_no,
        ]);

        // Credit the customer's receivable account - a return reduces what
        // they owe us.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $profile->account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'user_id'                 => $service_sale_return->customer_id,
            'description'             => 'Service Sale Return - ' . $service_sale_return->service_sale_return_no,
        ]);

        \App\Services\Concrete\Admin\JournalEntryService::assertBalanced($journal_entry->journal_entry_id);
    }

    /**
     * Reverse the Service Sale Return Voucher created when a Service Sale
     * Return was approved. Idempotent: a no-op if nothing active remains to
     * reverse.
     */
    protected function reverseServiceSaleReturnPosting(ServiceSaleReturn $service_sale_return)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_SALE_RETURN)
            ->where('source_id', $service_sale_return->service_sale_return_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            app(AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);

            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_service_sale_return->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
