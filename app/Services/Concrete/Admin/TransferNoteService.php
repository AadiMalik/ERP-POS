<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\ReferenceType;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Enums\TransactionType;
use App\Models\ProductVariationStock;
use App\Models\ProductVariationStockTransaction;
use App\Models\TransferNote;
use App\Models\TransferNoteDetail;
use App\Models\Warehouse;
use App\Repository\Repository;
use App\Services\Concrete\Admin\ProductVariationStockService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class TransferNoteService
{
    protected $model_transfer_note;
    protected $model_transfer_note_details;
    protected $with = [
        'business',
        'branch',
        'destinationBranch',
        'sourceWarehouse',
        'destinationWarehouse',
        'transferNoteDetails',
        'transferNoteDetails.product',
        'transferNoteDetails.productVariation',
        'transferNoteDetails.productVariationUnitConversion',
        'transferNoteDetails.unit',
        'createdby',
        'sentby',
        'receivedby',
    ];

    public function __construct()
    {
        $this->model_transfer_note = new Repository(new TransferNote());
        $this->model_transfer_note_details = new Repository(new TransferNoteDetail());
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
        if (isset($obj['source_warehouse_id']) && $obj['source_warehouse_id'] != 0 && $obj['source_warehouse_id'] != "") {
            $wh[] = ['source_warehouse_id', $obj['source_warehouse_id']];
        }
        if (isset($obj['destination_warehouse_id']) && $obj['destination_warehouse_id'] != 0 && $obj['destination_warehouse_id'] != "") {
            $wh[] = ['destination_warehouse_id', $obj['destination_warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['transfer_note_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }
        if (!empty($obj['end_date'])) {
            $wh[] = ['transfer_note_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }

        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
        ];

        $datatable = $this->model_transfer_note->getModel()::with($this->with)
            ->withCount('transferNoteDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('transfer_note_date', $orderBy);

        // A destination-branch user needs to see transfers being sent *to* them too,
        // not just ones sourced from their own branch - see destination_branch_id.
        $datatable = applyRoleScope($datatable, $allow_roles, 'business_id', 'branch_id', 'destination_branch_id');

        return DataTables::of($datatable)
            ->addColumn('transfer_note_date', function ($item) {
                return !empty($item->transfer_note_date)
                    ? localDate($item->transfer_note_date)
                    : 'N/A';
            })
            ->addColumn('source_warehouse', function ($item) {
                return $item->sourceWarehouse->name ?? '';
            })
            ->addColumn('destination_warehouse', function ($item) {
                return $item->destinationWarehouse->name ?? '';
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
                $badges = [
                    Status::DRAFT      => 'secondary',
                    Status::IN_TRANSIT => 'warning',
                    Status::RECEIVED   => 'success',
                    Status::CANCELLED  => 'danger',
                ];
                $labels = [
                    Status::DRAFT      => 'Draft',
                    Status::IN_TRANSIT => 'In Transit',
                    Status::RECEIVED   => 'Received',
                    Status::CANCELLED  => 'Cancelled',
                ];
                $color = $badges[$item->status] ?? 'secondary';
                $label = $labels[$item->status] ?? ucfirst($item->status);

                return "<span class='badge bg-{$color}'>{$label}</span>";
            })
            ->addColumn('action', function ($item) {
                $user = auth()->user();
                $has_received_qty = $item->transferNoteDetails->sum('received_quantity') > 0;

                $editButton = $item->status === Status::DRAFT
                    ? "<a class='btn btn-icon btn-outline-primary mr-2'
                        href='" . route('transfer-note.edit', $item->transfer_note_id) . "'
                        id='editTransferNote'>
                        <i class='fa fa-pencil'></i>
                        </a>"
                    : "<button type='button' class='btn btn-icon btn-outline-primary mr-2' disabled
                        title='Only draft transfer notes can be edited'>
                        <i class='fa fa-pencil'></i>
                        </button>";

                $sendButton = '';
                if ($item->status === Status::DRAFT && $user?->can('transfer-note.send') && $this->canActOnBranch($item->branch_id)) {
                    $sendButton = "<button type='button' class='btn btn-icon btn-outline-success mr-2 sendTransferNote'
                        data-id='{$item->transfer_note_id}' title='Send'>
                        <i class='fa fa-paper-plane'></i>
                        </button>";
                }

                $receiveButton = '';
                if ($item->status === Status::IN_TRANSIT && $user?->can('transfer-note.receive') && $this->canActOnBranch($item->destination_branch_id)) {
                    $receiveButton = "<button type='button' class='btn btn-icon btn-outline-success mr-2 receiveTransferNote'
                        data-id='{$item->transfer_note_id}' title='Receive'>
                        <i class='fa fa-truck-loading'></i>
                        </button>";
                }

                $printButton = "<a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('transfer-note.print', $item->transfer_note_id) . "' title='Print'>
                    <i class='fa fa-print'></i>
                    </a>";

                $canCancel = in_array($item->status, [Status::DRAFT, Status::IN_TRANSIT], true) && !$has_received_qty;
                $deleteButton = $canCancel && $user?->can('transfer-note.delete') && $this->canActOnBranch($item->branch_id)
                    ? "<a class='btn btn-icon btn-outline-danger'
                    id='deleteTransferNote'
                    data-id='{$item->transfer_note_id}'>
                    <i class='fa fa-trash'></i>
                    </a>"
                    : '';

                return $editButton . $sendButton . $receiveButton . $printButton . $deleteButton;
            })
            ->rawColumns(['transfer_note_date', 'business', 'branch', 'source_warehouse', 'destination_warehouse', 'total_products', 'total_value', 'status', 'action'])
            ->make(true);
    }

    /**
     * In-memory mirror of applyRoleScope()'s role classification (see
     * app/Helpers/CommonFunctions.php), used only to decide whether to render
     * the Send/Receive/Cancel buttons for a given branch. Not itself an
     * authorization boundary - the controller re-checks via applyRoleScope()
     * against the database before any action actually runs.
     */
    protected function canActOnBranch($branch_id)
    {
        $user = Auth::user();
        $role = getRoleName();

        if ($role === RoleNames::SUPERADMIN || $role === RoleNames::BUSINESSADMIN) {
            return true;
        }

        if (in_array($role, RoleNames::businessLevelRoles(), true)) {
            return true;
        }

        return $user && !empty($branch_id) && $user->branch_id === $branch_id;
    }

    /**
     * Current stock for every product/variation in a warehouse, used to
     * drive the "available quantity" picker/validation when building a
     * Transfer Note's product rows.
     */
    public function getSourceStock($warehouse_id)
    {
        $stocks = app(ProductVariationStockService::class)->getByWarehouse($warehouse_id);

        return $stocks->map(function ($stock) {
            return [
                'product_id'           => $stock->product_id,
                'product_name'         => $stock->product->name ?? '',
                'product_variation_id' => $stock->product_variation_id,
                'variation_name'       => $stock->productVariation->name ?? '',
                'available_quantity'   => $stock->quantity,
                'unit_cost'            => $stock->avg_price,
            ];
        })->values();
    }

    protected function getAvailableQuantity($business_id, $warehouse_id, $product_id, $product_variation_id)
    {
        // Excludes whatever a Manufacturing Plan has reserved at this
        // warehouse - reserved_quantity is always 0 for a business that never
        // enables Manufacturing, so this is a no-op everywhere else.
        $stock = ProductVariationStock::where('business_id', $business_id)
            ->where('warehouse_id', $warehouse_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->first(['quantity', 'reserved_quantity']);

        return (float) ($stock->quantity ?? 0) - (float) ($stock->reserved_quantity ?? 0);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {
            $source_warehouse = Warehouse::find($obj['source_warehouse_id'] ?? null);
            $destination_warehouse = Warehouse::find($obj['destination_warehouse_id'] ?? null);

            if (!$source_warehouse || !$destination_warehouse) {
                throw new Exception('The selected source/destination warehouse was not found.');
            }

            if ($source_warehouse->warehouse_id === $destination_warehouse->warehouse_id) {
                throw new Exception('Source and destination warehouse cannot be the same.');
            }

            if ($source_warehouse->business_id !== $destination_warehouse->business_id) {
                throw new Exception('Source and destination warehouse must belong to the same business.');
            }

            $business_id = $source_warehouse->business_id;
            $branch_id = $source_warehouse->branch_id;
            $destination_branch_id = $destination_warehouse->branch_id;

            //====================================
            // Update
            //====================================

            if (!empty($obj['transfer_note_id'])) {

                $transfer_note = $this->model_transfer_note->getModel()::findOrFail($obj['transfer_note_id']);

                if ($transfer_note->status !== Status::DRAFT) {
                    throw new Exception('Only draft transfer notes can be updated.');
                }

                $transfer_note->update([
                    'business_id'               => $business_id,
                    'branch_id'                 => $branch_id,
                    'source_warehouse_id'       => $source_warehouse->warehouse_id,
                    'destination_warehouse_id'  => $destination_warehouse->warehouse_id,
                    'destination_branch_id'     => $destination_branch_id,
                    'transfer_note_date'        => $obj['transfer_note_date'],
                    'reference'                 => $obj['reference'] ?? null,
                    'description'               => $obj['description'] ?? null,
                    'updatedby_id'              => Auth::id(),
                    'date_updated'              => now(),
                ]);

                $this->model_transfer_note_details->getModel()::where('transfer_note_id', $transfer_note->transfer_note_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $transfer_note = $this->model_transfer_note->create([
                    'transfer_note_id'          => generateUuid(),
                    'business_id'               => $business_id,
                    'branch_id'                 => $branch_id,
                    'source_warehouse_id'       => $source_warehouse->warehouse_id,
                    'destination_warehouse_id'  => $destination_warehouse->warehouse_id,
                    'destination_branch_id'     => $destination_branch_id,
                    'transfer_note_no'          => $obj['transfer_note_no'],
                    'transfer_note_date'        => $obj['transfer_note_date'],
                    'reference'                 => $obj['reference'] ?? null,
                    'description'               => $obj['description'] ?? null,
                    'status'                    => Status::DRAFT,
                    'createdby_id'              => Auth::id(),
                    'date_created'              => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            $total_quantity = 0;
            $total_value = 0;
            $has_quantity = false;

            foreach ($obj['products'] as $product) {

                $transfer_quantity = (float) ($product['transfer_quantity'] ?? 0);

                if ($transfer_quantity < 0) {
                    throw new Exception('Transfer quantity cannot be negative.');
                }

                $conversion_factor = (float) ($product['conversion_factor'] ?? 1) > 0 ? (float) $product['conversion_factor'] : 1;
                $base_quantity = $transfer_quantity * $conversion_factor;

                $available_quantity = $this->getAvailableQuantity(
                    $business_id,
                    $source_warehouse->warehouse_id,
                    $product['product_id'],
                    $product['product_variation_id']
                );

                if ($base_quantity > $available_quantity) {
                    throw new Exception('Transfer quantity exceeds the available stock at the source warehouse.');
                }

                if ($transfer_quantity > 0) {
                    $has_quantity = true;
                }

                $unit_cost = (float) ProductVariationStock::where('business_id', $business_id)
                    ->where('warehouse_id', $source_warehouse->warehouse_id)
                    ->where('product_id', $product['product_id'])
                    ->where('product_variation_id', $product['product_variation_id'])
                    ->value('avg_price') ?? 0;

                $line_total = $base_quantity * $unit_cost;

                $total_quantity += $base_quantity;
                $total_value += $line_total;

                $this->model_transfer_note_details->create([
                    'transfer_note_detail_id'              => generateUuid(),
                    'transfer_note_id'                      => $transfer_note->transfer_note_id,
                    'product_id'                             => $product['product_id'],
                    'product_variation_id'                   => $product['product_variation_id'],
                    'product_variation_unit_conversion_id'   => $product['product_variation_unit_conversion_id'] ?? null,
                    'unit_id'                                 => $product['unit_id'],
                    'conversion_factor'                       => $conversion_factor,
                    'available_quantity'                      => $available_quantity,
                    'transfer_quantity'                       => $transfer_quantity,
                    'received_quantity'                       => 0,
                    'base_quantity'                            => $base_quantity,
                    'unit_cost'                                => $unit_cost,
                    'total_value'                              => $line_total,
                    'description'                              => $product['description'] ?? null,
                    'createdby_id'                              => Auth::id(),
                    'date_created'                              => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a transfer quantity for at least one product.');
            }

            $transfer_note->update([
                'total_quantity' => $total_quantity,
                'total_value'    => $total_value,
            ]);

            DB::commit();

            return $transfer_note;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($transfer_note_id)
    {
        return $this->model_transfer_note->with($this->with)->find($transfer_note_id);
    }

    public function getDetails($transfer_note_id)
    {
        try {
            $transfer_note = $this->model_transfer_note->getModel()::with($this->with)->findOrFail($transfer_note_id);

            $data = [
                'header' => [
                    'transfer_note_id'          => $transfer_note->transfer_note_id,
                    'source_warehouse_id'       => $transfer_note->source_warehouse_id,
                    'destination_warehouse_id'  => $transfer_note->destination_warehouse_id,
                    'branch_id'                 => $transfer_note->branch_id,
                    'branch_name'               => $transfer_note->branch->name ?? '',
                    'destination_branch_id'     => $transfer_note->destination_branch_id,
                    'destination_branch_name'   => $transfer_note->destinationBranch->name ?? '',
                    'transfer_note_no'          => $transfer_note->transfer_note_no,
                    'transfer_note_date'        => $transfer_note->transfer_note_date,
                    'reference'                 => $transfer_note->reference,
                    'description'               => $transfer_note->description,
                    'total_quantity'            => $transfer_note->total_quantity,
                    'total_value'               => $transfer_note->total_value,
                    'status'                    => $transfer_note->status,
                    'createdby_name'            => $transfer_note->createdby->name ?? '',
                    'date_created'              => $transfer_note->date_created,
                    'sentby_name'               => $transfer_note->sentby->name ?? '',
                    'date_sent'                 => $transfer_note->date_sent,
                    'receivedby_name'           => $transfer_note->receivedby->name ?? '',
                    'date_received'             => $transfer_note->date_received,
                ],
                'details' => []
            ];

            foreach ($transfer_note->transferNoteDetails as $detail) {
                $data['details'][] = [
                    'transfer_note_detail_id'              => $detail->transfer_note_detail_id,
                    'product_id'                             => $detail->product_id,
                    'product_name'                           => $detail->product->name ?? '',
                    'product_variation_id'                   => $detail->product_variation_id,
                    'product_variation_name'                 => $detail->productVariation->name ?? '',
                    'product_variation_unit_conversion_id'   => $detail->product_variation_unit_conversion_id,
                    'available_quantity'                      => $detail->available_quantity,
                    'transfer_quantity'                       => $detail->transfer_quantity,
                    'received_quantity'                       => $detail->received_quantity,
                    'remaining_quantity'                      => max(0, (float) $detail->transfer_quantity - (float) $detail->received_quantity),
                    'unit_id'                                 => $detail->unit_id,
                    'unit_name'                                => $detail->unit->name ?? 'N/A',
                    'conversion_factor'                        => $detail->conversion_factor,
                    'unit_cost'                                 => $detail->unit_cost,
                    'total_value'                               => $detail->total_value,
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Draft -> In Transit. Re-validates available quantity at the source
     * warehouse (stock may have moved since the draft was created) and
     * deducts it, writing a TRANSFER_OUT ledger entry per line - the
     * destination is intentionally left untouched until receive().
     */
    public function send($transfer_note_id)
    {
        DB::beginTransaction();

        try {
            $transfer_note = $this->model_transfer_note->getModel()::with('transferNoteDetails.product')
                ->findOrFail($transfer_note_id);

            if ($transfer_note->status !== Status::DRAFT) {
                throw new Exception('Only draft transfer notes can be sent.');
            }

            foreach ($transfer_note->transferNoteDetails as $detail) {
                $base_quantity = $detail->base_quantity;

                if ($base_quantity <= 0) {
                    continue;
                }

                $source_stock = ProductVariationStock::where('business_id', $transfer_note->business_id)
                    ->where('warehouse_id', $transfer_note->source_warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variation_id', $detail->product_variation_id)
                    ->lockForUpdate()
                    ->first();

                $source_quantity = $source_stock->quantity ?? 0;
                // Excludes whatever a Manufacturing Plan has reserved at the
                // source warehouse - reserved_quantity is always 0 for a
                // business that never enables Manufacturing.
                $source_available = $source_quantity - ($source_stock->reserved_quantity ?? 0);

                if ($base_quantity > $source_available) {
                    throw new Exception('Insufficient stock for "' . ($detail->product->name ?? 'a product') . '" at the source warehouse to send this transfer.');
                }

                $unit_cost = $source_stock->avg_price ?? 0;
                $line_value = $base_quantity * $unit_cost;
                $source_new_qty = $source_quantity - $base_quantity;

                $source_stock->update(['quantity' => $source_new_qty]);

                ProductVariationStockTransaction::create([
                    'product_variation_stock_transaction_id' => generateUuid(),
                    'transaction_date'                       => now(),
                    'transaction_type'                        => TransactionType::TRANSFER_OUT,
                    'business_id'                             => $transfer_note->business_id,
                    'product_id'                              => $detail->product_id,
                    'product_variation_id'                    => $detail->product_variation_id,
                    'warehouse_id'                             => $transfer_note->source_warehouse_id,
                    'unit_id'                                  => $detail->unit_id,
                    'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                    'conversion_factor'                        => $detail->conversion_factor,
                    'quantity'                                 => $detail->transfer_quantity,
                    'base_quantity'                            => $base_quantity,
                    'unit_price'                               => $unit_cost,
                    'total_price'                              => $line_value,
                    'quantity_after'                           => $source_new_qty,
                    'avg_price_after'                          => $unit_cost,
                    'reference_id'                              => $transfer_note->transfer_note_id,
                    'reference_type'                            => ReferenceType::STOCK_TRANSFER,
                    'remarks'                                   => 'Sent on transfer note ' . $transfer_note->transfer_note_no . ' (out, in transit)',
                    'createdby_id'                              => Auth::id(),
                    'date_created'                              => now(),
                ]);
            }

            $transfer_note->update([
                'status'       => Status::IN_TRANSIT,
                'sentby_id'    => Auth::id(),
                'date_sent'    => now(),
                'updatedby_id' => Auth::id(),
                'date_updated' => now(),
            ]);

            DB::commit();

            return $transfer_note;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    /**
     * In Transit -> partially/fully Received. $obj['products'] is
     * [{transfer_note_detail_id, receive_quantity}, ...] - each line's
     * receive_quantity is capped at (transfer_quantity - received_quantity)
     * so this can never over-receive or double-receive an already-completed
     * line, and can be called repeatedly for partial receiving.
     */
    public function receive($obj)
    {
        DB::beginTransaction();

        try {
            $transfer_note = $this->model_transfer_note->getModel()::with('transferNoteDetails.product')
                ->findOrFail($obj['transfer_note_id']);

            if ($transfer_note->status !== Status::IN_TRANSIT) {
                throw new Exception('Only in-transit transfer notes can be received.');
            }

            $has_quantity = false;

            foreach ($obj['products'] as $product) {
                $detail = $transfer_note->transferNoteDetails
                    ->firstWhere('transfer_note_detail_id', $product['transfer_note_detail_id'] ?? null);

                if (!$detail) {
                    throw new Exception('One of the selected lines does not belong to this transfer note.');
                }

                $receive_quantity = (float) ($product['receive_quantity'] ?? 0);

                if ($receive_quantity < 0) {
                    throw new Exception('Received quantity cannot be negative.');
                }

                $remaining = (float) $detail->transfer_quantity - (float) $detail->received_quantity;

                if ($receive_quantity > $remaining) {
                    throw new Exception('Received quantity for "' . ($detail->product->name ?? 'a product') . '" exceeds the remaining quantity to receive.');
                }

                if ($receive_quantity <= 0) {
                    continue;
                }

                $has_quantity = true;

                $conversion_factor = $detail->conversion_factor > 0 ? $detail->conversion_factor : 1;
                $base_quantity = $receive_quantity * $conversion_factor;
                $unit_cost = $detail->unit_cost;
                $line_value = $base_quantity * $unit_cost;

                $destination_stock = ProductVariationStock::where('business_id', $transfer_note->business_id)
                    ->where('warehouse_id', $transfer_note->destination_warehouse_id)
                    ->where('product_id', $detail->product_id)
                    ->where('product_variation_id', $detail->product_variation_id)
                    ->lockForUpdate()
                    ->first();

                $destination_existing_qty = $destination_stock->quantity ?? 0;
                $destination_existing_avg = $destination_stock->avg_price ?? 0;
                $destination_new_qty = $destination_existing_qty + $base_quantity;
                $destination_new_avg = $destination_new_qty > 0
                    ? (($destination_existing_qty * $destination_existing_avg) + $line_value) / $destination_new_qty
                    : 0;

                if ($destination_stock) {
                    $destination_stock->update([
                        'quantity'  => $destination_new_qty,
                        'avg_price' => $destination_new_avg,
                    ]);
                } else {
                    $destination_stock = ProductVariationStock::create([
                        'product_variation_stock_id' => generateUuid(),
                        'business_id'                => $transfer_note->business_id,
                        'warehouse_id'               => $transfer_note->destination_warehouse_id,
                        'product_id'                 => $detail->product_id,
                        'product_variation_id'       => $detail->product_variation_id,
                        'quantity'                   => $destination_new_qty,
                        'avg_price'                  => $destination_new_avg,
                        'status'                     => 'active',
                        'createdby_id'               => Auth::id(),
                        'date_created'               => now(),
                    ]);
                }

                ProductVariationStockTransaction::create([
                    'product_variation_stock_transaction_id' => generateUuid(),
                    'transaction_date'                       => now(),
                    'transaction_type'                        => TransactionType::TRANSFER_IN,
                    'business_id'                             => $transfer_note->business_id,
                    'product_id'                              => $detail->product_id,
                    'product_variation_id'                    => $detail->product_variation_id,
                    'warehouse_id'                             => $transfer_note->destination_warehouse_id,
                    'unit_id'                                  => $detail->unit_id,
                    'product_variation_unit_conversion_id'     => $detail->product_variation_unit_conversion_id,
                    'conversion_factor'                        => $conversion_factor,
                    'quantity'                                 => $receive_quantity,
                    'base_quantity'                            => $base_quantity,
                    'unit_price'                               => $unit_cost,
                    'total_price'                              => $line_value,
                    'quantity_after'                           => $destination_new_qty,
                    'avg_price_after'                          => $destination_new_avg,
                    'reference_id'                              => $transfer_note->transfer_note_id,
                    'reference_type'                            => ReferenceType::STOCK_TRANSFER,
                    'remarks'                                   => 'Received on transfer note ' . $transfer_note->transfer_note_no . ' (in)',
                    'createdby_id'                              => Auth::id(),
                    'date_created'                              => now(),
                ]);

                $detail->update([
                    'received_quantity' => (float) $detail->received_quantity + $receive_quantity,
                    'updatedby_id'      => Auth::id(),
                    'date_updated'      => now(),
                ]);
            }

            if (!$has_quantity) {
                throw new Exception('Please enter a quantity to receive for at least one product.');
            }

            // Each $detail->update() above also mutates that same in-memory
            // model (Eloquent updates the instance, not just the row), so the
            // already-loaded relation reflects every change made in this call.
            $fully_received = $transfer_note->transferNoteDetails->every(
                fn ($detail) => (float) $detail->received_quantity >= (float) $detail->transfer_quantity
            );

            $transfer_note->update([
                'status'        => $fully_received ? Status::RECEIVED : Status::IN_TRANSIT,
                'receivedby_id' => Auth::id(),
                'date_received' => now(),
                'updatedby_id'  => Auth::id(),
                'date_updated'  => now(),
            ]);

            DB::commit();

            return $transfer_note;
        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }
    }

    public function delete($transfer_note_id)
    {
        DB::beginTransaction();

        try {
            $transfer_note = $this->model_transfer_note->getModel()::with($this->with)->findOrFail($transfer_note_id);

            if (!in_array($transfer_note->status, [Status::DRAFT, Status::IN_TRANSIT], true)) {
                throw new Exception('Only draft or in-transit transfer notes can be cancelled.');
            }

            $has_received_qty = $transfer_note->transferNoteDetails->sum('received_quantity') > 0;

            if ($has_received_qty) {
                throw new Exception('Cannot cancel a transfer note that has already been partially or fully received.');
            }

            if ($transfer_note->status === Status::IN_TRANSIT) {
                $this->reverseTransferOutPosting($transfer_note);
            }

            $transfer_note->update([
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
     * Reverses the TRANSFER_OUT ledger entries created by send() when an
     * in-transit (never-received) transfer note is cancelled. Idempotent: a
     * no-op if nothing active remains to reverse.
     */
    protected function reverseTransferOutPosting(TransferNote $transfer_note)
    {
        $stock_transactions = ProductVariationStockTransaction::where('reference_type', ReferenceType::STOCK_TRANSFER)
            ->where('reference_id', $transfer_note->transfer_note_id)
            ->where('transaction_type', TransactionType::TRANSFER_OUT)
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

        $stock_service = app(ProductVariationStockService::class);

        foreach ($stock_transactions as $transaction) {
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
        return $this->model_transfer_note->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }
}
