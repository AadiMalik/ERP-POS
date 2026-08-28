<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroBlog;
use App\Repository\Repository;
use App\Services\Concrete\Admin\Intro\Concerns\IntroAuditable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class BlogService
{
    use IntroAuditable;

    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroBlog());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with(['category', 'tags'])
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['category_id'])) {
            $q->where('intro_blog_category_id', $obj['category_id']);
        }

        return DataTables::of($q)
            ->addColumn('category', fn ($item) => $item->category?->name ?? '-')
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'draft' => 'secondary',
                    'published' => 'success',
                    'scheduled' => 'warning',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2 editIntroItem' href='javascript:void(0)' data-id='{$item->intro_blog_id}'><i class='fa fa-pencil'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_blog_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function save(array $obj)
    {
        $tagIds = $obj['tag_ids'] ?? [];
        unset($obj['tag_ids']);

        if (empty($obj['reading_time']) && !empty($obj['content'])) {
            $text = is_string($obj['content']) ? $obj['content'] : json_encode($obj['content']);
            $words = str_word_count(strip_tags($text));
            $obj['reading_time'] = max(1, (int) ceil($words / 200));
        }

        return DB::transaction(function () use ($obj, $tagIds) {
            if (!empty($obj['intro_blog_id'])) {
                $obj = $this->updateAudit($obj);
                $this->repo->update($obj, $obj['intro_blog_id']);
                $blog = $this->repo->find($obj['intro_blog_id']);
            } else {
                $obj['intro_blog_id'] = generateUuid();
                if (empty($obj['author_id'])) {
                    $obj['author_id'] = Auth::id();
                }
                $obj = $this->createAudit($obj);
                $blog = $this->repo->create($obj);
            }
            $blog->tags()->sync($tagIds ?: []);
            return $blog->load(['category', 'tags', 'author']);
        });
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load(['category', 'tags', 'author']);
    }

    public function delete($id)
    {
        return $this->repo->update($this->deleteAudit(), $id);
    }
}
