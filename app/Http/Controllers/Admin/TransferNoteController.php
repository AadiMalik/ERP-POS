<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Http\Controllers\Concerns\HandlesImportExport;
use App\Http\Controllers\Controller;
use App\Models\TransferNote;
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
    use HandlesImportExport;

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
        $this->middleware('permission:transfer-note.view')->only(['index', 'getData', 'details', 'sourceStock', 'availableSerialsForSend', 'inTransitSerials']);
        $this->middleware('permission:transfer-note.create')->only(['create']);
        $this->middleware('permission:transfer-note.create|transfer-note.edit')->only(['store']);
        $this->middleware('permission:transfer-note.edit')->only(['edit']);
        $this->middleware('permission:transfer-note.delete')->only(['destroy']);
        $this->middleware('permission:transfer-note.send')->only(['send']);
        $this->middleware('permission:transfer-note.receive')->only(['receive']);
        $this->middleware('permission:transfer-note.print')->only(['print']);
        $this->middleware('permission:transfer-note.import')->only(['importSample', 'importPreview', 'importConfirm']);
        $this->middleware('permission:transfer-note.export')->only(['export']);

        $this->transfer_note_service = $transfer_note_service;
        $this->business_service = $business_service;
        $this->product_service = $product_service;
        $this->warehouse_service = $warehouse_service;
        $this->unit_service = $unit_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    protected function importExportModuleKey(): string
    {
        return 'transfer-note';
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $warehouses = $this->warehouse_service->getAllActive();
        $statuses = [
            Status::DRAFT      => 'Draft',
            Status::IN_TRANSIT => 'In Transit',
            Status::RECEIVED   => 'Received',
            Status::CANCELLED  => ucfirst(Status::CANCELLED),
        ];

        return view('admin.transfer_note.index', compact('business', 'warehouses', 'statuses'));
    }

    /**
     * Mirrors OrderController::assertOrderAccessible() - the actual
     * authorization boundary for Send/Receive/Cancel. $branch_column is
     * 'branch_id' (source) for Send/Cancel, or 'destination_branch_id' for
     * Receive, so a source-branch user can never Receive and vice versa.
     */
    protected function assertTransferNoteAccessible($transfer_note_id, string $branch_column)
    {
        $allow_roles = [
            RoleNames::SUPERADMIN,
            RoleNames::BUSINESSADMIN,
            RoleNames::INVENTORYMANAGER,
            RoleNames::BRANCHADMIN,
            RoleNames::POSMANAGER,
        ];

        $accessible = applyRoleScope(
            TransferNote::where('transfer_note_id', $transfer_note_id),
            $allow_roles,
            'business_id',
            $branch_column
        )->exists();

        if (!$accessible) {
            abort(403, 'You are not authorized to perform this action on this transfer note.');
        }
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

        if (!$transfer_note || $transfer_note->status !== Status::DRAFT) {
            return redirect('admin/transfer-note')
                ->with('error', 'Only draft transfer notes can be edited.');
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

    public function availableSerialsForSend($transfer_note_detail_id)
    {
        try {
            $serials = $this->transfer_note_service->getAvailableSerialsForSend($transfer_note_detail_id);
            return $this->success(Message::SUCCESS, $serials);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function inTransitSerials($transfer_note_detail_id)
    {
        try {
            $serials = $this->transfer_note_service->getInTransitSerials($transfer_note_detail_id);
            return $this->success(Message::SUCCESS, $serials);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function send(Request $request, $transfer_note_id)
    {
        $this->assertTransferNoteAccessible($transfer_note_id, 'branch_id');

        $validate = Validator::make($request->all(), [
            'serials' => ['nullable', 'array'],
            'serials.*' => ['nullable', 'array'],
            'serials.*.*' => ['nullable', 'string', 'max:255'],
        ]);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->transfer_note_service->send($transfer_note_id, $request->input('serials', []));
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function receive(Request $request)
    {
        $rules = [
            'transfer_note_id' => 'required|exists:transfer_notes,transfer_note_id',
            'products' => 'required|array|min:1',
            'products.*.transfer_note_detail_id' => 'required|exists:transfer_note_details,transfer_note_detail_id',
            'products.*.receive_quantity' => 'required|numeric|min:0',
            'products.*.serial_numbers' => ['nullable', 'array'],
            'products.*.serial_numbers.*' => ['nullable', 'string', 'max:255'],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        $this->assertTransferNoteAccessible($request->transfer_note_id, 'destination_branch_id');

        try {
            $this->transfer_note_service->receive($request->all());
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($transfer_note_id)
    {
        $this->assertTransferNoteAccessible($transfer_note_id, 'branch_id');

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
