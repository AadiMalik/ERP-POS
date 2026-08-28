<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroMedia;
use App\Repository\Repository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Yajra\DataTables\DataTables;

class MediaService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroMedia());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderByDesc('date_created');
        if (!empty($obj['collection'])) {
            $q->where('collection', $obj['collection']);
        }

        return DataTables::of($q)
            ->addColumn('preview', function ($item) {
                return $item->url
                    ? '<img src="' . e($item->url) . '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;">'
                    : '-';
            })
            ->addColumn('action', function ($item) {
                return "<a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_media_id}'><i class='fa fa-trash'></i></a>";
            })
            ->rawColumns(['preview', 'action'])
            ->make(true);
    }

    public function upload(UploadedFile $file, string $collection = 'general', ?string $alt = null)
    {
        $path = public_path('uploads/intro/media');
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }
        $filename = time() . '_' . preg_replace('/\s+/', '_', $file->getClientOriginalName());
        $file->move($path, $filename);

        return $this->repo->create([
            'intro_media_id' => generateUuid(),
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'disk_path' => 'uploads/intro/media/' . $filename,
            'mime_type' => $file->getClientMimeType() ?: $file->getMimeType(),
            'collection' => $collection,
            'alt_text' => $alt,
            'size' => @filesize($path . DIRECTORY_SEPARATOR . $filename) ?: null,
            'createdby_id' => Auth::id(),
            'date_created' => now(),
        ]);
    }

    public function getById($id)
    {
        return $this->repo->find($id);
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }
}
