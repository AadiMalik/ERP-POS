<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestDetail;
use App\Models\PurchaseRequestQuotation;
use App\Models\PurchaseRequestQuotationDetail;
use App\Repository\Repository;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use App\Services\Concrete\Whatsapp\DTO\WhatsappData;
use App\Services\Concrete\Whatsapp\WhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

class PurchaseRequestService
{
    protected $model_purchase_request;
    protected $model_purchase_request_details;
    protected $model_purchase_request_quotation;
    protected $model_purchase_request_quotation_details;
    protected $email_service;
    protected $whatsapp_service;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'warehouse',
        'purchaseRequestDetails',
        'purchaseRequestDetails.product',
        'purchaseRequestDetails.product.productVariations',
        'purchaseRequestDetails.productVariation',
        'purchaseRequestDetails.unit',
    ];

    public function __construct()
    {
        $this->model_purchase_request = new Repository(new PurchaseRequest());
        $this->model_purchase_request_details = new Repository(new PurchaseRequestDetail());
        $this->model_purchase_request_quotation = new Repository(new PurchaseRequestQuotation());
        $this->model_purchase_request_quotation_details = new Repository(new PurchaseRequestQuotationDetail());
        $this->email_service = new EmailService();
        $this->whatsapp_service = new WhatsappService();
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
        if (isset($obj['warehouse_id']) && $obj['warehouse_id'] != 0 && $obj['warehouse_id'] != "") {
            $wh[] = ['warehouse_id', $obj['warehouse_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['purchase_request_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['purchase_request_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_purchase_request->getModel()::with($this->with)
            ->withCount('purchaseRequestDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('purchase_request_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('purchase_request_date', function ($item) {
                return !empty($item->purchase_request_date)
                    ? localDate($item->purchase_request_date)
                    : 'N/A';
            })
            ->addColumn('supplier', function ($item) {
                return isset($item->supplier) ? $item->supplier->code ?? '' . ' ' . $item->supplier->name ?? '' : 'N/A';
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
            ->addColumn('total', function ($item) {
                return currency($item->total ?? 0);
            })
            ->addColumn('status', function ($item) {

                $statuses = [
                    Status::PENDING   => ucfirst(Status::PENDING),
                    Status::APPROVED  => ucfirst(Status::APPROVED),
                    Status::QUOTATION_SENT => ucfirst(Status::QUOTATION_SENT),
                    Status::QUOTATION_RECEIVED => ucfirst(Status::QUOTATION_RECEIVED),
                    Status::CONVERTED => ucfirst(Status::CONVERTED),
                    Status::CANCELLED => ucfirst(Status::CANCELLED),
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_request_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                $html = "";

                if ($item->status == Status::PENDING) {
                    $html .= "
                        <a class='btn btn-icon btn-outline-primary mr-2'
                            href='" . route('purchase-request.edit', $item->purchase_request_id) . "'>
                            <i class='fa fa-pencil'></i>
                        </a>";
                }

                if ($item->status == Status::APPROVED || $item->status == Status::QUOTATION_SENT) {
                    $html .= "
                        <button
                            class='btn btn-icon btn-outline-success mr-2 sendQuotation'
                            data-id='{$item->purchase_request_id}'
                            title='Send RFQ'>
                            <i class='fa fa-paper-plane'></i>
                        </button>";
                }
                if ($item->status != Status::PENDING) {
                    $html .= "
                        <a class='btn btn-icon btn-outline-info mr-2'
                            href='" . route('purchase-request-quotation.index', [
                        'purchase_request_id' => $item->purchase_request_id
                    ]) . "'
                            title='Quotations'>
                            <i class='fa fa-file-text'></i>
                        </a>";
                }
                if ($item->status == Status::PENDING) {
                    $html .= "
                    <button
                        class='btn btn-icon btn-outline-danger'
                        data-id='{$item->purchase_request_id}'
                        id='deletePurchaseRequest'>
                        <i class='fa fa-trash'></i>
                    </button>";
                }

                return $html;
            })
            ->rawColumns(['purchase_request_date', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total',  'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_request_id'])) {

                $purchase_request = $this->model_purchase_request
                    ->getModel()::findOrFail($obj['purchase_request_id']);

                $purchase_request->update([
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_request_date'    => $obj['purchase_request_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'status'                 => status::PENDING,
                    'updatedby_id'           => Auth::user()->id,
                    'date_updated'           => now(),
                ]);

                // Remove previous items

                $this->model_purchase_request_details->getModel()::where('purchase_request_id', $purchase_request->purchase_request_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_request = $this->model_purchase_request->create([
                    'purchase_request_id'    => generateUuid(),
                    'business_id'            => $obj['business_id'],
                    'supplier_id'            => $obj['supplier_id'],
                    'warehouse_id'           => $obj['warehouse_id'],
                    'purchase_request_no'    => $obj['purchase_request_no'],
                    'purchase_request_date'  => $obj['purchase_request_date'],
                    'purchase_expected_date' => $obj['purchase_expected_date'],
                    'description'            => $obj['description'],
                    'status'                 => status::PENDING,
                    'createdby_id'           => Auth::user()->id,
                    'date_created'           => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['products'] as $product) {

                $this->model_purchase_request_details->create([

                    'purchase_request_detail_id'              => generateUuid(),
                    'purchase_request_id'                     => $purchase_request->purchase_request_id,
                    'product_id'                            => $product['product_id'],
                    'product_variation_id'                  => $product['product_variation_id'],
                    'unit_id'                               => $product['unit_id'],
                    'requested_quantity'                    => $product['requested_quantity']
                ]);
            }

            DB::commit();

            return true;
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($purchase_request_id)
    {
        return $this->model_purchase_request->with($this->with)->find($purchase_request_id);
    }

    public function status($obj)
    {
        return $this->model_purchase_request->update([
            'status' => $obj['status'],
            'updatedby_id' => Auth::user()->id,
            'date_updated' => now()
        ], $obj['purchase_request_id']);
    }

    public function delete($purchase_request_id)
    {
        return $this->model_purchase_request->update([
            'is_deleted' => 1,
            'status' => Status::CANCELLED,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $purchase_request_id);
    }

    public function getAll()
    {
        return $this->model_purchase_request->getModel()::where('is_deleted', 0)
            ->get();
    }
    public function getAllPending()
    {
        return $this->model_purchase_request->getModel()::where('status', Status::PENDING)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getAllApproved()
    {
        return $this->model_purchase_request->getModel()::whereIn('status', [Status::APPROVED, Status::QUOTATION_SENT, Status::QUOTATION_RECEIVED])
            ->where('is_deleted', 0)
            ->get();
    }
    public function getDetails($purchase_request_id)
    {
        try {
            $purchase_request = $this->model_purchase_request->getModel()::with($this->with)->findOrFail($purchase_request_id);

            $data = [
                'header' => [
                    'purchase_request_id' => $purchase_request->purchase_request_id,
                    'supplier_id' => $purchase_request->supplier_id,
                    'warehouse_id' => $purchase_request->warehouse_id,
                    'branch_id' => $purchase_request->branch_id,
                    'purchase_request_no' => $purchase_request->purchase_request_no,
                    'purchase_request_date' => localDate($purchase_request->purchase_request_date),
                    'purchase_expected_date' => localDate($purchase_request->purchase_expected_date),
                    'description' => $purchase_request->description,
                ],
                'details' => []
            ];

            foreach ($purchase_request->purchaseRequestDetails as $detail) {
                $productVariations = [];

                foreach ($detail->product->productVariations as $variation) {

                    $productVariations[] = [
                        'product_variation_id' => $variation->product_variation_id,
                        'name' => $variation->name,
                        'purchase_price' => $variation->purchase_price,
                        'unit_id' => optional($variation->purchase_unit)->unit_id,
                        'unit_name' => optional($variation->purchase_unit)->name,
                    ];
                }

                $data['details'][] = [
                    'purchase_request_detail_id' => $detail->purchase_request_detail_id,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? '',
                    'product_variation_id' => $detail->product_variation_id,
                    'product_variation_name' => $detail->productVariation->name ?? '',
                    'requested_quantity' => $detail->requested_quantity,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name ?? 'N/A',
                    'productVariations' => $productVariations
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getByBusiness($business_id)
    {
        return $this->model_purchase_request->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function sendQuotation(array $obj)
    {
        DB::beginTransaction();

        try {

            $purchase_request = $this->model_purchase_request->getModel()::with('purchaseRequestDetails')
                ->findOrFail($obj['purchase_request_id']);
            foreach ($obj['supplier_ids'] as $supplier_id) {

                // check already sent
                $exists = $this->model_purchase_request_quotation->getModel()::where(
                    'purchase_request_id',
                    $obj['purchase_request_id']
                )->where('supplier_id', $supplier_id)
                    ->where('is_deleted', 0)
                    ->exists();

                if ($exists) {
                    continue;
                }

                $purchase_request_quotation = $this->model_purchase_request_quotation->getModel()::create([
                    'purchase_request_quotation_id' => generateUuid(),
                    'purchase_request_id' => $obj['purchase_request_id'],
                    'supplier_id' => $supplier_id,
                    'branch_id' => auth()->user()->branch_id,
                    'business_id' => auth()->user()->business_id,
                    'purchase_request_quotation_no' => generateQuotationNo(),
                    'sent_date' => now(),
                    'status' => Status::SENT,
                    'description' => $purchase_request->description,
                    'tax' => 0,
                    'discount' => 0,
                    'tax_amount' => 0,
                    'discount_amount' => 0,
                    'subtotal' => 0,
                    'total' => 0,
                    'createdby_id' => auth()->user()->id,
                    'date_created' => now(),
                ]);

                $details = [];
                foreach ($purchase_request->purchaseRequestDetails as $item) {
                    $details[] = [
                        'purchase_request_quotation_detail_id' => generateUuid(),
                        'purchase_request_quotation_id' => $purchase_request_quotation->purchase_request_quotation_id,
                        'product_id' => $item->product_id,
                        'product_variation_id' => $item->product_variation_id,
                        'requested_quantity' => $item->requested_quantity,
                        'quoted_quantity' => 0,
                        'unit_id' => $item->unit_id,
                        'unit_price' => 0,
                        'discount' => 0,
                        'discount_amount' => 0,
                        'tax' => 0,
                        'tax_amount' => 0,
                        'subtotal' => 0,
                        'total' => 0,
                        'createdby_id' => auth()->user()->id,
                        'date_created' => now(),
                    ];
                }

                $this->model_purchase_request_quotation_details->getModel()::insert($details);

                //pdf generate
                $quotation = $this->model_purchase_request_quotation
                    ->getModel()::with([
                        'supplier',
                        'business',
                        'createdby',
                        'purchaseRequestQuotationDetails.product',
                        'purchaseRequestQuotationDetails.productVariation',
                        'purchaseRequestQuotationDetails.unit'
                    ])
                    ->find($purchase_request_quotation->purchase_request_quotation_id);

                $pdf = Pdf::loadView(
                    'admin.purchase_request_quotation.pdf.pdf',
                    compact('quotation')
                );

                $fileName = 'quotation_' . $quotation->purchase_request_quotation_no . '.pdf';

                $folder = public_path('uploads/quotations');

                if (!File::exists($folder)) {
                    File::makeDirectory($folder, 0755, true);
                }
                $path = $folder . '/' . $fileName;
                $pdf->save($path);

                $purchase_request_quotation->update([
                    'pdf_path' => $fileName
                ]);

                if ($obj['send_email'] === "1") {
                    $email = new EmailData([
                        'to' => $quotation->supplier->email,
                        'subject' => 'Purchase Request Quotation',
                        'body' => 'Please find attached quotation.',
                        'attachment' => public_path('uploads/quotations/' . $fileName),
                        'attachment_name' => 'Quotation.pdf'
                    ]);

                    $response = $this->email_service->send(
                        $purchase_request_quotation->business_id,
                        $email
                    );

                    if (!$response['status']) {

                        Log::error($response['message']);
                        DB::rollBack();
                        return [
                            'Status' => false,
                            'Message' => $response['message']
                        ];
                    }
                }

                if ($obj['send_whatsapp'] === "1") {
                    $whatsapp = new WhatsappData([

                        'phone' => $quotation->supplier->phone,

                        'message' => 'Please review attached quotation.',

                        'attachment' => public_path('uploads/quotations/' . $fileName),

                        'file_name' => 'Quotation.pdf'

                    ]);

                    $response = $this->whatsapp_service->send(

                        $quotation->business_id,

                        $whatsapp

                    );

                    if (!$response['status']) {

                        Log::error($response['message']);
                        DB::rollBack();
                        return [
                            'Status' => false,
                            'Message' => $response['message']
                        ];
                    }
                }
            }
            DB::commit();
            return [
                'Status' => true,
                'Message' => 'Quotation sent successfully.'
            ];
        } catch (Exception $ex) {
            DB::rollBack();
            return [
                'Status' => false,
                'Message' => $ex->getMessage()
            ];
        }
    }
}
