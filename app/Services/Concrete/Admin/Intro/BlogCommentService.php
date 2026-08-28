<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroBlogComment;
use App\Models\IntroWebsiteSetting;
use App\Repository\Repository;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class BlogCommentService
{
    protected $repo;

    public function __construct()
    {
        $this->repo = new Repository(new IntroBlogComment());
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::with('blog')
            ->where('is_deleted', 0)
            ->orderByDesc('date_created');

        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }
        if (!empty($obj['blog_id'])) {
            $q->where('intro_blog_id', $obj['blog_id']);
        }

        return DataTables::of($q)
            ->addColumn('blog_title', fn ($item) => $item->blog?->title ?? '-')
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'spam' => 'dark',
                    'hidden' => 'secondary',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-success mr-1' title='Approve' id='approveComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-check'></i></a>
                    <a class='btn btn-icon btn-outline-warning mr-1' title='Reject' id='rejectComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-ban'></i></a>
                    <a class='btn btn-icon btn-outline-secondary mr-1' title='Spam' id='spamComment' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-flag'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_blog_comment_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create(array $obj)
    {
        $requireModeration = IntroWebsiteSetting::where('key', 'comments_require_moderation')->value('value');
        $status = ($requireModeration === '0' || $requireModeration === 'false') ? 'approved' : 'pending';

        $obj['intro_blog_comment_id'] = generateUuid();
        $obj['status'] = $obj['status'] ?? $status;
        $obj['date_created'] = now();
        return $this->repo->create($obj);
    }

    public function moderate($id, string $status, ?string $note = null)
    {
        return $this->repo->update([
            'status' => $status,
            'moderation_note' => $note,
            'moderatedby_id' => Auth::id(),
            'moderated_at' => now(),
        ], $id);
    }

    public function getById($id)
    {
        return $this->repo->find($id)->load('blog');
    }

    public function delete($id)
    {
        return $this->repo->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    public function approvedForBlog($blogId)
    {
        return $this->repo->getModel()::where('intro_blog_id', $blogId)
            ->where('status', 'approved')
            ->where('is_deleted', 0)
            ->orderBy('date_created')
            ->get();
    }
}
