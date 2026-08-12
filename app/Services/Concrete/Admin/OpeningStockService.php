<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\JournalSourceTypes;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\AccountingSetting;
use App\Models\Journal;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Models\OpeningStock;
use App\Models\OpeningStockDetail;
use App\Models\ProductVariation;
use App\Models\ProductVariationBatch;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Services\Concrete\Admin\ProductVariationStockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class OpeningStockService
{
    protected $model_opening_stock;
    protected $model_opening_stock_details;
    protected $with = [
        'business',
        'branch',
        'warehouse',
        'openingStockDetails',
        'openingStockDetails.product',
        'openingStockDetails.productVariation',
        'openingStockDetails.productVariationUnitConversion',
        'openingStockDetails.unit',
    ];

    public function __construct()
    {
        $this->model_opening_stock = new Repository(new OpeningStock());
        $this->model_opening_stock_details = new Repository(new OpeningStockDetail());
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
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['opening_stock_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['opening_stock_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];

        $datatable = $this->model_opening_stock->getModel()::with($this->with)
            ->withCount('openingStockDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('opening_stock_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('opening_stock_date', function ($item) {
                return !empty($item->opening_stock_date)
                    ? localDate($item->opening_stock_date)
                    : 'N/A';
            })
            ->addColumn('warehouse', function ($item) {
                return $item->warehouse->name ?? '';
            })
            ->addColumn('business', function ($item) {
                return $item->business->name ?? '';
            })
            ->addColumn('branch', function ($item) {
                return $item->branch->name ?? '';
            })
            ->addColumn('total_products', function ($item) {
                return decimal($item->total_products ?? 0);
            })
            ->addColumn('total_value', function ($item) {
                return currency($item->total_value ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->opening_stock_id}'>";

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
                        href='" . route('opening-stock.edit', $item->opening_stock_id) . "'
                        id='editOpeningStock'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only pending opening stocks can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('opening-stock.print', $item->opening_stock_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $deleteButton = $item->status !== Status::CANCELLED
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteOpeningStock'
                    data-id='{$item->opening_stock_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $printButton . $deleteButton;
            })
            ->rawColumns(['opening_stock_date', 'business', 'branch', 'warehouse', 'total_products', 'total_value', 'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $warehouse = Warehouse::find($obj['warehouse_id'] ?? null);

            if (!$warehouse) {
                throw new Exception('The selected warehouse was not found.');
            }

            $business_id = $warehouse->business_id;
            $branch_id = $warehouse->branch_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['opening_stock_id'])) {

                $opening_stock = $this->model_opening_stock->getModel()::findOrFail($obj['opening_stock_id']);

                if ($opening_stock->status !== Status::PENDING) {
                    throw new Exception('Only pending opening stocks can be updated.');
                }

                $opening_stock->update([
                    'business_id'         => $business_id,
                    'branch_id'           => $branch_id,
                    'warehouse_id'        => $warehouse->warehouse_id,
                    'opening_stock_date'  => $obj['opening_stock_date'],
                    'reference'           => $obj['reference'] ?? null,
                    'description'         => $obj['description'] ?? null,
                    'updatedby_id'        => Auth::id(),
                    'date_updated'        => now(),
                ]);

                $this->model_opening_stock_details->getModel()::where('opening_stock_id', $opening_stock->opening_stock_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $opening_stock = $this->model_opening_stock->create([
                    'opening_stock_id'    => generateUuid(),
                    'business_id'         => $business_id,
                    'branch_id'           => $branch_id,
                    'warehouse_id'        => $warehouse->warehouse_id,
                    'opening_stock_no'    => $obj['opening_stock_no'],
                    'opening_stock_date'  => $obj['opening_stock_date'],
                    'reference'           => $obj['reference'] ?? null,
                    'description'         => $obj['description'] ?? null,
                    'status'              => Status::PENDING,
                    'createdby_id'        => Auth::id(),
                    'date_created'        => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $total_quantity = 0;
            $total_value = 0;
            $has_quantity = false;

            foreach ($obj['products'] as $product) {

                $quantity = (float) ($product['quantity'] ?? 0);

                if ($quantity < 0) {
                    throw new Exception('Quantity cannot be negative.');
                }

                if ($quantity > 0) {
                    $has_quantity = true;
                }

                $conversion_factor = (float) ($product['conversion_factor'] ?? 1) > 0 ? (float) $product['conversion_factor'] : 1;
                $unit_cost = (float) ($product['unit_cost'] ?? 0);
                $base_quantity = $quantity * $conversion_factor;
                $line_total = $base_quantity * $unit_cost;

                $total_quantity += $base_quantity;
                $total_value += $line_total;

                $this->model_opening_stock_details->create([
                    'opening_stock_detail_id'               => generateUuid(),
                    'opening_stock_id'                       => $opening_stock->opening_stock_id,
                    'product_id'                              => $product['product_id'],
                    'product_variation_id'                    => $product['product_variation_id'],
                    'product_variation_unit_conversion_id'    => $product['product_variation_unit_conversion_id'] ?? null,
                    'unit_id'                                  => $product['unit_id'],
                    'conversion_factor'                        => $conversion_factor,
                    'quantity'                                 => $quantity,
                    'base_quantity'                            => $base_quantity,
                    'unit_cost'                                => $unit_cost,
                    'total_value'                              => $line_total,
                    'batch_no'                                  => $product['batch_no'] ?? null,
                    'expiry_date'                               => !empty($product['expiry_date']) ? $product['expiry_date'] : null,
                    'description'                               => $product['description'] ?? null,
                    'createdby_id'                              => Auth::id(),
                    'date_created'                              => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a quantity for at least one product.');
            }

            $opening_stock->update([
                'total_quantity' => $total_quantity,
                'total_value'    => $total_value,
            ]);

            DB::commit();

            return $opening_stock;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($opening_stock_id)
    {
        return $this->model_opening_stock->with($this->with)->find($opening_stock_id);
    }

    public function getDetails($opening_stock_id)
    {
        try {
            $opening_stock = $this->model_opening_stock->getModel()::with($this->with)->findOrFail($opening_stock_id);

            $data = [
                'header' => [
                    'opening_stock_id'    => $opening_stock->opening_stock_id,
                    'warehouse_id'        => $opening_stock->warehouse_id,
                    'branch_id'           => $opening_stock->branch_id,
                    'opening_stock_no'    => $opening_stock->opening_stock_no,
                    'opening_stock_date'  => $opening_stock->opening_stock_date,
                    'reference'           => $opening_stock->reference,
                    'description'         => $opening_stock->description,
                    'total_quantity'      => $opening_stock->total_quantity,
                    'total_value'         => $opening_stock->total_value,
                    'status'              => $opening_stock->status,
                ],
                'details' => []
            ];

            foreach ($opening_stock->openingStockDetails as $detail) {
                $data['details'][] = [
                    'opening_stock_detail_id'             => $detail->opening_stock_detail_id,
                    'product_id'                            => $detail->product_id,
                    'product_name'                          => $detail->product->name ?? '',
                    'product_variation_id'                  => $detail->product_variation_id,
                    'product_variation_name'                => $detail->productVariation->name ?? '',
                    'product_variation_unit_conversion_id'  => $detail->product_variation_unit_conversion_id,
                    'quantity'                               => $detail->quantity,
                    'unit_id'                                => $detail->unit_id,
                    'unit_name'                              => $detail->unit->name ?? 'N/A',
                    'conversion_factor'                      => $detail->conversion_factor,
                    'unit_cost'                               => $detail->unit_cost,
                    'total_value'                             => $detail->total_value,
                    'batch_no'                                => $detail->batch_no,
                    'expiry_date'                             => !empty($detail->expiry_date) ? localDate($detail->expiry_date) : '',
                    'track_batch'                             => $detail->productVariation->track_batch ?? 0,
                    'track_expiry'                            => $detail->productVariation->track_expiry ?? 0,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function status($obj)
    {
        DB::beginTransaction();

        try {
            $opening_stock = $this->model_opening_stock->getModel()::with($this->with)->findOrFail($obj['opening_stock_id']);
            $old_status = $opening_stock->status;
            $new_status = $obj['status'];

            $opening_stock->update([
                'status'       => $new_status,
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            if ($new_status === Status::APPROVED && $old_status !== Status::APPROVED) {
                $this->applyOpeningStockPosting($opening_stock);
            } elseif ($old_status === Status::APPROVED && $new_status !== Status::APPROVED) {
                $this->reverseOpeningStockPosting($opening_stock);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return $opening_stock;
    }

    public function delete($opening_stock_id)
    {
        DB::beginTransaction();

        try {
            $opening_stock = $this->model_opening_stock->getModel()::with($this->with)->findOrFail($opening_stock_id);

            if ($opening_stock->status === Status::APPROVED) {
                $this->reverseOpeningStockPosting($opening_stock);
            }

            $opening_stock->update([
                'is_deleted'   => 1,
                'status'       => Status::CANCELLED,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * Auto-post an Opening Stock Voucher and stock-in transactions when an
     * Opening Stock is approved. Idempotent: a no-op if an active voucher
     * already exists for this opening stock.
     */
    protected function applyOpeningStockPosting(OpeningStock $opening_stock)
    {
        $existing = JournalEntry::where('source_type', JournalSourceTypes::OPENING_STOCK)
            ->where('source_id', $opening_stock->opening_stock_id)
            ->where('is_deleted', 0)
            ->exists();

        if ($existing) {
            return;
        }

        $accounting_setting = AccountingSetting::where('business_id', $opening_stock->business_id)->first();

        if (
            !$accounting_setting
            || !$accounting_setting->enable_accounting
            || empty($accounting_setting->default_inventory_account_id)
            || empty($accounting_setting->default_opening_stock_account_id)
        ) {
            throw new Exception('Inventory Account / Opening Stock Account is not configured in Accounting Settings. Please configure it before approving opening stock.');
        }

        $journal = Journal::where('short', 'OSV')->where('is_deleted', 0)->first();

        if (!$journal) {
            throw new Exception('No "Opening Stock Voucher" journal category found. Please configure it before approving opening stock.');
        }

        $entry_no = generateJVNum($journal->journal_id);

        $journal_entry = JournalEntry::create([
            'journal_entry_id' => generateUuid(),
            'journal_id'       => $journal->journal_id,
            'business_id'      => $opening_stock->business_id,
            'branch_id'        => $opening_stock->branch_id,
            'entry_no'         => $entry_no,
            'reference_no'     => $opening_stock->opening_stock_no,
            'entry_date'       => now(),
            'description'      => 'Auto-generated opening stock voucher for approved opening stock ' . $opening_stock->opening_stock_no,
            'source_type'      => JournalSourceTypes::OPENING_STOCK,
            'source_id'        => $opening_stock->opening_stock_id,
            'status'           => 'posted',
            'postedby_id'      => Auth::id(),
            'date_posted'      => now(),
            'createdby_id'     => Auth::id(),
            'date_created'     => now(),
        ]);

        $amount = $opening_stock->total_value;

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_inventory_account_id,
            'debit'                   => $amount,
            'credit'                  => 0,
            'description'             => 'Opening Stock - ' . $opening_stock->opening_stock_no,
        ]);

        JournalEntryDetail::create([
            'journal_entry_detail_id' => generateUuid(),
            'journal_entry_id'        => $journal_entry->journal_entry_id,
            'account_id'              => $accounting_setting->default_opening_stock_account_id,
            'debit'                   => 0,
            'credit'                  => $amount,
            'description'             => 'Opening Stock - ' . $opening_stock->opening_stock_no,
        ]);

        foreach ($opening_stock->openingStockDetails as $detail) {
            $base_quantity = $detail->base_quantity;

            if ($base_quantity <= 0) {
                continue;
            }

            $line_cost = $detail->unit_cost * $base_quantity;

            $stock = ProductVariationStock::where('business_id', $opening_stock->business_id)
                ->where('warehouse_id', $opening_stock->warehouse_id)
                ->where('product_id', $detail->product_id)
                ->where('product_variation_id', $detail->product_variation_id)
                ->first();

            $existing_qty = $stock->quantity ?? 0;
            $existing_avg = $stock->avg_price ?? 0;
            $new_qty = $existing_qty + $base_quantity;
            $new_avg = $new_qty > 0
                ? (($existing_qty * $existing_avg) + $line_cost) / $new_qty
                : 0;

            if ($stock) {
                $stock->update([
                    'quantity'  => $new_qty,
                    'avg_price' => $new_avg,
                ]);
            } else {
                $stock = ProductVariationStock::create([
                    'product_variation_stock_id' => generateUuid(),
                    'business_id'                => $opening_stock->business_id,
                    'warehouse_id'               => $opening_stock->warehouse_id,
                    'product_id'                 => $detail->product_id,
                    'product_variation_id'       => $detail->product_variation_id,
                    'quantity'                   => $new_qty,
                    'avg_price'                  => $new_avg,
                    'status'                     => 'active',
                    'createdby_id'               => Auth::id(),
                    'date_created'               => now(),
                ]);
            }

            $product_variation_batch_id = null;
            $variation = ProductVariation::find($detail->product_variation_id);

            if ($variation && ($variation->track_batch || $variation->track_expiry) && !empty($detail->batch_no)) {
                $batch = ProductVariationBatch::where('business_id', $opening_stock->business_id)
                    ->where('warehouse_id', $opening_stock->warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variation_id', $detail->product_variation_id)
                    ->where('batch_no', $detail->batch_no)
                    ->first();

                if ($batch) {
                    $batch->update([
                        'quantity'  => ($batch->quantity ?? 0) + $base_quantity,
                        'avg_price' => $new_avg,
                    ]);
                } else {
                    $batch = ProductVariationBatch::create([
                        'product_variation_batch_id' => generateUuid(),
                        'batch_no'                   => $detail->batch_no,
                        'business_id'                => $opening_stock->business_id,
                        'product_id'                 => $detail->product_id,
                        'product_variation_id'       => $detail->product_variation_id,
                        'warehouse_id'               => $opening_stock->warehouse_id,
                        'avg_price'                  => $new_avg,
                        'quantity'                   => $base_quantity,
                        'expiry_date'                => $detail->expiry_date,
                        'status'                     => 'active',
                        'createdby_id'               => Auth::id(),
                        'date_created'               => now(),
                    ]);
                }

                $product_variation_batch_id = $batch->product_variation_batch_id;

                $detail->update(['product_variation_batch_id' => $product_variation_batch_id]);
            }

            ProductVariationStockTransaction::create([
                'product_variation_stock_transaction_id' => generateUuid(),
                'transaction_date'                       => now(),
                'transaction_type'                        => TransactionType::OPENING,
                'business_id'                             => $opening_stock->business_id,
                'product_id'                              => $detail->product_id,
                'product_variation_id'                    => $detail->product_variation_id,
                'warehouse_id'                             => $opening_stock->warehouse_id,
                'unit_id'                                  => $detail->unit_id,
                'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                'conversion_factor'                        => $detail->conversion_factor,
                'quantity'                                 => $detail->quantity,
                'base_quantity'                            => $base_quantity,
                'unit_price'                               => $detail->unit_cost,
                'total_price'                              => $line_cost,
                'quantity_after'                           => $new_qty,
                'avg_price_after'                          => $new_avg,
                'reference_id'                              => $opening_stock->opening_stock_id,
                'reference_type'                            => ReferenceType::OPENING_STOCK,
                'remarks'                                   => 'Auto-created on approval of opening stock ' . $opening_stock->opening_stock_no,
                'product_variation_batch_id'                => $product_variation_batch_id,
                'createdby_id'                              => Auth::id(),
                'date_created'                              => now(),
            ]);
        }
    }

    /**
     * Reverse the Opening Stock Voucher and stock effects created when an
     * Opening Stock was approved. Idempotent: a no-op if nothing active
     * remains to reverse.
     */
    protected function reverseOpeningStockPosting(OpeningStock $opening_stock)
    {
        $journal_entry = JournalEntry::where('source_type', JournalSourceTypes::OPENING_STOCK)
            ->where('source_id', $opening_stock->opening_stock_id)
            ->where('is_deleted', 0)
            ->first();

        if ($journal_entry) {
            $journal_entry->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        }

        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::OPENING_STOCK)
            ->where('reference_id', $opening_stock->opening_stock_id)
            ->where('is_deleted', 0)
            ->get();

        if ($stock_transactions->isEmpty()) {
            return;
        }

        $stock_transactions->each(function ($transaction) {
            $transaction->update([
                'is_deleted'   => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);
        });

        $affected = $stock_transactions->unique(function ($transaction) {
            return $transaction->business_id . '|' . $transaction->warehouse_id . '|' .
                $transaction->product_id . '|' . $transaction->product_variation_id;
        });

        $stock_service = app(ProductVariationStockService::class);

        foreach ($affected as $transaction) {
            $stock_service->recomputeLedger(
                $transaction->business_id,
                $transaction->warehouse_id,
                $transaction->product_id,
                $transaction->product_variation_id
            );
        }
    }

    public function getByBusiness($business_id)
    {
        return $this->model_opening_stock->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
