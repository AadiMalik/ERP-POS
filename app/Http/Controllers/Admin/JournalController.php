<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Message;
use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\BusinessService;
use App\Services\Concrete\Admin\JournalService;
use App\Traits\ResponseAPI;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class JournalController extends Controller
{
    use ResponseAPI;

    protected $journal_service;

    public function __construct(JournalService $journal_service)
    {
        $this->middleware('permission:journal.view')->only(['index', 'getData']);
        $this->middleware('permission:journal.create|journal.edit')->only(['store']);
        $this->middleware('permission:journal.edit')->only(['edit']);
        $this->middleware('permission:journal.delete')->only(['destroy']);

        $this->journal_service = $journal_service;
    }

    public function index()
    {
        return view('admin.journal.index');
    }

    public function getData(Request $request)
    {
        return $this->journal_service->getData($request->all());
    }

    public function store(Request $request)
    {
        $rules = [
            'name' => [
                'required',
                Rule::unique('journals', 'name')
                    ->where(function ($query) use ($request) {
                        return $query->where('is_deleted', 0);
                    })
                    ->ignore($request->jounral_id, 'jounral_id')
            ],
            'short' => [
                'required',
                Rule::unique('journals', 'short')
                    ->where(function ($query) use ($request) {
                        return $query->where('is_deleted', 0);
                    })
                    ->ignore($request->jounral_id, 'jounral_id')
            ],
        ];

        $validate = Validator::make($request->all(), $rules);
        if ($validate->fails()) {
            return $this->validationResponse($validate->errors()->first());
        }


        $obj = $request->only([
            'journal_id',
            'name',
            'short'
        ]);

        // create/update journal
        $journal = $this->journal_service->save($obj);
        return $this->success(
            empty($request->journal_id) ? Message::SAVE : Message::UPDATE,
            $journal
        );
    }
    public function edit($journal_id)
    {
        try {
            $journal = $this->journal_service->getById($journal_id);
            return $this->success(
                Message::FETCH,
                $journal
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function destroy($journal_id)
    {
        try {

            $this->journal_service->delete($journal_id);

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

}
