<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Enums\RoleNames;
use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use App\Services\Concrete\Admin\JournalEntryService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Single reusable "View JV" endpoint, consumed from a small AJAX modal
 * dropped into any page that has a Journal Voucher to show (Orders,
 * Purchases, Customer/Supplier Payments, Expenses, ...). Deliberately not
 * gated behind `permission:journal-entry.view` - a Sales user viewing the
 * JV behind their own order shouldn't need full Journal Entry module
 * access; business-scoping below is the only access control.
 */
class JournalVoucherViewController extends Controller
{
    use ResponseAPI;

    protected $journal_entry_service;

    public function __construct(JournalEntryService $journal_entry_service)
    {
        $this->journal_entry_service = $journal_entry_service;
    }

    public function show(Request $request)
    {
        $journal_entry = null;

        if ($request->filled('journal_entry_id')) {
            $journal_entry = JournalEntry::with(['business', 'branch', 'journal'])
                ->where('journal_entry_id', $request->journal_entry_id)
                ->where('is_deleted', 0)
                ->first();
        } elseif ($request->filled('source_type') && $request->filled('source_id')) {
            $journal_entry = JournalEntry::with(['business', 'branch', 'journal'])
                ->where('source_type', $request->source_type)
                ->where('source_id', $request->source_id)
                ->where('is_deleted', 0)
                ->first();
        }

        if (!$journal_entry) {
            return $this->error('No Journal Voucher found for this record.');
        }

        if (getRoleName() !== RoleNames::SUPERADMIN && $journal_entry->business_id !== Auth::user()->business_id) {
            return $this->error(Message::ERROR);
        }

        $lines = $this->journal_entry_service->getDetailsById($journal_entry->journal_entry_id);

        return $this->success(Message::SUCCESS, [
            'journal_entry_id' => $journal_entry->journal_entry_id,
            'entry_no'         => $journal_entry->entry_no,
            'reference_no'     => $journal_entry->reference_no,
            'entry_date'       => localDate($journal_entry->entry_date),
            'description'      => $journal_entry->description,
            'status'           => $journal_entry->status,
            'source_type'      => $journal_entry->source_type,
            'source_id'        => $journal_entry->source_id,
            'journal_name'     => $journal_entry->journal->name ?? '',
            'journal_short'    => $journal_entry->journal->short ?? '',
            'business'         => $journal_entry->business->name ?? '',
            'branch'           => $journal_entry->branch->name ?? '',
            'lines'            => $lines,
            'total_debit'      => array_sum(array_column($lines, 'debit')),
            'total_credit'     => array_sum(array_column($lines, 'credit')),
        ]);
    }
}
