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
use App\Models\ServicePurchaseReturn;
use App\Models\ServicePurchaseReturnDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

/**
 * Returns/reversals against a previously approved Service Purchase.
 * Structurally cloned from PurchaseReturnService, minus the GRN duality
 * (a Service Purchase has no receiving workflow) and minus every stock
 * code path - a Service Purchase Return can never move inventory.
 */
class ServicePurchaseReturnService
{
    use Auditable;

    protected $model_service_purchase_return;
    protected $model_service_purchase_return_details;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'servicePurchase',
        'servicePurchaseReturnDetails',
        'servicePurchaseReturnDetails.product',
    ];

    public function __construct()
    {
        $this->model_service_purchase_return = new Repository(new ServicePurchaseReturn());
        $this->model_service_purchase_return_details = new Repository(new ServicePurchaseReturnDetail());
    }

    /**
     * Service Purchases eligible as a return source: approved only.
     */
    public function getEligibleServicePurchases($business_id = null)
    {
        $query = ServicePurchase::with(['supplier'])
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
        if (isset($obj['supplier_id']) && $obj['supplier_id'] != 0 && $obj['supplier_id'] != "") {
            $wh[] = ['supplier_id', $obj['supplier_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['service_purchase_return_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['service_purchase_return_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
        ];

        $datatable = $this->model_service_purchase_return->getModel()::with($this->with)
            ->withCount('servicePurchaseReturnDetails as total_items')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('service_purchase_return_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('service_purchase_return_date', function ($item) {
                return !empty($item->service_purchase_return_date)
                    ? localDate($item->service_purchase_return_date)
                    : 'N/A';
            })
            ->addColumn('source_no', function ($item) {
                return $item->servicePurchase->service_purchase_no ?? '';
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
                data-id='{$item->service_purchase_return_id}'>";

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
                        href='" . route('service-purchase-return.edit', $item->service_purchase_return_id) . "'
                        id='editServicePurchaseReturn'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending service purchase returns can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('service-purchase-return.print', $item->service_purchase_return_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteServicePurchaseReturn'
                    data-id='{$item->service_purchase_return_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['business', 'branch', 'supplier', 'source_no', 'total_items', 'total', 'status', 'action'])
            ->make(true);
    }

    /**
     * Sum of return_quantity across every posted (approved) Service Purchase
     * Return line for this service purchase line.
     */
    protected function getAlreadyReturnedQuantity($service_purchase_detail_id)
    {
        return (float) ServicePurchaseReturnDetail::query()
            ->join('service_purchase_returns', 'service_purchase_returns.service_purchase_return_id', '=', 'service_purchase_return_details.service_purchase_return_id')
            ->where('service_purchase_return_details.service_purchase_detail_id', $service_purchase_detail_id)
            ->where('service_purchase_returns.status', Status::APPROVED)
            ->where('service_purchase_returns.is_deleted', 0)
            ->sum('service_purchase_return_details.return_quantity');
    }

    /**
     * Fresh returnable lines for a Service Purchase, used to seed a new
     * Service Purchase Return's rows.
     */
    public function getSourceLines($service_purchase_id)
    {
        $service_purchase = ServicePurchase::with([
            'supplier',
            'servicePurchaseDetails',
            'servicePurchaseDetails.product',
        ])->findOrFail($service_purchase_id);

        if ($service_purchase->status !== Status::APPROVED) {
            throw new Exception('This service purchase is not eligible for a return.');
        }

        $lines = [];

        foreach ($service_purchase->servicePurchaseDetails as $detail) {
            $quantity = (float) ($detail->quantity ?? 0);
            $already_returned = $this->getAlreadyReturnedQuantity($detail->service_purchase_detail_id);
            $returnable = $quantity - $already_returned;

            if ($returnable <= 0) {
                continue;
            }

            $lines[] = [
                'service_purchase_detail_id' => $detail->service_purchase_detail_id,
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
                'service_purchase_id' => $service_purchase->service_purchase_id,
                'source_no'           => $service_purchase->service_purchase_no,
                'supplier_id'         => $service_purchase->supplier_id,
                'supplier_name'       => $service_purchase->supplier->name ?? '',
                'business_id'         => $service_purchase->business_id,
                'branch_id'           => $service_purchase->branch_id,
            ],
            'lines' => $lines,
        ];
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $service_purchase = ServicePurchase::with('servicePurchaseDetails.product')->find($obj['service_purchase_id'] ?? null);

            if (!$service_purchase) {
                throw new Exception('The selected service purchase was not found.');
            }

            if ($service_purchase->status !== Status::APPROVED) {
                throw new Exception('A Service Purchase Return can only be created against an approved service purchase.');
            }

            $business_id = $service_purchase->business_id;
            $branch_id = $service_purchase->branch_id;
            $supplier_id = $service_purchase->supplier_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['service_purchase_return_id'])) {

                $service_purchase_return = $this->model_service_purchase_return->getModel()::findOrFail($obj['service_purchase_return_id']);

                if ($service_purchase_return->status !== Status::PENDING) {
                    throw new Exception('Only pending service purchase returns can be updated.');
                }

                $service_purchase_return->update([
                    'business_id'                   => $business_id,
                    'branch_id'                     => $branch_id,
                    'supplier_id'                   => $supplier_id,
                    'service_purchase_id'           => $service_purchase->service_purchase_id,
                    'service_purchase_return_date'  => $obj['service_purchase_return_date'],
                    'reason'                        => $obj['reason'] ?? null,
                    'description'                   => $obj['description'] ?? null,
                    'updatedby_id'                  => Auth::id(),
                    'date_updated'                  => now(),
                ]);

                $this->model_service_purchase_return_details->getModel()::where('service_purchase_return_id', $service_purchase_return->service_purchase_return_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $service_purchase_return = $this->model_service_purchase_return->create([
                    'service_purchase_return_id'    => generateUuid(),
                    'business_id'                    => $business_id,
                    'branch_id'                      => $branch_id,
                    'supplier_id'                    => $supplier_id,
                    'service_purchase_id'            => $service_purchase->service_purchase_id,
                    'service_purchase_return_no'     => $obj['service_purchase_return_no'],
                    'service_purchase_return_date'   => $obj['service_purchase_return_date'],
                    'reason'                         => $obj['reason'] ?? null,
                    'description'                    => $obj['description'] ?? null,
                    'status'                         => Status::PENDING,
                    'createdby_id'                   => Auth::id(),
                    'date_created'                   => now(),
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

                $service_purchase_detail = $service_purchase->servicePurchaseDetails
                    ->firstWhere('service_purchase_detail_id', $item['service_purchase_detail_id']);

                if (!$service_purchase_detail) {
                    throw new Exception('One of the selected lines does not belong to the chosen service purchase.');
                }

                $quantity = (float) ($service_purchase_detail->quantity ?? 0);
                $already_returned = $this->getAlreadyReturnedQuantity($service_purchase_detail->service_purchase_detail_id);
                $unit_price = (float) $service_purchase_detail->unit_price;
                $discount_percent = (float) ($service_purchase_detail->discount ?? 0);
                $tax_percent = (float) ($service_purchase_detail->tax ?? 0);
                $item_label = $service_purchase_detail->item_name ?: ($service_purchase_detail->product->name ?? 'an item');

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

                $this->model_service_purchase_return_details->create([
                    'service_purchase_return_detail_id' => generateUuid(),
                    'service_purchase_return_id'        => $service_purchase_return->service_purchase_return_id,
                    'service_purchase_id'                => $service_purchase->service_purchase_id,
                    'service_purchase_detail_id'         => $service_purchase_detail->service_purchase_detail_id,
                    'product_id'                          => $service_purchase_detail->product_id,
                    'item_name'                           => $service_purchase_detail->item_name,
                    'quantity'                            => $quantity,
                    'already_returned_quantity'           => $already_returned,
                    'return_quantity'                     => $return_quantity,
                    'unit_price'                           => $unit_price,
                    'discount'                             => $discount_percent,
                    'discount_amount'                      => $line_discount_amount,
                    'tax'                                   => $tax_percent,
                    'tax_amount'                            => $line_tax_amount,
                    'subtotal'                              => $line_subtotal,
                    'total'                                 => $line_total,
                    'reason'                                => $item['reason'] ?? null,
                    'description'                           => $item['description'] ?? null,
                    'createdby_id'                           => Auth::id(),
                    'date_created'                           => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a return quantity for at least one item.');
            }

            $service_purchase_return->update([
                'subtotal'         => $subtotal,
                'discount_amount'  => $discount_amount_total,
                'tax_amount'       => $tax_amount_total,
                'total'            => $total,
            ]);

            DB::commit();

            return $service_purchase_return;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($service_purchase_return_id)
    {
        return $this->model_service_purchase_return->with($this->with)->find($service_purchase_return_id);
    }

    public function getDetails($service_purchase_return_id)
    {
        $service_purchase_return = $this->model_service_purchase_return->getModel()::with($this->with)->findOrFail($service_purchase_return_id);

        $data = [
            'header' => [
                'service_purchase_return_id'   => $service_purchase_return->service_purchase_return_id,
                'service_purchase_id'          => $service_purchase_return->service_purchase_id,
                'service_purchase_no'          => $service_purchase_return->servicePurchase->service_purchase_no ?? '',
                'supplier_id'                  => $service_purchase_return->supplier_id,
                'branch_id'                    => $service_purchase_return->branch_id,
                'service_purchase_return_no'   => $service_purchase_return->service_purchase_return_no,
                'service_purchase_return_date' => $service_purchase_return->service_purchase_return_date,
                'reason'                       => $service_purchase_return->reason,
                'description'                  => $service_purchase_return->description,
                'subtotal'                     => $service_purchase_return->subtotal,
                'discount_amount'              => $service_purchase_return->discount_amount,
                'tax_amount'                   => $service_purchase_return->tax_amount,
                'total'                        => $service_purchase_return->total,
                'status'                       => $service_purchase_return->status,
            ],
            'items' => [],
        ];

        foreach ($service_purchase_return->servicePurchaseReturnDetails as $detail) {
            $data['items'][] = [
                'service_purchase_return_detail_id' => $detail->service_purchase_return_detail_id,
                'service_purchase_detail_id'        => $detail->service_purchase_detail_id,
                'product_id'                          => $detail->product_id,
                'product_name'                        => $detail->product->name ?? '',
                'item_name'                            => $detail->item_name,
                'quantity'                             => $detail->quantity,
                'already_returned_quantity'            => $detail->already_returned_quantity,
                'return_quantity'                       => $detail->return_quantity,
                'unit_price'                            => $detail->unit_price,
                'discount'                              => $detail->discount,
                'discount_amount'                       => $detail->discount_amount,
                'tax'                                   => $detail->tax,
                'tax_amount'                             => $detail->tax_amount,
                'subtotal'                               => $detail->subtotal,
                'total'                                  => $detail->total,
                'reason'                                 => $detail->reason,
            ];
        }

        return $data;
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $service_purchase_return = $this->model_service_purchase_return->getModel()::with($this->with)->findOrFail($obj['service_purchase_return_id']);
            $old_status = $service_purchase_return->status;
            $new_status = $obj['status'];

            $service_purchase_return->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyServicePurchaseReturnPosting($service_purchase_return);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseServicePurchaseReturnPosting($service_purchase_return);
            }

            DB::commit();

            $this->logActivity(
                'service_purchase_return',
                $service_purchase_return->service_purchase_return_id,
                $new_status === Status::APPROVED ? 'approved' : 'status_changed',
                ['status' => $old_status],
                ['status' => $new_status]
            );
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $service_purchase_return;
    }

    public function delete($service_purchase_return_id)
    {
        DB::beginTransaction();

        try {
            $service_purchase_return = $this->model_service_purchase_return->getModel()::with($this->with)->findOrFail($service_purchase_return_id);

            if ($service_purchase_return->status === Status::APPROVED) {
                $this->reverseServicePurchaseReturnPosting($service_purchase_return);
            }

            $service_purchase_return->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            $this->logActivity('service_purchase_return', $service_purchase_return->service_purchase_return_id, 'deleted');

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post a Service Purchase Return Voucher when a Service Purchase
     * Return is approved. Idempotent: a no-op if an active voucher already
     * exists. No stock code path exists here at all.
     */
    protected function applyServicePurchaseReturnPosting(ServicePurchaseReturn $service_purchase_return)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_PURCHASE_RETURN)
            ->where('source_id', $service_purchase_return->service_purchase_return_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $service_purchase_return->business_id)->first();

        if (!$accounting_setting || !$accounting_setting->enable_accounting || empty($accounting_setting->default_service_purchase_return_account_id)) {
            throw new Exception('Service Purchase Return Account is not configured in Accounting Settings. Please configure it before approving service purchase returns.');
        }

        app(AccountingPeriodService::class)->assertPostable($service_purchase_return->business_id, now());

        if (empty($service_purchase_return->supplier) || empty($service_purchase_return->supplier->account_id)) {
            throw new Exception('The supplier does not have a linked Chart of Account. Please configure it before approving service purchase returns.');
        }

        $journal = Journal::where('short', 'SPRV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Service Purchase Return Voucher" journal category found. Please configure it before approving service purchase returns.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $service_purchase_return->business_id,
            'branch_id'        => $service_purchase_return->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $service_purchase_return->service_purchase_return_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated return voucher for approved service purchase return ' . $service_purchase_return->service_purchase_return_no,
            'source_type'      => JournalSourceTypes::SERVICE_PURCHASE_RETURN,
            'source_id'        => $service_purchase_return->service_purchase_return_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $service_purchase_return->total;

        // Debit the supplier's payable account - a return reduces what we owe them.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $service_purchase_return->supplier->account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'supplier_id'             => $service_purchase_return->supplier_id,
            'description'             => 'Service Purchase Return - ' . $service_purchase_return->service_purchase_return_no,
        ]);

        // Credit the Service Purchase Return account - contra to the original
        // Service Purchase Account debit.
        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_service_purchase_return_account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'supplier_id'             => $service_purchase_return->supplier_id,
            'description'             => 'Service Purchase Return - ' . $service_purchase_return->service_purchase_return_no,
        ]);

        \App\Services\Concrete\Admin\JournalEntryService::assertBalanced($journal_entry->journal_entry_id);
    }

    /**
     * Reverse the Service Purchase Return Voucher created when a Service
     * Purchase Return was approved. Idempotent: a no-op if nothing active
     * remains to reverse.
     */
    protected function reverseServicePurchaseReturnPosting(ServicePurchaseReturn $service_purchase_return)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::SERVICE_PURCHASE_RETURN)
            ->where('source_id', $service_purchase_return->service_purchase_return_id)
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
        return $this->model_service_purchase_return->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
