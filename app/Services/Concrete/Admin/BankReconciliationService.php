<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\Account;
use App\Models\AccountingSetting;
use App\Models\BankReconciliation;
use App\Models\BankStatementLine;
use App\Models\JournalEntryDetail;
use App\Repository\Repository;
use App\Services\Concrete\Admin\Reports\Accounting\AccountClassifier;
use App\Services\Concrete\Admin\Reports\Accounting\AccountingLedgerQueryService;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BankReconciliationService
{
    use Auditable;

    protected const DIFFERENCE_TOLERANCE = 0.01;

    protected $model_bank_reconciliation;
    protected $ledger_query_service;
    protected $classifier;

    public function __construct(
        AccountingLedgerQueryService $ledger_query_service,
        AccountClassifier $classifier
    ) {
        $this->model_bank_reconciliation = new Repository(new BankReconciliation());
        $this->ledger_query_service = $ledger_query_service;
        $this->classifier = $classifier;
    }

    public function getData(array $obj)
    {
        $wh = [];
        if (!empty($obj['business_id'])) {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['account_id'])) {
            $wh[] = ['account_id', $obj['account_id']];
        }
        if (!empty($obj['status'])) {
            $wh[] = ['status', $obj['status']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $query = $this->model_bank_reconciliation->getModel()::with(['account', 'business', 'completedBy'])
            ->where($wh)
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        $query = applyRoleScope($query, $allow_roles);

        return DataTables::of($query)
            ->addColumn('account', fn ($item) => ($item->account->code ?? '') . ' - ' . ($item->account->name ?? ''))
            ->addColumn('period', function ($item) {
                return Carbon::parse($item->period_from)->format('d-m-Y')
                    . ' to '
                    . Carbon::parse($item->period_to)->format('d-m-Y');
            })
            ->addColumn('statement_closing', fn ($item) => number_format((float) $item->statement_closing_balance, 2))
            ->addColumn('difference', function ($item) {
                $diff = $item->status === 'draft'
                    ? $this->calculateBalances($item)['difference']
                    : (float) $item->difference;

                $class = abs($diff) < self::DIFFERENCE_TOLERANCE ? 'text-success' : 'text-danger';

                return '<span class="' . $class . '">' . number_format($diff, 2) . '</span>';
            })
            ->addColumn('status_badge', function ($item) {
                return $item->status === 'completed'
                    ? '<span class="badge bg-success">Completed</span>'
                    : '<span class="badge bg-warning">Draft</span>';
            })
            ->addColumn('completed_info', function ($item) {
                if ($item->status !== 'completed') {
                    return '—';
                }
                $who = $item->completedBy->name ?? 'N/A';
                $when = $item->completed_at ? Carbon::parse($item->completed_at)->format('d-m-Y H:i') : '';

                return e($who) . '<br><small>' . e($when) . '</small>';
            })
            ->addColumn('action', function ($item) {
                $id = $item->bank_reconciliation_id;
                $workspace = route('bank-reconciliation.show', $id);
                $print = route('bank-reconciliation.print', $id);
                $html = "<a class='btn btn-icon btn-outline-primary me-1' href='{$workspace}' title='Open'><i class='fa fa-eye'></i></a>";
                $html .= "<a class='btn btn-icon btn-outline-secondary me-1' target='_blank' href='{$print}' title='Print'><i class='fa fa-print'></i></a>";
                if ($item->status === 'draft') {
                    $html .= "<a class='btn btn-icon btn-outline-danger' id='deleteBankReconciliation' data-id='{$id}' title='Delete'><i class='fa fa-trash'></i></a>";
                }

                return $html;
            })
            ->rawColumns(['difference', 'status_badge', 'completed_info', 'action'])
            ->make(true);
    }

    public function getById(string $id): BankReconciliation
    {
        return $this->model_bank_reconciliation->getModel()::with([
            'account.accountType',
            'account.accountSubType',
            'business',
            'branch',
            'completedBy',
            'createdBy',
            'statementLines',
        ])->where('is_deleted', 0)->findOrFail($id);
    }

    public function getCashBankAccounts(?string $businessId): Collection
    {
        $settings = AccountingSetting::where('business_id', $businessId)->first();

        return app(AccountService::class)->getAllActive($businessId)
            ->filter(fn (Account $account) => $this->classifier->isCashOrBank($account, $settings))
            ->values();
    }

    public function assertCashOrBankAccount(string $accountId, string $businessId): Account
    {
        $account = Account::with(['accountType', 'accountSubType'])
            ->where('account_id', $accountId)
            ->where('business_id', $businessId)
            ->where('is_deleted', 0)
            ->firstOrFail();

        $settings = AccountingSetting::where('business_id', $businessId)->first();
        if (!$this->classifier->isCashOrBank($account, $settings)) {
            throw new Exception('Selected account is not a cash or bank account.');
        }

        return $account;
    }

    public function create(array $data): BankReconciliation
    {
        $businessId = $data['business_id'];
        $accountId = $data['account_id'];

        $this->assertCashOrBankAccount($accountId, $businessId);

        $existingDraft = BankReconciliation::where('business_id', $businessId)
            ->where('account_id', $accountId)
            ->where('status', 'draft')
            ->where('is_deleted', 0)
            ->first();

        if ($existingDraft) {
            throw new Exception('A draft reconciliation already exists for this account. Continue or delete it first.');
        }

        $periodFrom = Carbon::parse($data['period_from'])->startOfDay();
        $periodTo = Carbon::parse($data['period_to'])->startOfDay();
        if ($periodTo->lt($periodFrom)) {
            throw new Exception('Period end date must be on or after the start date.');
        }

        $opening = array_key_exists('statement_opening_balance', $data) && $data['statement_opening_balance'] !== null && $data['statement_opening_balance'] !== ''
            ? (float) $data['statement_opening_balance']
            : $this->lastCompletedClosingBalance($businessId, $accountId);

        $rec = new BankReconciliation();
        $rec->bank_reconciliation_id = generateUuid();
        $rec->business_id = $businessId;
        $rec->branch_id = !empty($data['branch_id']) ? $data['branch_id'] : null;
        $rec->account_id = $accountId;
        $rec->period_from = $periodFrom->toDateString();
        $rec->period_to = $periodTo->toDateString();
        $rec->statement_opening_balance = $opening;
        $rec->statement_closing_balance = (float) $data['statement_closing_balance'];
        $rec->status = 'draft';
        $rec->notes = $data['notes'] ?? null;
        $rec->is_deleted = 0;
        $rec->createdby_id = Auth::id();
        $rec->date_created = now();

        $balances = $this->calculateBalances($rec);
        $rec->book_balance = $balances['book_balance'];
        $rec->adjusted_book_balance = $balances['adjusted_book_balance'];
        $rec->difference = $balances['difference'];
        $rec->save();

        $this->logActivity('bank-reconciliation', $rec->bank_reconciliation_id, 'created', null, $rec->toArray(), null, $rec->business_id, $rec->branch_id);

        return $rec;
    }

    public function updateHeader(BankReconciliation $rec, array $data): BankReconciliation
    {
        $this->assertDraft($rec);

        if (isset($data['statement_closing_balance'])) {
            $rec->statement_closing_balance = (float) $data['statement_closing_balance'];
        }
        if (array_key_exists('statement_opening_balance', $data) && $data['statement_opening_balance'] !== null && $data['statement_opening_balance'] !== '') {
            $rec->statement_opening_balance = (float) $data['statement_opening_balance'];
        }
        if (array_key_exists('notes', $data)) {
            $rec->notes = $data['notes'];
        }

        $hasMatches = $rec->statementLines()->where('match_status', 'matched')->exists();
        if (!$hasMatches) {
            if (!empty($data['period_from'])) {
                $rec->period_from = Carbon::parse($data['period_from'])->toDateString();
            }
            if (!empty($data['period_to'])) {
                $rec->period_to = Carbon::parse($data['period_to'])->toDateString();
            }
            if (Carbon::parse($rec->period_to)->lt(Carbon::parse($rec->period_from))) {
                throw new Exception('Period end date must be on or after the start date.');
            }
        }

        $rec->updatedby_id = Auth::id();
        $rec->date_updated = now();
        $this->refreshBalances($rec);

        $this->logActivity('bank-reconciliation', $rec->bank_reconciliation_id, 'updated', null, $rec->toArray(), null, $rec->business_id, $rec->branch_id);

        return $rec;
    }

    public function lastCompletedClosingBalance(string $businessId, string $accountId): float
    {
        $last = BankReconciliation::where('business_id', $businessId)
            ->where('account_id', $accountId)
            ->where('status', 'completed')
            ->where('is_deleted', 0)
            ->orderByDesc('period_to')
            ->orderByDesc('completed_at')
            ->first();

        return $last ? (float) $last->statement_closing_balance : 0.0;
    }

    /**
     * Book lines eligible for this session: posted cash/bank detail lines dated
     * on or before period_to that are either unmatched or matched in this session.
     */
    public function bookLines(BankReconciliation $rec, ?string $filter = null): Collection
    {
        $periodTo = Carbon::parse($rec->period_to)->toDateString();
        $periodFrom = Carbon::parse($rec->period_from)->toDateString();

        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->leftJoin('journals', 'journals.journal_id', '=', 'journal_entries.journal_id')
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.business_id', $rec->business_id)
            ->where('journal_entry_details.account_id', $rec->account_id)
            ->whereDate('journal_entries.entry_date', '<=', $periodTo)
            ->where(function ($q) use ($rec) {
                $q->where(function ($inner) {
                    $inner->where('journal_entry_details.is_reconciled', 0)
                        ->orWhereNull('journal_entry_details.is_reconciled');
                })->orWhere('journal_entry_details.bank_reconciliation_id', $rec->bank_reconciliation_id);
            });

        if (!empty($rec->branch_id)) {
            $query->where('journal_entries.branch_id', $rec->branch_id);
        }

        if ($filter === 'unmatched') {
            $query->where(function ($q) {
                $q->where('journal_entry_details.is_reconciled', 0)
                    ->orWhereNull('journal_entry_details.is_reconciled');
            });
        } elseif ($filter === 'matched') {
            $query->where('journal_entry_details.is_reconciled', 1)
                ->where('journal_entry_details.bank_reconciliation_id', $rec->bank_reconciliation_id);
        }

        $query = applyRoleScope($query, [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN], 'journal_entries.business_id', 'journal_entries.branch_id');

        return $query->orderBy('journal_entries.entry_date')
            ->orderBy('journal_entries.entry_no')
            ->get([
                'journal_entry_details.journal_entry_detail_id',
                'journal_entry_details.account_id',
                'journal_entry_details.debit',
                'journal_entry_details.credit',
                'journal_entry_details.description as detail_description',
                'journal_entry_details.is_reconciled',
                'journal_entry_details.bank_reconciliation_id',
                'journal_entries.journal_entry_id',
                'journal_entries.entry_no',
                'journal_entries.entry_date',
                'journal_entries.reference_no',
                'journal_entries.description as entry_description',
                'journal_entries.source_type',
                'journals.short as voucher_short',
                'journals.name as voucher_name',
            ])
            ->map(function ($row) use ($periodFrom, $periodTo) {
                $row->is_reconciled = (bool) $row->is_reconciled;
                $row->signed_amount = (float) $row->debit - (float) $row->credit;
                $entryDate = Carbon::parse($row->entry_date)->toDateString();
                $row->in_period = $entryDate >= $periodFrom && $entryDate <= $periodTo;

                return $row;
            });
    }

    public function statementLines(BankReconciliation $rec, ?string $filter = null): Collection
    {
        $query = $rec->statementLines()->orderBy('transaction_date')->orderBy('date_created');

        if ($filter === 'unmatched') {
            $query->where('match_status', 'unmatched');
        } elseif ($filter === 'matched') {
            $query->where('match_status', 'matched');
        } elseif ($filter === 'ignored') {
            $query->where('match_status', 'ignored');
        }

        return $query->get();
    }

    public function calculateBalances(BankReconciliation $rec): array
    {
        $periodTo = Carbon::parse($rec->period_to)->endOfDay();
        $filters = [
            'business_id' => $rec->business_id,
            'account_id' => $rec->account_id,
            'allow_roles' => [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN],
        ];
        if (!empty($rec->branch_id)) {
            $filters['branch_id'] = $rec->branch_id;
        }

        $totals = $this->ledger_query_service->totalBalances($filters, $periodTo);
        $accountTotals = $totals[$rec->account_id] ?? ['debit' => 0.0, 'credit' => 0.0];
        $bookBalance = round((float) $accountTotals['debit'] - (float) $accountTotals['credit'], 2);

        $unmatched = $this->unmatchedBookDetails($rec);
        $unmatchedDebit = round($unmatched->sum(fn ($d) => (float) $d->debit), 2);
        $unmatchedCredit = round($unmatched->sum(fn ($d) => (float) $d->credit), 2);

        $adjustedBook = round($bookBalance - $unmatchedDebit + $unmatchedCredit, 2);
        $difference = round((float) $rec->statement_closing_balance - $adjustedBook, 2);

        $statementLines = $rec->relationLoaded('statementLines')
            ? $rec->statementLines
            : $rec->statementLines()->get();

        return [
            'book_balance' => $bookBalance,
            'adjusted_book_balance' => $adjustedBook,
            'difference' => $difference,
            'unmatched_book_debit' => $unmatchedDebit,
            'unmatched_book_credit' => $unmatchedCredit,
            'unmatched_book_count' => $unmatched->count(),
            'matched_statement_count' => $statementLines->where('match_status', 'matched')->count(),
            'unmatched_statement_count' => $statementLines->where('match_status', 'unmatched')->count(),
            'ignored_statement_count' => $statementLines->where('match_status', 'ignored')->count(),
            'is_balanced' => abs($difference) < self::DIFFERENCE_TOLERANCE,
        ];
    }

    public function refreshBalances(BankReconciliation $rec): BankReconciliation
    {
        $balances = $this->calculateBalances($rec);
        $rec->book_balance = $balances['book_balance'];
        $rec->adjusted_book_balance = $balances['adjusted_book_balance'];
        $rec->difference = $balances['difference'];
        $rec->save();

        return $rec;
    }

    protected function unmatchedBookDetails(BankReconciliation $rec): Collection
    {
        $periodTo = Carbon::parse($rec->period_to)->toDateString();

        $query = JournalEntryDetail::query()
            ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
            ->where('journal_entries.is_deleted', 0)
            ->where('journal_entries.status', 'posted')
            ->where('journal_entries.business_id', $rec->business_id)
            ->where('journal_entry_details.account_id', $rec->account_id)
            ->whereDate('journal_entries.entry_date', '<=', $periodTo)
            ->where(function ($q) use ($rec) {
                $q->where('journal_entry_details.is_reconciled', 0)
                    ->orWhereNull('journal_entry_details.is_reconciled');
            });

        if (!empty($rec->branch_id)) {
            $query->where('journal_entries.branch_id', $rec->branch_id);
        }

        $query = applyRoleScope($query, [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN], 'journal_entries.business_id', 'journal_entries.branch_id');

        return $query->get([
            'journal_entry_details.journal_entry_detail_id',
            'journal_entry_details.debit',
            'journal_entry_details.credit',
        ]);
    }

    public function addStatementLine(BankReconciliation $rec, array $data): BankStatementLine
    {
        $this->assertDraft($rec);

        $line = new BankStatementLine();
        $line->bank_statement_line_id = generateUuid();
        $line->bank_reconciliation_id = $rec->bank_reconciliation_id;
        $line->transaction_date = Carbon::parse($data['transaction_date'])->toDateString();
        $line->amount = (float) $data['amount'];
        $line->reference = $data['reference'] ?? null;
        $line->description = $data['description'] ?? null;
        $line->match_status = 'unmatched';
        $line->is_deleted = 0;
        $line->createdby_id = Auth::id();
        $line->date_created = now();
        $line->save();

        $this->refreshBalances($rec);

        return $line;
    }

    public function deleteStatementLine(BankReconciliation $rec, string $lineId): void
    {
        $this->assertDraft($rec);

        $line = BankStatementLine::where('bank_statement_line_id', $lineId)
            ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        if ($line->match_status === 'matched') {
            $this->unmatch($rec, $line->bank_statement_line_id);
            $line->refresh();
        }

        $line->is_deleted = 1;
        $line->deletedby_id = Auth::id();
        $line->date_deleted = now();
        $line->save();

        $this->refreshBalances($rec);
    }

    public function importStatementLines(BankReconciliation $rec, array $rows): array
    {
        $this->assertDraft($rec);

        $imported = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($rec, $rows, &$imported, &$skipped, &$errors) {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2;
                $date = $row['date'] ?? $row['transaction_date'] ?? null;
                $amount = $row['amount'] ?? null;

                if (blank($date) || $amount === null || $amount === '') {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: date and amount are required.";
                    continue;
                }

                try {
                    $parsedDate = Carbon::parse($date)->toDateString();
                } catch (\Throwable $e) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: invalid date.";
                    continue;
                }

                if (!is_numeric($amount)) {
                    $skipped++;
                    $errors[] = "Row {$rowNum}: amount must be numeric.";
                    continue;
                }

                BankStatementLine::create([
                    'bank_statement_line_id' => generateUuid(),
                    'bank_reconciliation_id' => $rec->bank_reconciliation_id,
                    'transaction_date' => $parsedDate,
                    'amount' => (float) $amount,
                    'reference' => $row['reference'] ?? null,
                    'description' => $row['description'] ?? $row['details'] ?? null,
                    'match_status' => 'unmatched',
                    'is_deleted' => 0,
                    'createdby_id' => Auth::id(),
                    'date_created' => now(),
                ]);
                $imported++;
            }
        });

        $this->refreshBalances($rec);

        return compact('imported', 'skipped', 'errors');
    }

    public function match(BankReconciliation $rec, string $statementLineId, string $journalEntryDetailId): void
    {
        $this->assertDraft($rec);

        DB::transaction(function () use ($rec, $statementLineId, $journalEntryDetailId) {
            $line = BankStatementLine::where('bank_statement_line_id', $statementLineId)
                ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->firstOrFail();

            if ($line->match_status !== 'unmatched') {
                throw new Exception('Statement line is not available to match.');
            }

            $detail = JournalEntryDetail::query()
                ->join('journal_entries', 'journal_entries.journal_entry_id', '=', 'journal_entry_details.journal_entry_id')
                ->where('journal_entry_details.journal_entry_detail_id', $journalEntryDetailId)
                ->where('journal_entry_details.account_id', $rec->account_id)
                ->where('journal_entries.business_id', $rec->business_id)
                ->where('journal_entries.is_deleted', 0)
                ->where('journal_entries.status', 'posted')
                ->whereDate('journal_entries.entry_date', '<=', Carbon::parse($rec->period_to)->toDateString())
                ->lockForUpdate()
                ->first(['journal_entry_details.*']);

            if (!$detail) {
                throw new Exception('ERP transaction not found for this account/period.');
            }

            if ((bool) $detail->is_reconciled) {
                throw new Exception('ERP transaction is already reconciled.');
            }

            $alreadyMatched = BankStatementLine::where('matched_journal_entry_detail_id', $journalEntryDetailId)
                ->where('is_deleted', 0)
                ->where('match_status', 'matched')
                ->exists();
            if ($alreadyMatched) {
                throw new Exception('ERP transaction is already matched to another statement line.');
            }

            $signedBook = (float) $detail->debit - (float) $detail->credit;
            if (round(abs($signedBook - (float) $line->amount), 2) > self::DIFFERENCE_TOLERANCE) {
                throw new Exception('Amounts do not match. Statement amount must equal ERP debit−credit.');
            }

            $line->match_status = 'matched';
            $line->matched_journal_entry_detail_id = $detail->journal_entry_detail_id;
            $line->updatedby_id = Auth::id();
            $line->date_updated = now();
            $line->save();

            JournalEntryDetail::where('journal_entry_detail_id', $detail->journal_entry_detail_id)->update([
                'is_reconciled' => 1,
                'bank_reconciliation_id' => $rec->bank_reconciliation_id,
                'reconciled_at' => now(),
                'reconciled_by_id' => Auth::id(),
            ]);
        });

        $this->refreshBalances($rec);
    }

    public function unmatch(BankReconciliation $rec, string $statementLineId): void
    {
        $this->assertDraft($rec);

        DB::transaction(function () use ($rec, $statementLineId) {
            $line = BankStatementLine::where('bank_statement_line_id', $statementLineId)
                ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
                ->where('is_deleted', 0)
                ->lockForUpdate()
                ->firstOrFail();

            if ($line->match_status !== 'matched' || empty($line->matched_journal_entry_detail_id)) {
                throw new Exception('Statement line is not matched.');
            }

            JournalEntryDetail::where('journal_entry_detail_id', $line->matched_journal_entry_detail_id)
                ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
                ->update([
                    'is_reconciled' => 0,
                    'bank_reconciliation_id' => null,
                    'reconciled_at' => null,
                    'reconciled_by_id' => null,
                ]);

            $line->match_status = 'unmatched';
            $line->matched_journal_entry_detail_id = null;
            $line->updatedby_id = Auth::id();
            $line->date_updated = now();
            $line->save();
        });

        $this->refreshBalances($rec);
    }

    public function ignore(BankReconciliation $rec, string $statementLineId): void
    {
        $this->assertDraft($rec);

        $line = BankStatementLine::where('bank_statement_line_id', $statementLineId)
            ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        if ($line->match_status === 'matched') {
            throw new Exception('Unmatch the line before ignoring it.');
        }

        $line->match_status = 'ignored';
        $line->updatedby_id = Auth::id();
        $line->date_updated = now();
        $line->save();

        $this->refreshBalances($rec);
    }

    public function unignore(BankReconciliation $rec, string $statementLineId): void
    {
        $this->assertDraft($rec);

        $line = BankStatementLine::where('bank_statement_line_id', $statementLineId)
            ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
            ->where('is_deleted', 0)
            ->firstOrFail();

        if ($line->match_status !== 'ignored') {
            throw new Exception('Statement line is not ignored.');
        }

        $line->match_status = 'unmatched';
        $line->updatedby_id = Auth::id();
        $line->date_updated = now();
        $line->save();

        $this->refreshBalances($rec);
    }

    public function suggestMatches(BankReconciliation $rec): array
    {
        $statements = $this->statementLines($rec, 'unmatched');
        $books = $this->bookLines($rec, 'unmatched');
        $suggestions = [];

        foreach ($statements as $line) {
            $best = null;
            $bestScore = 0;

            foreach ($books as $book) {
                $signed = (float) $book->debit - (float) $book->credit;
                if (round(abs($signed - (float) $line->amount), 2) > self::DIFFERENCE_TOLERANCE) {
                    continue;
                }

                $score = 50;
                $dateDiff = abs(Carbon::parse($line->transaction_date)->diffInDays(Carbon::parse($book->entry_date), false));
                $dateDiff = abs($dateDiff);
                if ($dateDiff <= 3) {
                    $score += max(0, 30 - ($dateDiff * 10));
                } else {
                    continue;
                }

                $lineRef = strtolower(trim((string) ($line->reference ?? '')));
                $bookRef = strtolower(trim((string) ($book->reference_no ?? '')));
                if ($lineRef !== '' && $bookRef !== '' && ($lineRef === $bookRef || str_contains($bookRef, $lineRef) || str_contains($lineRef, $bookRef))) {
                    $score += 20;
                }

                $lineDesc = strtolower(trim((string) ($line->description ?? '')));
                $bookDesc = strtolower(trim((string) ($book->detail_description ?: $book->entry_description ?: '')));
                if ($lineDesc !== '' && $bookDesc !== '' && (str_contains($bookDesc, $lineDesc) || str_contains($lineDesc, $bookDesc))) {
                    $score += 10;
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $book;
                }
            }

            if ($best && $bestScore >= 50) {
                $suggestions[] = [
                    'bank_statement_line_id' => $line->bank_statement_line_id,
                    'journal_entry_detail_id' => $best->journal_entry_detail_id,
                    'score' => $bestScore,
                    'statement' => [
                        'date' => Carbon::parse($line->transaction_date)->format('d-m-Y'),
                        'amount' => (float) $line->amount,
                        'reference' => $line->reference,
                        'description' => $line->description,
                    ],
                    'book' => [
                        'date' => Carbon::parse($best->entry_date)->format('d-m-Y'),
                        'entry_no' => $best->entry_no,
                        'amount' => (float) $best->debit - (float) $best->credit,
                        'reference' => $best->reference_no,
                        'description' => $best->detail_description ?: $best->entry_description,
                    ],
                ];
            }
        }

        usort($suggestions, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $suggestions;
    }

    public function complete(BankReconciliation $rec): BankReconciliation
    {
        $this->assertDraft($rec);

        $balances = $this->calculateBalances($rec);
        if (!$balances['is_balanced']) {
            throw new Exception('Cannot complete: difference must be zero (within 0.01). Current difference: ' . number_format($balances['difference'], 2));
        }

        $rec->book_balance = $balances['book_balance'];
        $rec->adjusted_book_balance = $balances['adjusted_book_balance'];
        $rec->difference = $balances['difference'];
        $rec->status = 'completed';
        $rec->completed_at = now();
        $rec->completed_by_id = Auth::id();
        $rec->updatedby_id = Auth::id();
        $rec->date_updated = now();
        $rec->save();

        $this->logActivity('bank-reconciliation', $rec->bank_reconciliation_id, 'completed', null, [
            'difference' => $rec->difference,
            'statement_closing_balance' => $rec->statement_closing_balance,
        ], null, $rec->business_id, $rec->branch_id);

        return $rec;
    }

    public function reopen(BankReconciliation $rec): BankReconciliation
    {
        if ($rec->status !== 'completed') {
            throw new Exception('Only completed reconciliations can be reopened.');
        }

        $otherDraft = BankReconciliation::where('business_id', $rec->business_id)
            ->where('account_id', $rec->account_id)
            ->where('status', 'draft')
            ->where('is_deleted', 0)
            ->where('bank_reconciliation_id', '!=', $rec->bank_reconciliation_id)
            ->exists();

        if ($otherDraft) {
            throw new Exception('A draft reconciliation already exists for this account.');
        }

        $rec->status = 'draft';
        $rec->completed_at = null;
        $rec->completed_by_id = null;
        $rec->updatedby_id = Auth::id();
        $rec->date_updated = now();
        $this->refreshBalances($rec);

        $this->logActivity('bank-reconciliation', $rec->bank_reconciliation_id, 'reopened', null, null, null, $rec->business_id, $rec->branch_id);

        return $rec;
    }

    public function destroy(BankReconciliation $rec): void
    {
        $this->assertDraft($rec);

        DB::transaction(function () use ($rec) {
            $matchedDetailIds = $rec->statementLines()
                ->where('match_status', 'matched')
                ->whereNotNull('matched_journal_entry_detail_id')
                ->pluck('matched_journal_entry_detail_id');

            if ($matchedDetailIds->isNotEmpty()) {
                JournalEntryDetail::whereIn('journal_entry_detail_id', $matchedDetailIds)
                    ->where('bank_reconciliation_id', $rec->bank_reconciliation_id)
                    ->update([
                        'is_reconciled' => 0,
                        'bank_reconciliation_id' => null,
                        'reconciled_at' => null,
                        'reconciled_by_id' => null,
                    ]);
            }

            $rec->statementLines()->update([
                'match_status' => 'unmatched',
                'matched_journal_entry_detail_id' => null,
                'is_deleted' => 1,
                'deletedby_id' => Auth::id(),
                'date_deleted' => now(),
            ]);

            $rec->is_deleted = 1;
            $rec->deletedby_id = Auth::id();
            $rec->date_deleted = now();
            $rec->updatedby_id = Auth::id();
            $rec->date_updated = now();
            $rec->save();
        });

        $this->logActivity('bank-reconciliation', $rec->bank_reconciliation_id, 'deleted', null, null, null, $rec->business_id, $rec->branch_id);
    }

    public function workspacePayload(BankReconciliation $rec): array
    {
        if ($rec->isDraft()) {
            $this->refreshBalances($rec);
            $rec->refresh();
        }

        $balances = $this->calculateBalances($rec);

        return [
            'reconciliation' => $rec,
            'balances' => $balances,
            'book_lines' => $this->bookLines($rec),
            'statement_lines' => $this->statementLines($rec),
        ];
    }

    protected function assertDraft(BankReconciliation $rec): void
    {
        if ($rec->is_deleted) {
            throw new Exception('Reconciliation not found.');
        }
        if (!$rec->isDraft()) {
            throw new Exception('This reconciliation is completed and read-only. Reopen it to make changes.');
        }
    }
}
