<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Enums\Status;
use App\Models\IntroPage;
use App\Repository\Repository;
use App\Services\Concrete\Admin\Intro\Concerns\IntroAuditable;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class PageService
{
    use IntroAuditable;

    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroPage());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderBy('date_created');
        return DataTables::of($q)

            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2 editIntroItem' href='javascript:void(0)' data-id='{$item->intro_page_id}'><i class='fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_page_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function save(array $obj)
    {
        if (!empty($obj['intro_page_id'])) {
            $obj = $this->updateAudit($obj);
            $this->repo->update($obj, $obj['intro_page_id']);
            return $this->repo->find($obj['intro_page_id']);
        }
        $obj['intro_page_id'] = generateUuid();
        $obj = $this->createAudit($obj);
        return $this->repo->create($obj);
    }

    public function getById($id)
    {
        return $this->repo->find($id);
    }

    public function delete($id)
    {
        return $this->repo->update($this->deleteAudit(), $id);
    }

    public function getAllActive()
    {
        return $this->repo->getModel()::where('is_deleted', 0)
            ->where('status', Status::ACTIVE)
            ->orderBy('date_created')
            ->get();
    }
}
