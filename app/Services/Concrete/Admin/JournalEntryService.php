<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Repository\Repository;
use App\Traits\Auditable;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class JournalEntryService
{
      use Auditable;

      protected $model_journal_entry;
      protected $model_journal_entry_details;
      protected $with = [
            'business',
            'branch',
            'journal',
            'journalEntryDetails',
            'journalEntryDetails.account'
      ];

      public function __construct()
      {
            $this->model_journal_entry = new Repository(new JournalEntry());
            $this->model_journal_entry_details = new Repository(new JournalEntryDetail());
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
            if (isset($obj['brand_id']) && $obj['brand_id'] != 0 && $obj['brand_id'] != "") {
                  $wh[] = ['brand_id', $obj['brand_id']];
            }
            if (!empty($obj['start_date'])) {
                  $wh[] = ['entry_date', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
            }

            if (!empty($obj['end_date'])) {
                  $wh[] = ['entry_date', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
            }
            $allow_roles = [
                  RoleNames::SUPERADMIN,
                  RoleNames::BUSINESSADMIN
            ];
            $datatable = $this->model_journal_entry->getModel()::with($this->with)
                  ->withSum('journalEntryDetails as total_debit', 'debit')
                  ->withSum('journalEntryDetails as total_credit', 'credit')
                  ->where($wh)
                  ->where('is_deleted', 0)
                  ->orderBy('entry_date', $orderBy);
            $datatable = applyRoleScope($datatable, $allow_roles);
            return DataTables::of($datatable)
                  ->addColumn('entry_date', function ($item) {
                        return !empty($item->entry_date)
                              ? Carbon::parse($item->entry_date)->format('d-m-Y')
                              : 'N/A';
                  })
                  ->addColumn('journal', function ($item) {
                        return $item->journal->name ?? '';
                  })
                  ->addColumn('business', function ($item) {
                        return $item->business->name ?? '';
                  })
                  ->addColumn('branch', function ($item) {
                        return $item->branch->name ?? '';
                  })
                  ->addColumn('total_debit', function ($item) {
                        return number_format($item->total_debit ?? 0, 3);
                  })

                  ->addColumn('total_credit', function ($item) {
                        return number_format($item->total_credit ?? 0, 3);
                  })
                  ->addColumn('status', function ($item) {
                        return $item->status == 'posted'
                              ? '<span class="badge bg-success">Posted</span>'
                              : '<span class="badge bg-danger">Pending</span>';
                  })
                  ->addColumn('action', function ($item) {

                        return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     href='" . route('journal-entry.edit', $item->journal_entry_id) . "'
                    id='editProduct'>

                    <i class='fa fa-pencil'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-secondary mr-2' target='_blank'
                    href='" . route('journal-entry.print', $item->journal_entry_id) . "'
                    title='Print'>
                    <i class='fa fa-print'></i>
                    </a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteJournalEntry'
                    data-id='{$item->journal_entry_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['entry_date', 'business', 'branch', 'journal', 'total_debit', 'total_credit', 'status', 'action'])
                  ->make(true);
      }

      public function save($obj, string $status = 'posted')
      {
            if ($status === Status::POSTED) {
                  app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($obj['business_id'] ?? null, $obj['entry_date'] ?? null);
            }

            DB::beginTransaction();

            try {

                  //==============================
                  // Update
                  //==============================
                  if (!empty($obj['journal_entry_id'])) {

                        $journal_entry = $this->model_journal_entry->getModel()::findOrFail($obj['journal_entry_id']);

                        $journal_entry->update([
                              'journal_id'   => $obj['journal_id'],
                              'entry_no'     => $obj['entry_no'],
                              'entry_date'   => $obj['entry_date'],
                              'reference_no' => $obj['reference_no'],
                              'description'  => $obj['description'],
                              'business_id'  => $obj['business_id'],
                              'branch_id'    => $obj['branch_id'],
                              'status'       => $status,
                              'updatedby_id' => Auth::user()->id,
                              'date_updated' => now(),
                              'date_posted'  => $status === 'posted' ? now() : null,
                        ]);

                        // Purani details remove
                        $this->model_journal_entry_details->getModel()::where('journal_entry_id', $journal_entry->journal_entry_id)
                              ->delete();
                  }

                  //==============================
                  // Create
                  //==============================
                  else {

                        $journal_entry = $this->model_journal_entry->create([
                              'journal_entry_id' => generateUuid(),
                              'recurring_transaction_id'     => $obj['recurring_transaction_id'] ?? null,
                              'recurring_transaction_run_id' => $obj['recurring_transaction_run_id'] ?? null,
                              'journal_id'       => $obj['journal_id'],
                              'entry_no'         => $obj['entry_no'],
                              'entry_date'       => $obj['entry_date'],
                              'reference_no'     => $obj['reference_no'],
                              'description'      => $obj['description'],
                              'business_id'      => $obj['business_id'],
                              'branch_id'        => $obj['branch_id'],
                              'status'           => $status,
                              'createdby_id'     => Auth::user()->id,
                              'date_posted'      => $status === 'posted' ? now() : null,
                              'date_created'     => now(),
                        ]);
                  }

                  //==============================
                  // Save Details
                  //==============================
                  foreach ($obj['details'] as $detail) {

                        $this->model_journal_entry_details->create([
                              'journal_entry_detail_id' => generateUuid(),
                              'journal_entry_id'        => $journal_entry->journal_entry_id,
                              'account_id'   => $detail['account_id'],
                              'debit'        => (float)$detail['debit'],
                              'credit'       => (float)$detail['credit'],
                              'description'  => $detail['description'] ?? null,
                              'reference_no' => $detail['reference_no'] ?? null,
                              'cheque_date'  => !empty($detail['cheque_date']) ? $detail['cheque_date'] : null,
                              'cheque_no'    => $detail['cheque_no'] ?? null,
                              'bill_no'      => $detail['bill_no'] ?? null
                        ]);
                  }

                  self::assertBalanced($journal_entry->journal_entry_id);

                  DB::commit();

                  $this->logActivity(
                        'journal_entry',
                        $journal_entry->journal_entry_id,
                        !empty($obj['journal_entry_id']) ? 'updated' : 'created',
                        null,
                        ['entry_no' => $journal_entry->entry_no, 'status' => $journal_entry->status]
                  );

                  return true;
            } catch (Exception $e) {

                  DB::rollBack();

                  throw $e;
            }
      }

      public function getById($journal_entry_id)
      {
            return $this->model_journal_entry->getModel()::with($this->with)->find($journal_entry_id);
      }

      public function getDetailsById($journal_entry_id)
      {
            $journal_entries = $this->model_journal_entry_details->getModel()::with('account')->where('journal_entry_id', $journal_entry_id)->get();
            $data = [];
            foreach ($journal_entries as $index => $item) {
                  $data[] = [
                        "account_id" => $item->account_id,
                        "account_name" => $item->account->code . ' ' . $item->account->name,
                        "debit" => $item->debit,
                        "credit" => $item->credit,
                        "description" => $item->description,
                        "reference_no" => $item->reference_no,
                        "cheque_date" => $item->cheque_date,
                        "cheque_no" => $item->cheque_no,
                        "bill_no" => $item->bill_no,
                        "tbl_id" => $index,
                        "tbl_index" => $index
                  ];
            }
            return $data;
      }

      /**
       * Move a journal entry between pending/posted. Only needed today for
       * recurring-generated entries saved with auto_post=false (see
       * App\Support\Recurring\Generators\JournalEntryRecurringGenerator) - the
       * manual Create/Edit screen always posts immediately via save().
       */
      public function changeStatus($journal_entry_id, string $status)
      {
            $journal_entry = $this->model_journal_entry->getModel()::findOrFail($journal_entry_id);
            $old_status = $journal_entry->status;

            if ($status === Status::POSTED) {
                  app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);
            }

            $journal_entry->update([
                  'status'       => $status,
                  'updatedby_id' => Auth::id(),
                  'date_updated' => now(),
                  'date_posted'  => $status === 'posted' ? now() : null,
            ]);

            $this->logActivity(
                  'journal_entry',
                  $journal_entry_id,
                  $status === 'posted' ? 'posted' : 'unposted',
                  ['status' => $old_status],
                  ['status' => $status]
            );

            return $journal_entry;
      }

      public function delete($journal_entry_id)
      {
            $journal_entry = $this->model_journal_entry->getModel()::findOrFail($journal_entry_id);

            app(\App\Services\Concrete\Admin\AccountingPeriodService::class)->assertPostable($journal_entry->business_id, $journal_entry->entry_date);

            $result = $this->model_journal_entry->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $journal_entry_id);

            $this->logActivity('journal_entry', $journal_entry_id, 'deleted');

            return $result;
      }

      /**
       * Re-sums a journal entry's detail rows and throws if debit != credit -
       * the single authoritative balance check, called from every code path
       * that writes JournalEntryDetail rows (manual save() below, the import
       * path, and every automated posting method across the app) so the
       * guarantee holds regardless of entry point.
       */
      public static function assertBalanced(string $journalEntryId): void
      {
            $totals = JournalEntryDetail::where('journal_entry_id', $journalEntryId)
                  ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
                  ->first();

            if (abs((float) $totals->total_debit - (float) $totals->total_credit) > 0.01) {
                  throw new Exception('Journal entry is not balanced: total debit (' . number_format((float) $totals->total_debit, 2) . ') must equal total credit (' . number_format((float) $totals->total_credit, 2) . ').');
            }
      }

      public function getAll()
      {
            return $this->model_journal_entry->getModel()::where('is_deleted', 0)
                  ->get();
      }
}
