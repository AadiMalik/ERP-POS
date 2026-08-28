<?php

namespace App\Services\Concrete\Admin\Intro;

use App\Models\IntroContactInquiry;
use App\Models\IntroContactReply;
use App\Repository\Repository;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ContactInquiryService
{
    protected $repo;
    protected $email_service;

    public function __construct(EmailService $email_service)
    {
        $this->repo = new Repository(new IntroContactInquiry());
        $this->email_service = $email_service;
    }

    public function getData($obj = [])
    {
        $q = $this->repo->getModel()::where('is_deleted', 0)->orderByDesc('date_created');
        if (!empty($obj['status_filter'])) {
            $q->where('status', $obj['status_filter']);
        }

        return DataTables::of($q)
            ->addColumn('status_badge', function ($item) {
                $map = [
                    'new' => 'danger',
                    'read' => 'warning',
                    'replied' => 'success',
                    'closed' => 'secondary',
                ];
                $cls = $map[$item->status] ?? 'secondary';
                return '<span class="badge bg-label-' . $cls . '">' . e(ucfirst($item->status)) . '</span>';
            })
            ->addColumn('date_created', fn ($item) => localDateTime($item->date_created))
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='viewIntroInquiry' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-danger deleteIntroItem' data-id='{$item->intro_contact_inquiry_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create(array $obj)
    {
        $obj['intro_contact_inquiry_id'] = generateUuid();
        $obj['status'] = 'new';
        $obj['date_created'] = now();
        return $this->repo->create($obj);
    }

    public function getById($id)
    {
        $inquiry = $this->repo->find($id)->load('replies');
        if ($inquiry->status === 'new') {
            $inquiry->update(['status' => 'read']);
        }
        return $inquiry->fresh('replies');
    }

    public function updateStatus($id, string $status)
    {
        return $this->repo->update(['status' => $status], $id);
    }

    public function reply($id, string $message)
    {
        $inquiry = $this->repo->find($id);

        $result = $this->email_service->sendPlatform(new EmailData([
            'to' => $inquiry->email,
            'subject' => 'Re: ' . ($inquiry->subject ?: 'Your message to Dukanaz'),
            'body' => nl2br(e($message)),
        ]));

        $reply = IntroContactReply::create([
            'intro_contact_reply_id' => generateUuid(),
            'intro_contact_inquiry_id' => $inquiry->intro_contact_inquiry_id,
            'reply_message' => $message,
            'send_status' => $result['status'] ? 'sent' : 'failed',
            'error_message' => $result['status'] ? null : ($result['message'] ?? 'Send failed'),
            'repliedby_id' => Auth::id(),
            'date_created' => now(),
        ]);

        if (!$result['status']) {
            throw new Exception($result['message'] ?? 'Failed to send email.');
        }

        $inquiry->update(['status' => 'replied']);
        return $reply;
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
