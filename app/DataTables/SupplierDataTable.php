<?php

namespace App\DataTables;

use App\Enums\Status;
use App\Models\Business;
use App\Models\Supplier;
use App\Support\DataTables\AbstractDataTable;
use App\Support\DataTables\DataTableFilterEngine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SupplierDataTable extends AbstractDataTable
{
    public function key(): string
    {
        return 'suppliers';
    }

    public function label(): string
    {
        return __('suppliers.title');
    }

    public function permission(): string
    {
        return 'supplier.view';
    }

    public function exportPermission(): string
    {
        return 'supplier.export';
    }

    public function moduleKey(): ?string
    {
        return 'inventory';
    }

    public function rowId(): string
    {
        return 'supplier_id';
    }

    public function defaultSort(): array
    {
        return ['column' => 'name', 'dir' => 'asc'];
    }

    public function query(): Builder
    {
        $query = Supplier::query()
            ->select('suppliers.*')
            ->with(['business', 'branch', 'account'])
            ->where('suppliers.is_deleted', 0);

        return applyRoleScope(
            $query,
            [],
            'suppliers.business_id',
            'suppliers.branch_id'
        );
    }

    public function columns(): array
    {
        $user = Auth::user();

        return [
            $this->column('code', __('common.code'), [
                'name' => 'suppliers.code',
            ]),
            $this->column('name', __('common.name'), [
                'name' => 'suppliers.name',
            ]),
            $this->column('company_name', __('suppliers.company'), [
                'name' => 'suppliers.company_name',
            ]),
            $this->column('email', __('common.email'), [
                'name' => 'suppliers.email',
            ]),
            $this->column('phone', __('common.phone'), [
                'name' => 'suppliers.phone',
            ]),
            $this->column('address', __('common.address'), [
                'name' => 'suppliers.address',
            ]),
            $this->column('city', __('common.city'), [
                'name' => 'suppliers.city',
                'visible' => false,
            ]),
            $this->column('contact_person', __('suppliers.contact_person'), [
                'name' => 'suppliers.contact_person',
                'visible' => false,
            ]),
            $this->column('balance', __('common.balance'), [
                'name' => 'suppliers.balance',
                'formatter' => fn ($item) => currency($item->balance ?? 0),
                'export_formatter' => fn ($item) => $item->balance ?? 0,
            ]),
            $this->column('credit_limit', __('suppliers.credit_limit'), [
                'name' => 'suppliers.credit_limit',
                'visible' => false,
                'permission' => 'supplier.view-financial',
                'formatter' => fn ($item) => currency($item->credit_limit ?? 0),
                'export_formatter' => fn ($item) => $item->credit_limit ?? 0,
            ]),
            $this->column('opening_balance', __('suppliers.opening_balance'), [
                'name' => 'suppliers.opening_balance',
                'visible' => false,
                'permission' => 'supplier.view-financial',
                'formatter' => fn ($item) => currency($item->opening_balance ?? 0),
                'export_formatter' => fn ($item) => $item->opening_balance ?? 0,
            ]),
            $this->column('ntn', __('suppliers.ntn'), [
                'name' => 'suppliers.ntn',
                'visible' => false,
            ]),
            $this->column('status', __('common.status'), [
                'name' => 'suppliers.status',
                'searchable' => false,
                'orderable' => true,
                'exportable' => true,
                'html' => true,
                'formatter' => function ($item) use ($user) {
                    if ($user && $user->can('supplier.status')) {
                        $checked = $item->status == Status::ACTIVE ? 'checked' : '';

                        return '
                <div class="form-check form-switch mb-0">
                    <input
                        class="form-check-input statusSupplier"
                        type="checkbox"
                        data-id="' . e($item->supplier_id) . '"
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
                'name' => 'suppliers.branch_id',
                'searchable' => false,
                'orderable' => false,
                'visible' => false,
                'formatter' => fn ($item) => $item->branch->name ?? '',
                'export_formatter' => fn ($item) => $item->branch->name ?? '',
            ]),
            $this->column('business', __('common.business'), [
                'name' => 'suppliers.business_id',
                'searchable' => false,
                'orderable' => false,
                'formatter' => fn ($item) => $item->business->name ?? '',
                'export_formatter' => fn ($item) => $item->business->name ?? '',
            ]),
            $this->column('date_created', __('common.created_at'), [
                'name' => 'suppliers.date_created',
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
                    if ($user && $user->can('supplier.view')) {
                        $html .= "<a class='btn btn-icon btn-outline-info mr-2' href='" . route('supplier.show', $item->supplier_id) . "' id='viewSupplier'><i class='fa fa-eye'></i></a>";
                    }
                    if ($user && $user->can('supplier.edit')) {
                        $html .= "<a class='btn btn-icon btn-outline-primary mr-2' href='" . route('supplier.edit', $item->supplier_id) . "' id='editSupplier'><i class='fa fa-pencil'></i></a>";
                    }
                    if ($user && $user->can('supplier.delete')) {
                        $html .= "<a class='btn btn-icon btn-outline-danger' id='deleteSupplier' data-id='{$item->supplier_id}'><i class='fa fa-trash'></i></a>";
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
                'column' => 'suppliers.business_id',
                'placeholder' => __('common.all_businesses'),
                'options' => $this->businessOptions(),
            ]);
        }

        $filters[] = $this->filter('date_created', 'date_range', __('common.date'), [
            'column' => 'suppliers.date_created',
        ]);

        return $filters;
    }

    public function listing(): array
    {
        return [
            'title' => $this->label(),
            'table_id' => 'supplier_table',
            'create_url' => url('admin/supplier/create'),
            'create_permission' => 'supplier.create',
            'import_export_module' => 'supplier',
            'export_params_selector' => '#business_id',
            'status' => [
                'button_class' => '.statusSupplier',
                'url' => url('admin/supplier/change-status'),
            ],
            'delete' => [
                'button_class' => '#deleteSupplier',
                'url' => url('admin/supplier'),
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
