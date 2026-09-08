<?php

namespace App\DataTables;

use App\Enums\Status;
use App\Models\Business;
use App\Models\CustomerProfile;
use App\Support\DataTables\AbstractDataTable;
use App\Support\DataTables\DataTableFilterEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CustomerDataTable extends AbstractDataTable
{
    public function key(): string
    {
        return 'customers';
    }

    public function label(): string
    {
        return __('customers.title');
    }

    public function permission(): string
    {
        return 'customer.view';
    }

    public function exportPermission(): string
    {
        return 'customer.export';
    }

    public function rowId(): string
    {
        return 'user_id';
    }

    public function defaultSort(): array
    {
        return ['column' => 'date_created', 'dir' => 'desc'];
    }

    public function query(): Builder
    {
        $query = CustomerProfile::query()
            ->select('customer_profiles.*')
            ->leftJoin('users', 'users.id', '=', 'customer_profiles.user_id')
            ->with(['user', 'business', 'branch'])
            ->where('customer_profiles.is_deleted', 0)
            ->where('customer_profiles.is_walkin', 0);

        return applyRoleScope(
            $query,
            [],
            'customer_profiles.business_id',
            'customer_profiles.branch_id'
        );
    }

    public function columns(): array
    {
        $user = Auth::user();

        return [
            $this->column('code', __('common.code'), [
                'name' => 'customer_profiles.code',
            ]),
            $this->column('name', __('common.name'), [
                'name' => 'users.name',
                'formatter' => fn ($item) => $item->user->name ?? '',
                'export_formatter' => fn ($item) => $item->user->name ?? '',
            ]),
            $this->column('email', __('common.email'), [
                'name' => 'users.email',
                'formatter' => fn ($item) => $item->user->email ?? '',
                'export_formatter' => fn ($item) => $item->user->email ?? '',
            ]),
            $this->column('phone', __('common.phone'), [
                'name' => 'users.phone',
                'formatter' => fn ($item) => $item->user->phone ?? '',
                'export_formatter' => fn ($item) => $item->user->phone ?? '',
            ]),
            $this->column('company_name', __('customers.company_name'), [
                'name' => 'customer_profiles.company_name',
                'visible' => false,
            ]),
            $this->column('contact_person', __('customers.contact_person'), [
                'name' => 'customer_profiles.contact_person',
                'visible' => false,
            ]),
            $this->column('address', __('common.address'), [
                'name' => 'customer_profiles.address',
                'visible' => false,
            ]),
            $this->column('city', __('common.city'), [
                'name' => 'customer_profiles.city',
                'visible' => false,
            ]),
            $this->column('credit_limit', __('customers.credit_limit'), [
                'name' => 'customer_profiles.credit_limit',
                'formatter' => fn ($item) => currency($item->credit_limit ?? 0),
                'export_formatter' => fn ($item) => $item->credit_limit ?? 0,
            ]),
            $this->column('credit_days', __('customers.credit_days'), [
                'name' => 'customer_profiles.credit_days',
                'visible' => false,
                'permission' => 'customer.view-financial',
            ]),
            $this->column('opening_balance', __('customers.opening_balance'), [
                'name' => 'customer_profiles.opening_balance',
                'visible' => false,
                'permission' => 'customer.view-financial',
                'formatter' => fn ($item) => currency($item->opening_balance ?? 0),
                'export_formatter' => fn ($item) => $item->opening_balance ?? 0,
            ]),
            $this->column('status', __('common.status'), [
                'name' => 'customer_profiles.status',
                'searchable' => false,
                'orderable' => true,
                'exportable' => true,
                'html' => true,
                'formatter' => function ($item) use ($user) {
                    if ($user && $user->can('customer.status')) {
                        $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                        return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusCustomer"
                        type="checkbox"
                        data-id="' . e($item->user_id) . '"
                        ' . $checked . '>
                </div>';
                    }

                    $label = $item->status == Status::ACTIVE ? __('common.active') : __('common.inactive');
                    $cls = $item->status == Status::ACTIVE ? 'bg-label-success' : 'bg-label-secondary';

                    return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
                },
                'export_formatter' => fn ($item) => $item->status == Status::ACTIVE
                    ? __('common.active')
                    : __('common.inactive'),
            ]),
            $this->column('branch', __('common.branch'), [
                'name' => 'customer_profiles.branch_id',
                'searchable' => false,
                'orderable' => false,
                'visible' => false,
                'formatter' => fn ($item) => $item->branch->name ?? '',
                'export_formatter' => fn ($item) => $item->branch->name ?? '',
            ]),
            $this->column('business', __('common.business'), [
                'name' => 'customer_profiles.business_id',
                'searchable' => false,
                'orderable' => false,
                'formatter' => fn ($item) => $item->business->name ?? '',
                'export_formatter' => fn ($item) => $item->business->name ?? '',
            ]),
            $this->column('date_created', __('common.created_at'), [
                'name' => 'customer_profiles.date_created',
                'visible' => false,
                'formatter' => fn ($item) => $item->date_created ? localDate($item->date_created) : '',
                'export_formatter' => fn ($item) => $item->date_created ? localDate($item->date_created) : '',
            ]),
            $this->column('action', __('common.action'), [
                'name' => 'action',
                'searchable' => false,
                'orderable' => false,
                'exportable' => false,
                'html' => true,
                'always' => true,
                'formatter' => function ($item) use ($user) {
                    $html = '';
                    if ($user && $user->can('customer.view')) {
                        $html .= "<a class='btn btn-icon btn-outline-info mr-2' href='" . route('customer.show', $item->user_id) . "' id='viewCustomer'><i class='fa fa-eye'></i></a>";
                    }
                    if ($user && $user->can('customer.edit')) {
                        $html .= "<a class='btn btn-icon btn-outline-primary mr-2' href='" . route('customer.edit', $item->user_id) . "' id='editCustomer'><i class='fa fa-pencil'></i></a>";
                    }
                    if ($user && $user->can('customer.delete')) {
                        $html .= "<a class='btn btn-icon btn-outline-danger' id='deleteCustomer' data-id='{$item->user_id}'><i class='fa fa-trash'></i></a>";
                    }

                    return $html;
                },
            ]),
        ];
    }

    public function filters(): array
    {
        $filters = [];

        if (DataTableFilterEngine::isSuperAdmin()) {
            $filters[] = $this->filter('business_id', 'business', __('common.business'), [
                'column' => 'customer_profiles.business_id',
                'placeholder' => __('common.all_businesses'),
                'options' => $this->businessOptions(),
            ]);
        }

        $filters[] = $this->filter('date_created', 'date_range', __('common.date'), [
            'column' => 'customer_profiles.date_created',
        ]);

        return $filters;
    }

    public function listing(): array
    {
        return [
            'title' => $this->label(),
            'table_id' => 'customer_table',
            'create_url' => url('admin/customer/create'),
            'create_permission' => 'customer.create',
            'import_export_module' => 'customer',
            'export_params_selector' => '#business_id',
            'status' => [
                'button_class' => '.statusCustomer',
                'url' => url('admin/customer/change-status'),
            ],
            'delete' => [
                'button_class' => '#deleteCustomer',
                'url' => url('admin/customer'),
            ],
        ];
    }

    protected function businessOptions(): array
    {
        return Business::where('is_deleted', 0)
            ->orderBy('name')
            ->get(['business_id', 'name', 'code'])
            ->map(fn ($item) => [
                'value' => $item->business_id,
                'label' => trim(($item->code ? $item->code . ' ' : '') . ($item->name ?? '')),
            ])
            ->all();
    }
}
