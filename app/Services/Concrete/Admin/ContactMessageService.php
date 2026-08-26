<?php

namespace App\Services\Concrete\Admin;

use App\Enums\RoleNames;
use App\Models\ContactMessage;
use App\Repository\Repository;
use App\Services\Concrete\Email\DTO\EmailData;
use App\Services\Concrete\Email\EmailService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\DataTables;

class ContactMessageService
{
    protected $model_message;
    protected $email_service;

    public function __construct(EmailService $email_service)
    {
        $this->model_message = new Repository(new ContactMessage());
        $this->email_service = $email_service;
    }

    public function getData($obj)
    {
        $wh = [];
        if (isset($obj['business_id']) && $obj['business_id'] != '') {
            $wh[] = ['business_id', $obj['business_id']];
        }
        if (!empty($obj['status_filter'])) {
            $wh[] = ['status', $obj['status_filter']];
        }

        $allow_roles = [RoleNames::SUPERADMIN, RoleNames::BUSINESSADMIN];
        $datatable = $this->model_message->getModel()::where($wh)
            ->where('is_deleted', 0)
            ->orderBy('date_created', 'desc');
        $datatable = applyRoleScope($datatable, $allow_roles);

        return DataTables::of($datatable)
            ->addColumn('status', function ($item) {
                $map = [
                    'unread' => 'bg-label-danger',
                    'read' => 'bg-label-warning',
                    'replied' => 'bg-label-success',
                ];
                $cls = $map[$item->status] ?? 'bg-label-secondary';
                return '<span class="badge ' . $cls . '">' . ucfirst($item->status) . '</span>';
            })
            ->addColumn('date_created', function ($item) {
                return localDateTime($item->date_created);
            })
            ->addColumn('action', function ($item) {
                return "
                    <a class='btn btn-icon btn-outline-primary mr-2' id='viewContactMessage' href='javascript:void(0)' data-id='{$item->contact_message_id}' data-original-title='View'><i class='icon-base fa fa-eye'></i></a>
                    <a class='btn btn-icon btn-outline-danger' id='deleteContactMessage' data-id='{$item->contact_message_id}'><i class='fa fa-trash'></i></a>
                ";
            })
            ->rawColumns(['status', 'action'])
            ->make(true);
    }

    public function getById($id)
    {
        $message = $this->model_message->find($id);

        if ($message->status === 'unread') {
            $message->update(['status' => 'read']);
        }

        return $message;
    }

    public function reply($id, $business_id, $reply_message)
    {
        $message = $this->model_message->find($id);

        $result = $this->email_service->send($business_id, new EmailData([
            'to' => $message->email,
            'subject' => 'Re: ' . ($message->subject ?: 'Your message to us'),
            'body' => nl2br(e($reply_message)),
        ]));

        if (!$result['status']) {
            throw new Exception($result['message']);
        }

        $message->update([
            'status' => 'replied',
            'reply_message' => $reply_message,
            'replied_at' => now(),
            'repliedby_id' => Auth::id(),
        ]);

        return $message;
    }

    public function delete($id)
    {
        return $this->model_message->update([
            'is_deleted' => 1,
            'deletedby_id' => Auth::id(),
            'date_deleted' => now(),
        ], $id);
    }

    /**
     * Public storefront submission - stores the message; does not send any
     * email itself (that only happens when an admin replies).
     */
    public function submit($business_id, array $data)
    {
        return $this->model_message->getModel()::create([
            'contact_message_id' => generateUuid(),
            'business_id' => $business_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'subject' => $data['subject'] ?? null,
            'message' => $data['message'],
            'status' => 'unread',
            'date_created' => now(),
        ]);
    }
}
