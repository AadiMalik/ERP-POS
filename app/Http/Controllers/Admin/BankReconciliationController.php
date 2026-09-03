<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\BankReconciliationService;
use App\Services\Concrete\Admin\BranchService;
use App\Services\Concrete\Admin\BusinessService;
use App\Traits\ResponseAPI;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankReconciliationController extends Controller
{
    use ResponseAPI;

    protected $bank_reconciliation_service;
    protected $business_service;
    protected $branch_service;
    protected $account_service;

    public function __construct(
        BankReconciliationService $bank_reconciliation_service,
        BusinessService $business_service,
        BranchService $branch_service,
        AccountService $account_service
    ) {
        $this->middleware('permission:bank-reconciliation.view')->only(['index', 'getData', 'show', 'balances']);
        $this->middleware('permission:bank-reconciliation.create')->only(['create', 'store']);
        $this->middleware('permission:bank-reconciliation.edit')->only([
            'update', 'storeStatementLine', 'destroyStatementLine',
            'match', 'unmatch', 'ignore', 'unignore', 'suggestMatches', 'reopen',
        ]);
        $this->middleware('permission:bank-reconciliation.delete')->only(['destroy']);
        $this->middleware('permission:bank-reconciliation.complete')->only(['complete']);
        $this->middleware('permission:bank-reconciliation.import')->only(['importSample', 'import']);
        $this->middleware('permission:bank-reconciliation.print')->only(['print', 'pdf']);
        $this->middleware('module:bank-reconciliation');

        $this->bank_reconciliation_service = $bank_reconciliation_service;
        $this->business_service = $business_service;
        $this->branch_service = $branch_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        $business = $this->business_service->getAll();
        $accounts = $this->bank_reconciliation_service->getCashBankAccounts(Auth::user()->business_id);

        return view('admin.bank_reconciliation.index', compact('business', 'accounts'));
    }

    public function getData(Request $request)
    {
        return $this->bank_reconciliation_service->getData($request->all());
    }

    public function create()
    {
        $business = $this->business_service->getAll();
        $branches = $this->branch_service->getAllActive();
        $accounts = $this->bank_reconciliation_service->getCashBankAccounts(Auth::user()->business_id);

        return view('admin.bank_reconciliation.create', compact('business', 'branches', 'accounts'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_id' => 'required|uuid',
            'account_id' => 'required|uuid',
            'branch_id' => 'nullable|uuid',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'statement_opening_balance' => 'nullable|numeric',
            'statement_closing_balance' => 'required|numeric',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->create($validator->validated());

            return $this->success('Reconciliation started.', [
                'bank_reconciliation_id' => $rec->bank_reconciliation_id,
                'redirect' => route('bank-reconciliation.show', $rec->bank_reconciliation_id),
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return $this->error($e->getMessage());
        }
    }

    public function show(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $payload = $this->bank_reconciliation_service->workspacePayload($rec);

            return view('admin.bank_reconciliation.workspace', [
                'reconciliation' => $payload['reconciliation'],
                'balances' => $payload['balances'],
                'book_lines' => $payload['book_lines'],
                'statement_lines' => $payload['statement_lines'],
            ]);
        } catch (Exception $e) {
            return redirect()->route('bank-reconciliation.index')->with('error', $e->getMessage());
        }
    }

    public function update(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'statement_opening_balance' => 'nullable|numeric',
            'statement_closing_balance' => 'nullable|numeric',
            'period_from' => 'nullable|date',
            'period_to' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $rec = $this->bank_reconciliation_service->updateHeader($rec, $validator->validated());
            $balances = $this->bank_reconciliation_service->calculateBalances($rec);

            return $this->success('Reconciliation updated.', ['balances' => $balances, 'reconciliation' => $rec]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function balances(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            if ($rec->isDraft()) {
                $this->bank_reconciliation_service->refreshBalances($rec);
                $rec->refresh();
            }

            return $this->success('OK', $this->bank_reconciliation_service->calculateBalances($rec));
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function storeStatementLine(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric',
            'reference' => 'nullable|string|max:191',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $line = $this->bank_reconciliation_service->addStatementLine($rec, $validator->validated());
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Statement line added.', ['line' => $line, 'balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroyStatementLine(string $bank_reconciliation_id, string $bank_statement_line_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->deleteStatementLine($rec, $bank_statement_line_id);
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Statement line removed.', ['balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function match(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'bank_statement_line_id' => 'required|uuid',
            'journal_entry_detail_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->match(
                $rec,
                $validator->validated()['bank_statement_line_id'],
                $validator->validated()['journal_entry_detail_id']
            );
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Matched.', ['balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function unmatch(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'bank_statement_line_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->unmatch($rec, $validator->validated()['bank_statement_line_id']);
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Unmatched.', ['balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function ignore(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'bank_statement_line_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->ignore($rec, $validator->validated()['bank_statement_line_id']);
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Ignored.', ['balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function unignore(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'bank_statement_line_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->unignore($rec, $validator->validated()['bank_statement_line_id']);
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success('Restored to unmatched.', ['balances' => $balances]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function suggestMatches(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $suggestions = $this->bank_reconciliation_service->suggestMatches($rec);

            return $this->success('OK', ['suggestions' => $suggestions]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function complete(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $rec = $this->bank_reconciliation_service->complete($rec);

            return $this->success('Reconciliation completed.', [
                'bank_reconciliation_id' => $rec->bank_reconciliation_id,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function reopen(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $rec = $this->bank_reconciliation_service->reopen($rec);

            return $this->success('Reconciliation reopened.', [
                'bank_reconciliation_id' => $rec->bank_reconciliation_id,
            ]);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy(string $bank_reconciliation_id)
    {
        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $this->bank_reconciliation_service->destroy($rec);

            return $this->success('Reconciliation deleted.', null);
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function importSample(): StreamedResponse
    {
        $headers = ['date', 'amount', 'reference', 'description'];
        $sample = [
            ['2026-09-01', '1500.00', 'TRX-001', 'Customer deposit'],
            ['2026-09-02', '-250.00', 'CHQ-88', 'Supplier payment'],
        ];

        return response()->streamDownload(function () use ($headers, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($sample as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'bank-statement-sample.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request, string $bank_reconciliation_id)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt,xlsx,xls|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first());
        }

        try {
            $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
            $sheets = Excel::toArray(null, $request->file('file'));
            $sheet = $sheets[0] ?? [];
            if (count($sheet) < 2) {
                return $this->error('File has no data rows. Expected header + at least one row.');
            }

            $header = array_map(function ($h) {
                return strtolower(trim((string) $h));
            }, $sheet[0]);

            $map = [];
            foreach ($header as $i => $col) {
                if (in_array($col, ['date', 'transaction_date', 'txn_date'], true)) {
                    $map['date'] = $i;
                } elseif ($col === 'amount') {
                    $map['amount'] = $i;
                } elseif (in_array($col, ['reference', 'ref', 'reference_no'], true)) {
                    $map['reference'] = $i;
                } elseif (in_array($col, ['description', 'details', 'narration'], true)) {
                    $map['description'] = $i;
                }
            }

            if (!isset($map['date']) || !isset($map['amount'])) {
                return $this->error('File must include date and amount columns.');
            }

            $rows = [];
            for ($r = 1; $r < count($sheet); $r++) {
                $row = $sheet[$r];
                if (!is_array($row) || (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0)) {
                    continue;
                }
                $rows[] = [
                    'date' => $row[$map['date']] ?? null,
                    'amount' => $row[$map['amount']] ?? null,
                    'reference' => isset($map['reference']) ? ($row[$map['reference']] ?? null) : null,
                    'description' => isset($map['description']) ? ($row[$map['description']] ?? null) : null,
                ];
            }

            $result = $this->bank_reconciliation_service->importStatementLines($rec, $rows);
            $balances = $this->bank_reconciliation_service->calculateBalances($rec->fresh());

            return $this->success(
                "Imported {$result['imported']} line(s)" . ($result['skipped'] ? ", skipped {$result['skipped']}" : '') . '.',
                array_merge($result, ['balances' => $balances])
            );
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return $this->error($e->getMessage());
        }
    }

    public function print(string $bank_reconciliation_id)
    {
        $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
        $payload = $this->bank_reconciliation_service->workspacePayload($rec);

        return view('admin.bank_reconciliation.print.print', $payload);
    }

    public function pdf(string $bank_reconciliation_id)
    {
        $rec = $this->bank_reconciliation_service->getById($bank_reconciliation_id);
        $payload = $this->bank_reconciliation_service->workspacePayload($rec);

        return Pdf::loadView('admin.bank_reconciliation.pdf', $payload)
            ->setPaper('a4', 'portrait')
            ->stream('bank-reconciliation.pdf');
    }
}
