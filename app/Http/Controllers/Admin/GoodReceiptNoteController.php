<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\GrnService;
use App\Services\Concrete\Admin\PurchaseService;
use App\Services\Concrete\Admin\SupplierService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class GoodReceiptNoteController extends Controller
{
    use ResponseAPI;

    protected $grn_service;
    protected $purchase_service;
    protected $business_service;
    protected $supplier_service;
    protected $warehouse_service;

    public function __construct(
        GrnService $grn_service,
        PurchaseService $purchase_service,
        BusinessService $business_service,
        SupplierService $supplier_service,
        WarehouseService $warehouse_service
    ) {
        $this->grn_service = $grn_service;
        $this->purchase_service = $purchase_service;
        $this->business_service = $business_service;
        $this->supplier_service = $supplier_service;
        $this->warehouse_service = $warehouse_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $suppliers = $this->supplier_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $purchases = $this->grn_service->getEligiblePurchases();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.good_receipt_note.index', compact('business', 'suppliers', 'warehouses', 'purchases', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->grn_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $purchases = $this->grn_service->getEligiblePurchases();
        $good_receipt_note_no = generateGRNNo();

        return view('admin.good_receipt_note.create', compact('business', 'purchases', 'good_receipt_note_no'));
    }

    public function edit($good_receipt_note_id)
    {
        $grn = $this->grn_service->getById($good_receipt_note_id);

        if (!$grn || $grn->status !== Status::PENDING) {
            return redirect('admin/good-receipt-note')
                ->with('error', 'Only pending GRNs can be edited.');
        }

        $grn_details = $this->grn_service->getDetails($good_receipt_note_id);
        $business = $this->business_service->getAll();
        $purchases = $this->grn_service->getEligiblePurchases();
        // The GRN's own purchase must always be selectable while editing,
        // even if it has since become fully received by another GRN.
        if ($grn->purchase && !$purchases->contains('purchase_id', $grn->purchase_id)) {
            $purchases->push($grn->purchase);
        }

        return view('admin.good_receipt_note.create', compact('grn', 'grn_details', 'business', 'purchases'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'purchase_id' => ['required', Rule::exists('purchases', 'purchase_id')->where('is_deleted', 0)],
            'good_receipt_note_no' => [
                'required',
                Rule::unique('good_receipt_notes', 'good_receipt_note_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->good_receipt_note_id, 'good_receipt_note_id')
            ],
            'good_receipt_note_date' => ['required', 'date'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.purchase_detail_id' => ['required', Rule::exists('purchase_details', 'purchase_detail_id')],
            'products.*.received_quantity' => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['good_receipt_note_date'] = ($request->good_receipt_note_id)
                ? utcDate($request->good_receipt_note_date, true)
                : utcDate($request->good_receipt_note_date);

            $this->grn_service->save($obj);

            return redirect('admin/good-receipt-note')
                ->with('success', empty($request->good_receipt_note_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'good_receipt_note_id' => 'required|exists:good_receipt_notes,good_receipt_note_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->grn_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($good_receipt_note_id)
    {
        try {
            $this->grn_service->delete($good_receipt_note_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($good_receipt_note_id)
    {
        try {
            $grn = $this->grn_service->getDetails($good_receipt_note_id);
            return $this->success(Message::SUCCESS, $grn);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function getPurchaseDetails($purchase_id)
    {
        try {
            $data = $this->grn_service->getReceivableLines($purchase_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }
}
