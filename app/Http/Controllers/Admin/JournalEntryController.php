<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Concrete\Admin\AccountService;
use App\Services\Concrete\Admin\JournalEntryService;
use App\Services\Concrete\Admin\JournalService;
use App\Traits\ResponseAPI;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    use ResponseAPI;

    protected $journal_entry_service;
    protected $journal_service;
    protected $account_service;

    public function __construct(
        JournalEntryService $journal_entry_service,
        JournalService $journal_service,
        AccountService $account_service
    ) {
        $this->journal_entry_service = $journal_entry_service;
        $this->journal_service = $journal_service;
        $this->account_service = $account_service;
    }

    public function index()
    {
        return view('admin.journal_entry.index');
    }

    public function getData(Request $request)
    {
        return $this->journal_service->getData($request->all());
    }

    public function create()
    {
        $journals = $this->journal_service->getAll();
        $accounts = $this->account_service->getAllChild();
        return view('admin.journal_entry.create', compact('journals', 'accounts'));
    }
}
