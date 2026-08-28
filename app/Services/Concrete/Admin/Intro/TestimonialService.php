<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Enums\Status;
use App\Models\IntroTestimonial;
use App\Repository\Repository;
use App\Services\Concrete\Admin\Intro\Concerns\IntroAuditable;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class TestimonialService
{
    use IntroAuditable;

    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroTestimonial());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderBy('display_order');
        return DataTables::of($q)

            ->addColumn('status', function ($item) {
                $checked = $item->status == Status::ACTIVE ? 'checked' : '';
                return '<div class="form-check form-switch mb-0"><input class="form-check-input statusToggle" type="checkbox" data-id="' . $item->intro_testimonial_id . '" ' . $checked . '></div>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2 editIntroItem' href='javascript:void(0)' data-id='{$item->intro_testimonial_id}'><i class='fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_testimonial_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status','action'])
            ->make(true);
    }

    public function save(array $obj)
    {
        if (!empty($obj['intro_testimonial_id'])) {
            $obj = $this->updateAudit($obj);
            $this->repo->update($obj, $obj['intro_testimonial_id']);
            return $this->repo->find($obj['intro_testimonial_id']);
        }
        $obj['intro_testimonial_id'] = generateUuid();
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
            ->orderBy('display_order')
            ->get();
    }

    public function status($id)
    {
        $row = $this->repo->find($id);
        return $this->repo->update([
            'status' => ($row->status == Status::ACTIVE ? Status::INACTIVE : Status::ACTIVE),
            'updatedby_id' => Auth::id(),
            'date_updated' => now(),
        ], $id);
    }
}
