<?php

namespace App\Services\Concrete\Admin;

use App\Enums\Filter;
use App\Enums\RoleNames;
use App\Enums\Status;
use App\Models\Journal;
use App\Repository\Repository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class JournalEntryService
{
      protected $model_journal;

      public function __construct()
      {
            $this->model_journal = new Repository(new Journal());
      }

      public function getData($obj)
      {
            $wh = [];
            $orderBy = Filter::ORDERBY;

            if (isset($obj['orderBy']) && $obj['orderBy'] != 0 && $obj['orderBy'] != "") {
                  $orderBy = $obj['orderBy'];
            }
            if (!empty($obj['start_date'])) {
                  $wh[] = ['date_created', '>=', Carbon::parse($obj['start_date'])->startOfDay()];
            }

            if (!empty($obj['end_date'])) {
                  $wh[] = ['date_created', '<=', Carbon::parse($obj['end_date'])->endOfDay()];
            }
            $datatable = $this->model_journal->getModel()::where($wh)
                  ->where('is_deleted', 0)
                  ->orderBy('name', $orderBy);
            return DataTables::of($datatable)
                  ->addColumn('action', function ($item) {

                        return "
                    <a class='btn btn-icon btn-outline-primary mr-2'
                     id='editJournal' href='javascript:void(0)'
                      data-toggle='tooltip'  data-id='" . $item->journal_id . "' data-original-title='Edit'><i title='Edit' class='icon-base fa fa-pencil'></i></a>

                    <a class='btn btn-icon btn-outline-danger'
                    id='deleteJournal'
                    data-id='{$item->journal_id}'>

                    <i class='fa fa-trash'></i>
                    </a>
                ";
                  })
                  ->rawColumns(['action'])
                  ->make(true);
      }

      public function save($obj)
      {

            if (!empty($obj['journal_id'])) {
                  $obj['updatedby_id'] = Auth::user()->id;
                  $obj['date_updated'] = now();
                  $this->model_journal->update($obj, $obj['journal_id']);
                  return $this->model_journal->find($obj['journal_id']);
            }

            $obj['journal_id'] = generateUuid();
            $obj['createdby_id'] = Auth::user()->id;
            $obj['date_created'] = now();
            $saved_obj = $this->model_journal->create($obj);
            return $saved_obj;
      }

      public function getById($journal_id)
      {
            return $this->model_journal->find($journal_id);
      }

      public function delete($journal_id)
      {
            return $this->model_journal->update([
                  'is_deleted' => 1,
                  'deletedby_id' => Auth::id(),
                  'date_deleted' => now()
            ], $journal_id);
      }

      public function getAll()
      {
            return $this->model_journal->getModel()::where('is_deleted', 0)
                  ->get();
      }
}
