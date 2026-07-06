<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\JournalEntry;
use App\Models\JournalEntryDetail;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class PurchaseOrderService
{
      protected $model_journal_entry;
      protected $model_journal_entry_details;
      protected $with = [
            'business',
            'branch',
            'journal',
            'journalEntryDetails'
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

      public function save($obj)
      {
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
                              'updatedby_id' => Auth::user()->id,
                              'date_updated' => now(),
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
                              'journal_id'       => $obj['journal_id'],
                              'entry_no'         => $obj['entry_no'],
                              'entry_date'       => $obj['entry_date'],
                              'reference_no'     => $obj['reference_no'],
                              'description'      => $obj['description'],
                              'business_id'      => $obj['business_id'],
                              'branch_id'        => $obj['branch_id'],
                              'createdby_id'     => Auth::user()->id,
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

                  DB::commit();

                  return true;
            } catch (Exception $e) {

                  DB::rollBack();

                  throw $e;
            }
      }

      public function getById($journal_entry_id)
      {
            return $this->model_journal_entry->find($journal_entry_id);
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

      public function delete($journal_entry_id)
      {
            return $this->model_journal_entry->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $journal_entry_id);
      }

      public function getAll()
      {
            return $this->model_journal_entry->getModel()::where('is_deleted', 0)
                  ->get();
      }
}
