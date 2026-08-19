<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\DocumentSendLogService;
use App\Services\Concrete\Admin\JournalEntryService;
use App\Services\Concrete\Admin\JournalService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JournalEntryController extends Controller
{
    use ResponseAPI;

    protected $journal_entry_service;
    protected $journal_service;
    protected $account_service;
    protected $business_service;
    protected $branch_service;
    protected $document_send_log_service;

    public function __construct(
        JournalEntryService $journal_entry_service,
        JournalService $journal_service,
        AccountService $account_service,
        BusinessService $business_service,
        BranchService $branch_service,
        DocumentSendLogService $document_send_log_service
    ) {
        $this->middleware('permission:journal-entry.view')->only(['index', 'getData', 'detail', 'getEntryNo']);
        $this->middleware('permission:journal-entry.create')->only(['create']);
        $this->middleware('permission:journal-entry.edit')->only(['edit']);
        $this->middleware('permission:journal-entry.create|journal-entry.edit')->only(['store']);
        $this->middleware('permission:journal-entry.delete')->only(['destroy']);
        $this->middleware('permission:journal-entry.print')->only(['print']);
        $this->middleware('permission:journal-entry.post')->only(['status']);
        $this->middleware('module:journal-entry');

        $this->journal_entry_service = $journal_entry_service;
        $this->journal_service = $journal_service;
        $this->account_service = $account_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->document_send_log_service = $document_send_log_service;
    }

    public function index()
    {
        $journals = $this->journal_service->getAll();
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.journal_entry.index', compact('journals', 'business', 'branches'));
    }

    public function getData(Request $request)
    {
        return $this->journal_entry_service->getData($request->all());
    }

    public function create()
    {
        $journals = $this->journal_service->getAll();
        $accounts = $this->account_service->getAllChild();
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        return view('admin.journal_entry.create', compact('journals', 'accounts', 'business', 'branches'));
    }

    public function edit($journal_entry_id)
    {
        $journals = $this->journal_service->getAll();
        $accounts = $this->account_service->getAllChild();
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $journal_entry = $this->journal_entry_service->getById($journal_entry_id);
        return view('admin.journal_entry.create', compact('journals', 'accounts', 'business', 'branches', 'journal_entry'));
    }

    public function getEntryNo(Request $request)
    {
        $entry_no = generateJVNum($request->journal_id);

        return $this->success(Message::SUCCESS, $entry_no);
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $data['details'] = json_decode($request->details, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->validationResponse('Invalid details data.');
        }
        $rules = [
            'branch_id' => 'nullable|exists:branches,branch_id',
            'journal_entry_id' => 'nullable|exists:journal_entries,journal_entry_id',
            'journal_id' => 'required|exists:journals,journal_id',
            'entry_no' => [
                'required',
                Rule::unique('journal_entries', 'entry_no')
                    ->where(
                        fn($q) => $q->where('is_deleted', 0)
                            ->where('business_id', $request->business_id ?? Auth::user()->business_id)
                    )
                    ->ignore($request->journal_entry_id, 'journal_entry_id'),
            ],
            'entry_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'details' => 'required|array|min:2',
            'details.*.account_id' => 'required|exists:accounts,account_id',
            'details.*.debit' => 'required|numeric|min:0',
            'details.*.credit' => 'required|numeric|min:0',
            'details.*.description' => 'nullable|string',
            'details.*.reference_no' => 'nullable|string|max:100',
            'details.*.cheque_date' => 'nullable|date',
            'details.*.cheque_no' => 'nullable|string|max:100',
            'details.*.bill_no' => 'nullable|string|max:100',
        ];
        $validator = Validator::make($data, $rules);
        $validator->after(function ($validator) use ($data) {
            $totalDebit = collect($data['details'])->sum(fn($r) => (float)$r['debit']);
            $totalCredit = collect($data['details'])->sum(fn($r) => (float)$r['credit']);
            if ($totalDebit != $totalCredit) {
                $validator->errors()->add('details', 'Total Debit and Credit must be equal.');
            }
            foreach ($data['details'] as $index => $row) {
                if ((float)$row['debit'] == 0 && (float)$row['credit'] == 0) {
                    $validator->errors()->add(
                        "details.$index",
                        "Either Debit or Credit is required on row " . ($index + 1)
                    );
                }
                if ((float)$row['debit'] > 0 && (float)$row['credit'] > 0) {
                    $validator->errors()->add(
                        "details.$index",
                        "Both Debit and Credit cannot have values on row " . ($index + 1)
                    );
                }
            }
        });
        if ($validator->fails()) {
            return $this->validationResponse($validator->errors()->first());
        }
        try {
            $obj = [
                'journal_entry_id' => $request->journal_entry_id,
                'journal_id' => $request->journal_id,
                'entry_no' => $request->entry_no,
                'entry_date' => $request->entry_date,
                'reference_no' => $request->reference_no,
                'description' => $request->description,
                'branch_id' => $request->branch_id ?? null,
                'business_id' => $request->business_id ??  Auth::user()->business_id,
                'details' => $data['details'],
            ];
            $journal_entry = $this->journal_entry_service->save($obj);

            return $this->success(
                empty($request->journal_entry_id) ? Message::SAVE : Message::UPDATE,
                $journal_entry
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function detail(Request $request)
    {
        $journal_entry_detail = $this->journal_entry_service->getDetailsById($request->journal_entry_id);
        return $this->success(Message::SUCCESS, $journal_entry_detail);
    }

    public function status(Request $request)
    {
        $rules = [
            'journal_entry_id' => 'required|exists:journal_entries,journal_entry_id',
            'status'           => 'required|in:pending,posted',
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }

        try {
            $this->journal_entry_service->changeStatus($request->journal_entry_id, $request->status);
            return $this->success(Message::STATUS, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($journal_entry_id)
    {
        try {

            $this->journal_entry_service->delete($journal_entry_id);

            return $this->success(
                Message::DELETE,
                []
            );
        } catch (Exception $e) {

            return $this->error(
                Message::ERROR
            );
        }
    }

    public function print($journal_entry_id)
    {
        $journal_entry = $this->journal_entry_service->getById($journal_entry_id);

        if (!$journal_entry) {
            abort(404);
        }

        try {
            $this->document_send_log_service->log(
                $journal_entry->business_id,
                'journal_entry',
                $journal_entry_id,
                'print',
                null,
                'sent',
                null,
                Auth::id()
            );
        } catch (Exception $e) {
            Log::warning('Print audit log failed: ' . $e->getMessage());
        }

        return view('admin.journal_entry.print.print', compact('journal_entry'));
    }
}
