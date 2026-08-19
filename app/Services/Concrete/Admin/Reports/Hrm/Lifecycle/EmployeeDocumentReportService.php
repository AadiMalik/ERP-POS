<?php

namespace App\Services\Concrete\Admin\Reports\Hrm\Lifecycle;

use App\Models\EmployeeDocument;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Yajra\DataTables\DataTables;

class EmployeeDocumentReportService extends BaseLifecycleReportService
{
    public function build(array $filters): Collection
    {
        $business_id = $this->resolveBusinessId($filters);

        $query = EmployeeDocument::with(['employee.user', 'employee.department'])
            ->where('is_deleted', 0);

        if (!empty($business_id)) {
            $query->where('business_id', $business_id);
        }
        if (!empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }
        if (!empty($filters['document_type'])) {
            $query->where('document_type', $filters['document_type']);
        }

        $query = $this->scope($query);

        return $query->orderBy('date_created', 'desc')->get()->map(function ($document) {
            if (!$document->expiry_date) {
                $document->expiry_status = 'No Expiry';
            } elseif (Carbon::parse($document->expiry_date)->isPast()) {
                $document->expiry_status = 'Expired';
            } elseif (Carbon::parse($document->expiry_date)->lte(Carbon::today()->addDays(30))) {
                $document->expiry_status = 'Expiring Soon';
            } else {
                $document->expiry_status = 'Valid';
            }

            return $document;
        })->when(!empty($filters['expiry_status']), fn ($rows) => $rows->where('expiry_status', $filters['expiry_status'] ?? null))->values();
    }

    public function getData(array $filters)
    {
        $rows = $this->build($filters);

        return DataTables::of($rows)
            ->addColumn('employee_code', fn ($row) => $row->employee?->employee_code ?? '-')
            ->addColumn('name', fn ($row) => $row->employee?->user?->name ?? '-')
            ->addColumn('department', fn ($row) => $row->employee?->department?->name ?? '-')
            ->addColumn('expiry_date', fn ($row) => $row->expiry_date ? localDate($row->expiry_date) : '-')
            ->addColumn('uploaded_on', fn ($row) => localDate($row->date_created))
            ->make(true);
    }
}
