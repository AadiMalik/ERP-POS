<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\Status;
use App\Http\Controllers\Controller;
use App\Traits\ResponseAPI;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\ProductService;
use App\Services\Concrete\Admin\TransferNoteService;
use App\Services\Concrete\Admin\UnitService;
use App\Services\Concrete\Admin\WarehouseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class TransferNoteController extends Controller
{
    use ResponseAPI;

    protected $transfer_note_service;
    protected $business_service;
    protected $product_service;
    protected $warehouse_service;
    protected $unit_service;
    protected $document_send_log_service;

    public function __construct(
        TransferNoteService $transfer_note_service,
        BusinessService $business_service,
        ProductService $product_service,
        WarehouseService $warehouse_service,
        UnitService $unit_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->transfer_note_service = $transfer_note_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::PENDING   => ucfirst(Status::PENDING),
            Status::APPROVED  => ucfirst(Status::APPROVED),
            Status::CANCELLED => ucfirst(Status::CANCELLED),
        ];

        return view('admin.transfer_note.index', compact('business', 'warehouses', 'statuses'));
    }

    public function getData(Request $request)
    {
        return $this->transfer_note_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();
        $transfer_note_no = generateTransferNoteNo();

        return view('admin.transfer_note.create', compact('business', 'products', 'warehouses', 'units', 'transfer_note_no'));
    }

    public function edit($transfer_note_id)
    {
        $transfer_note = $this->transfer_note_service->getById($transfer_note_id);

        if (!$transfer_note || $transfer_note->status !== Status::PENDING) {
            return redirect('admin/transfer-note')
                ->with('error', 'Only pending transfer notes can be edited.');
        }

        $transfer_note_details = $this->transfer_note_service->getDetails($transfer_note_id);
        $business = $this->business_service->getAll();
        $products = $this->product_service->getAllActive();
        $warehouses = $this->warehouse_service->getAllActive();
        $units = $this->unit_service->getAllActive();

        return view('admin.transfer_note.create', compact('transfer_note', 'transfer_note_details', 'business', 'products', 'warehouses', 'units'));
    }

    public function store(Request $request)
    {
        $products = $request->input('products', []);
        foreach ($products as $index => $product) {
            if (($product['product_variation_unit_conversion_id'] ?? '') === '') {
                $products[$index]['product_variation_unit_conversion_id'] = null;
            }
        }
        $request->merge(['products' => $products]);

        $validator = Validator::make($request->all(), [
            'source_warehouse_id' => ['required', 'different:destination_warehouse_id', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'destination_warehouse_id' => ['required', Rule::exists('warehouses', 'warehouse_id')->where('is_deleted', 0)],
            'transfer_note_no' => [
                'required',
                Rule::unique('transfer_notes', 'transfer_note_no')
                    ->where('is_deleted', 0)
                    ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    ->ignore($request->transfer_note_id, 'transfer_note_id')
            ],
            'transfer_note_date' => ['required', 'date'],
            'reference' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', Rule::exists('products', 'product_id')->where('is_deleted', 0)],
            'products.*.product_variation_id' => ['required', Rule::exists('product_variations', 'product_variation_id')->where('is_deleted', 0)],
            'products.*.product_variation_unit_conversion_id' => ['nullable', Rule::exists('product_variation_unit_conversions', 'product_variation_unit_conversion_id')->where('is_deleted', 0)],
            'products.*.unit_id' => ['required', Rule::exists('units', 'unit_id')->where('is_deleted', 0)],
            'products.*.transfer_quantity' => ['required', 'numeric', 'min:0.0001'],
            'products.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $obj = $request->all();
            $obj['transfer_note_date'] = ($request->transfer_note_id)
                ? utcDate($request->transfer_note_date, true)
                : utcDate($request->transfer_note_date);

            $this->transfer_note_service->save($obj);

            return redirect('admin/transfer-note')
                ->with('success', empty($request->transfer_note_id) ? Message::SAVE : Message::UPDATE);
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function status(Request $request)
    {
        $rules = [
            'transfer_note_id' => 'required|exists:transfer_notes,transfer_note_id',
            'status' => 'required|in:' . Status::PENDING . ',' . Status::APPROVED . ',' . Status::CANCELLED,
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }
        try {
            $this->transfer_note_service->status($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($transfer_note_id)
    {
        try {
            $this->transfer_note_service->delete($transfer_note_id);
            return $this->success(Message::DELETE, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function details($transfer_note_id)
    {
        try {
            $transfer_note = $this->transfer_note_service->getDetails($transfer_note_id);
            return $this->success(Message::SUCCESS, $transfer_note);
        } catch (Exception $e) {
            return $this->error(Message::ERROR);
        }
    }

    public function sourceStock($warehouse_id)
    {
        try {
            $data = $this->transfer_note_service->getSourceStock($warehouse_id);
            return $this->success(Message::SUCCESS, $data);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function print($transfer_note_id)
    {
        $transfer_note = $this->transfer_note_service->getById($transfer_note_id);

        if (!$transfer_note) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $transfer_note->business_id,
                'transfer_note',
                $transfer_note_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.transfer_note.print.print', compact('transfer_note'));
    }
}
