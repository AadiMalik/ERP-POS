<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\PurchaseRequestQuotation;
use App\Models\PurchaseRequestQuotationDetail;
use App\Repository\Repository;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use App\Services\Concrete\SMS\DTO\SMSData;
use App\Services\Concrete\SMS\SMSService;
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

class PurchaseRequestQuotationService
{
    protected $model_purchase_request_quotation;
    protected $model_purchase_request_quotation_details;
    protected $email_service;
    protected $whatsapp_service;
    protected $sms_service;
    protected $with = [
        'business',
        'branch',
        'supplier',
        'purchaseRequestQuotationDetails',
        'purchaseRequestQuotationDetails.product',
        'purchaseRequestQuotationDetails.product.productVariations',
        'purchaseRequestQuotationDetails.productVariation',
        'purchaseRequestQuotationDetails.unit',
    ];

    public function __construct()
    {
        $this->model_purchase_request_quotation = new Repository(new PurchaseRequestQuotation());
        $this->model_purchase_request_quotation_details = new Repository(new PurchaseRequestQuotationDetail());
        $this->email_service = new EmailService();
        $this->whatsapp_service = new WhatsappService();
        $this->sms_service = new SMSService();
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
        if (isset($obj['purchase_request_id']) && $obj['purchase_request_id'] != 0 && $obj['purchase_request_id'] != "") {
            $wh[] = ['purchase_request_id', $obj['purchase_request_id']];
        }
        if (isset($obj['status']) && $obj['status'] != 0 && $obj['status'] != "") {
            $wh[] = ['status', $obj['status']];
        }
        if (!empty($obj['start_date'])) {
            $wh[] = ['sent_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
        }

        if (!empty($obj['end_date'])) {
            $wh[] = ['sent_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
        }
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN
        ];
        $datatable = $this->model_purchase_request_quotation->getModel()::with($this->with)
            ->withCount('purchaseRequestQuotationDetails as total_products')
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderBy('sent_date', $orderBy);
        $datatable = applyRoleScope($datatable, $allow_roles);
        return DataTables::of($datatable)
            ->addColumn('sent_date', function ($item) {
                return !empty($item->sent_date)
                    ? localDate($item->sent_date)
                    : 'N/A';
            })
            ->addColumn('received_date', function ($item) {
                return !empty($item->received_date)
                    ? localDate($item->received_date)
                    : 'N/A';
            })
            ->addColumn('purchase_request', function ($item) {
                return $item->purchaseRequest->purchase_request_no ?? 'N/A';
            })
            ->addColumn('supplier', function ($item) {
                return $item->supplier->code ?? '' . ' ' . $item->supplier->name ?? '';
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
                    Status::SENT   => ucfirst(Status::SENT),
                    Status::RECEIVED  => ucfirst(Status::RECEIVED),
                    Status::SELECTED => ucfirst(Status::SELECTED),
                    Status::REJECTED => ucfirst(Status::REJECTED)
                ];

                $html = "<select class='form-select form-select-sm change-status'
                data-id='{$item->purchase_request_quotation_id}'>";

                foreach ($statuses as $value => $label) {
                    $selected = $item->status == $value ? 'selected' : '';
                    $html .= "<option value='{$value}' {$selected}>{$label}</option>";
                }

                $html .= "</select>";

                return $html;
            })
            ->addColumn('action', function ($item) {

                return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('purchase-request-quotation.edit', $item->purchase_request_quotation_id) . "'
                    id='editPurchaseRequestQuotation'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deletePurchaseRequestQuotation'
                    data-id='{$item->purchase_request_quotation_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
            })
            ->rawColumns(['sent_date', 'received_date', 'business', 'branch', 'warehouse', 'supplier', 'total_products', 'total',  'status', 'action'])
            ->make(true);
    }

    public function save($obj)
    {
        DB::beginTransaction();

        try {

            //====================================
            // Update
            //====================================

            if (!empty($obj['purchase_request_quotation_id'])) {

                $purchase_request_quotation = $this->model_purchase_request_quotation
                    ->getModel()::findOrFail($obj['purchase_request_quotation_id']);

                $purchase_request_quotation->update([
                    'business_id'               => $obj['business_id'],
                    'supplier_id'               => $obj['supplier_id'],
                    'received_date'             => $obj['received_date'],
                    'description'               => $obj['description'],
                    'subtotal'                  => $obj['subtotal'],
                    'discount_amount'           => $obj['discount_amount'],
                    'tax_amount'                => $obj['tax_amount'],
                    'total'                     => $obj['total'],
                    'status'                    => status::SENT,
                    'updatedby_id'              => Auth::user()->id,
                    'date_updated'              => now(),
                ]);

                // Remove previous items

                $this->model_purchase_request_quotation_details->getModel()::where('purchase_request_quotation_id', $purchase_request_quotation->purchase_request_quotation_id)
                    ->delete();
            }

            //====================================
            // Create
            //====================================

            else {

                $purchase_request_quotation = $this->model_purchase_request_quotation->create([
                    'purchase_request_quotation_id'         => generateUuid(),
                    'business_id'                           => $obj['business_id'],
                    'supplier_id'                           => $obj['supplier_id'],
                    'purchase_request_quotation_no'         => $obj['purchase_request_quotation_no'],
                    'sent_date'                             => now(),
                    'received_date'                         => $obj['received_date'],
                    'description'                           => $obj['description'],
                    'subtotal'                              => $obj['subtotal'],
                    'discount_amount'                       => $obj['discount_amount'],
                    'tax_amount'                            => $obj['tax_amount'],
                    'total'                                 => $obj['total'],
                    'status'                                => status::SENT,
                    'createdby_id'                          => Auth::user()->id,
                    'date_created'                          => now(),
                ]);
            }

            //====================================
            // Save Items
            //====================================

            foreach ($obj['products'] as $product) {

                $this->model_purchase_request_quotation_details->create([

                    'purchase_request_quotation_detail_id'  => generateUuid(),
                    'purchase_request_quotation_id'         => $purchase_request_quotation->purchase_request_quotation_id,
                    'product_id'                            => $product['product_id'],
                    'product_variation_id'                  => $product['product_variation_id'],
                    'unit_id'                               => $product['unit_id'],
                    'requested_quantity'                    => $product['requested_quantity'],
                    'quoted_quantity'                       => $product['quoted_quantity'],
                    'unit_price'                            => $product['unit_price'],
                    'tax'                                   => $product['tax'],
                    'tax_amount'                            => $product['tax_amount'],
                    'discount'                              => $product['discount'],
                    'discount_amount'                       => $product['discount_amount'],
                    'subtotal'                              => $product['subtotal'],
                    'total'                                 => $product['total'],
                    'createdby_id'                          => Auth::user()->id,
                    'date_created'                          => now(),
                ]);
            }

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

            if ($obj['send_email'] === 1) {
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

            if ($obj['send_whatsapp'] === 1) {
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

            if ($obj['send_sms'] === 1) {
                $productLines = [];

                foreach ($quotation->purchaseRequestQuotationDetails as $index => $item) {

                    if ($index == 2) {
                        break;
                    }

                    $product = $item->product?->name ?? '';

                    $variation = $item->productVariation?->name;

                    if (!empty($variation)) {
                        $product .= " ({$variation})";
                    }

                    $product .= " x{$item->requested_quantity}";

                    $productLines[] = $product;
                }

                $remaining = count($quotation->purchaseRequestQuotationDetails) - count($productLines);

                $message = "Quotation Request\n";
                $message .= "Business: {$quotation->business->name}\n";
                $message .= "Quotation: {$quotation->purchase_request_quotation_no}\n";
                $message .= implode(", ", $productLines);

                if ($remaining > 0) {
                    $message .= " +{$remaining} more";
                }
                $message .= "\n";
                $message .= $quotation->pdf_url;

                $sms = new SMSData([
                    'phone'   => $quotation->supplier->phone,
                    'message' => $message
                ]);

                $response = $this->sms_service->send(
                    $quotation->business_id,
                    $sms
                );

                if (!$response['status']) {

                    Log::error($response['message']);
                }
            }

            DB::commit();

            return [
                'Status' => true,
                'Message' => null
            ];
        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function getById($purchase_request_quotation_id)
    {
        return $this->model_purchase_request_quotation->with($this->with)->find($purchase_request_quotation_id);
    }

    public function status($obj)
    {
        return $this->model_purchase_request_quotation->update([
            'status' => $obj['status'],
            'updatedby_id' => Auth::user()->id,
            'date_updated' => now()
        ], $obj['purchase_request_quotation_id']);
    }

    public function delete($purchase_request_quotation_id)
    {
        return $this->model_purchase_request_quotation->update([
            'is_deleted' => 1,
            'status' => Status::REJECTED,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now()
        ], $purchase_request_quotation_id);
    }

    public function getAll()
    {
        return $this->model_purchase_request_quotation->getModel()::where('is_deleted', 0)
            ->get();
    }
    public function getAllSent()
    {
        return $this->model_purchase_request_quotation->getModel()::where('status', Status::SENT)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getAllSeleted()
    {
        return $this->model_purchase_request_quotation->getModel()::where('status', Status::SELECTED)
            ->where('is_deleted', 0)
            ->get();
    }
    public function getDetails($purchase_request_quotation_id)
    {
        try {
            $purchase_request_quotation = $this->model_purchase_request_quotation->getModel()::with($this->with)
                ->findOrFail($purchase_request_quotation_id);

            $data = [
                'header' => [
                    'purchase_request_quotation_id' => $purchase_request_quotation->purchase_request_quotation_id,
                    'purchase_request_id' => $purchase_request_quotation->purchase_request_id,
                    'supplier_id' => $purchase_request_quotation->supplier_id,
                    'supplier_reference_no' => $purchase_request_quotation->supplier_reference_no,
                    'business_id' => $purchase_request_quotation->business_id,
                    'branch_id' => $purchase_request_quotation->branch_id,
                    'purchase_request_quotation_no' => $purchase_request_quotation->purchase_request_quotation_no,
                    'sent_date' => localDate($purchase_request_quotation->sent_date),
                    'received_date' => localDate($purchase_request_quotation->received_date),
                    'description' => $purchase_request_quotation->description,
                    'subtotal' => decimal($purchase_request_quotation->subtotal),
                    'discount' => decimal($purchase_request_quotation->discount),
                    'discount_amount' => decimal($purchase_request_quotation->discount_amount),
                    'tax' => decimal($purchase_request_quotation->tax),
                    'tax_amount' => decimal($purchase_request_quotation->tax_amount),
                    'other_charge' => decimal($purchase_request_quotation->other_charge),
                    'total' => decimal($purchase_request_quotation->total),
                ],
                'details' => []
            ];

            foreach ($purchase_request_quotation->purchaseRequestQuotationDetails as $detail) {
                $conversions = [];
                if ($detail->productVariation) {
                    foreach ($detail->productVariation->productVariationUnitConversion as $conversion) {
                        $conversions[] = [
                            'product_variation_unit_conversion_id' => $conversion->product_variation_unit_conversion_id,
                            'from_unit_id' => $conversion->from_unit_id,
                            'from_unit_name' => $conversion->fromUnit->name ?? 'N/A',
                            'to_unit_id' => $conversion->to_unit_id,
                            'to_unit_name' => $conversion->toUnit->name ?? 'N/A',
                            'conversion_factor' => $conversion->conversion_factor,
                        ];
                    }
                }

                $data['details'][] = [
                    'purchase_request_quotation_detail_id' => $detail->purchase_request_quotation_detail_id,
                    'purchase_request_quotation_id' => $detail->purchase_request_quotation_id,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? '',
                    'product_variation_id' => $detail->product_variation_id,
                    'product_variation_name' => $detail->productVariation->name ?? '',
                    'requested_quantity' => $detail->requested_quantity,
                    'quoted_quantity' => $detail->quoted_quantity,
                    'ordered_quantity' => $detail->quoted_quantity,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name ?? 'N/A',
                    'unit_price' => $detail->unit_price,
                    'discount' => $detail->discount,
                    'discount_amount' => $detail->discount_amount,
                    'tax' => $detail->tax,
                    'tax_amount' => $detail->tax_amount,
                    'subtotal' => $detail->subtotal,
                    'total' => $detail->total,
                    'conversions' => $conversions
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getRecievedQuotationByPRId($purchase_request_id)
    {
        try {
            $purchase_request_quotation = $this->model_purchase_request_quotation->getModel()::with($this->with)
                ->where('purchase_request_id', $purchase_request_id)->where('status', Status::SELECTED)->first();
            if ($purchase_request_quotation == null) {
                return [];
            }
            $data = [
                'header' => [
                    'purchase_request_quotation_id' => $purchase_request_quotation->purchase_request_quotation_id,
                    'purchase_request_id' => $purchase_request_quotation->purchase_request_id,
                    'supplier_id' => $purchase_request_quotation->supplier_id,
                    'supplier_reference_no' => $purchase_request_quotation->supplier_reference_no,
                    'business_id' => $purchase_request_quotation->business_id,
                    'branch_id' => $purchase_request_quotation->branch_id,
                    'purchase_request_quotation_no' => $purchase_request_quotation->purchase_request_quotation_no,
                    'sent_date' => localDate($purchase_request_quotation->sent_date),
                    'received_date' => localDate($purchase_request_quotation->received_date),
                    'description' => $purchase_request_quotation->description,
                    'subtotal' => decimal($purchase_request_quotation->subtotal),
                    'discount' => decimal($purchase_request_quotation->discount),
                    'discount_amount' => decimal($purchase_request_quotation->discount_amount),
                    'tax' => decimal($purchase_request_quotation->tax),
                    'tax_amount' => decimal($purchase_request_quotation->tax_amount),
                    'other_charge' => decimal($purchase_request_quotation->other_charge),
                    'total' => decimal($purchase_request_quotation->total),
                ],
                'details' => []
            ];

            foreach ($purchase_request_quotation->purchaseRequestQuotationDetails as $detail) {
                $conversions = [];
                if ($detail->productVariation) {
                    foreach ($detail->productVariation->productVariationUnitConversion as $conversion) {
                        $conversions[] = [
                            'product_variation_unit_conversion_id' => $conversion->product_variation_unit_conversion_id,
                            'from_unit_id' => $conversion->from_unit_id,
                            'from_unit_name' => $conversion->fromUnit->name ?? 'N/A',
                            'to_unit_id' => $conversion->to_unit_id,
                            'to_unit_name' => $conversion->toUnit->name ?? 'N/A',
                            'conversion_factor' => $conversion->conversion_factor,
                        ];
                    }
                }
                $data['details'][] = [
                    'purchase_request_quotation_detail_id' => $detail->purchase_request_quotation_detail_id,
                    'purchase_request_quotation_id' => $detail->purchase_request_quotation_id,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? '',
                    'product_variation_id' => $detail->product_variation_id,
                    'product_variation_name' => $detail->productVariation->name ?? '',
                    'requested_quantity' => $detail->requested_quantity,
                    'ordered_quantity' => $detail->quoted_quantity,
                    'unit_id' => $detail->unit_id,
                    'unit_name' => $detail->unit->name ?? 'N/A',
                    'unit_price' => decimal($detail->unit_price),
                    'discount' => decimal($detail->discount),
                    'discount_amount' => decimal($detail->discount_amount),
                    'tax' => decimal($detail->tax),
                    'tax_amount' => decimal($detail->tax_amount),
                    'subtotal' => decimal($detail->subtotal),
                    'total' => decimal($detail->total),
                    'conversions' => $conversions
                ];
            }

            return $data;
        } catch (Exception $e) {
            throw $e;
        }
    }
    public function getByBusiness($business_id)
    {
        return $this->model_purchase_request_quotation->getModel()::with($this->with)
            ->where('business_id', $business_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getByPurchaseRequest($purchase_request_id)
    {
        return $this->model_purchase_request_quotation->getModel()::with($this->with)
            ->where('purchase_request_id', $purchase_request_id)
            ->where('is_deleted', 0)
            ->get();
    }

    public function getSelectedByPurchaseRequest($purchase_request_id)
    {
        return $this->model_purchase_request_quotation->getModel()::with($this->with)
            ->where('purchase_request_id', $purchase_request_id)
            ->where('status', Status::SELECTED)
            ->where('is_deleted', 0)
            ->get();
    }
}
